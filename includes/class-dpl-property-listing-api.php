<?php

/**
 * The class that is responsible for getting the data from the API.
 *
 * @link       https://digitalpie.co.nz/custom-development/
 * @since      1.0.0
 *
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/admin
 */

/**
 * The class that is responsible for getting the data from the API.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/admin
 * @author     Digital Pie <charlie@digitalpie.co.nz>
 */
class Dpl_Property_Listing_Api {

    /**
	 * Calls the API and get the records prepared for mapping
	 * 
	 *
	 * @since    1.0.0
	 * @access   private
     * @param    string     $resource
	 */
    public static function dpl_property_listing_search_property_get( $args = array() ){   
       
        if ( empty($args['resource']) ) {
            return;
        }
        
        $args = array(
            'consumer_key'      =>  get_option('dpl_property_listing_consumer_key',false) ? get_option('dpl_property_listing_consumer_key',false) : "",
            'access_token'      =>  get_option('dpl_property_listing_final_oauth_token',false) ? get_option('dpl_property_listing_final_oauth_token',false) : "",
            'consumer_secret'   =>  get_option('dpl_property_listing_consumer_secret',false) ? get_option('dpl_property_listing_consumer_secret',false) : "",
            'token_secret'      =>  get_option('dpl_property_listing_final_oauth_token_secret',false) ? get_option('dpl_property_listing_final_oauth_token_secret',false) : "",
            'endpoint_url'      =>  "https://api.".DPL_PROPERTY_LISTING_API_DOMAIN.".co.nz/v1/Search/Property/" . $args['resource'].'.json',
            'page'              =>  !empty($args['page']) ? $args['page'] : 1,
            'photo_size'        =>  !empty($args['photo_size']) ? $args['photo_size'] : 'FullSize',
            'search_string'     =>  !empty($args['search_string']) ? $args['search_string'] : ""
        );

        $lists_obj = self::dpl_property_listing_curl( $args );

        //self::dpl_property_print_for_testing($lists_obj);
        
        return $lists_obj;
      
    }

    /**
	 * Sends request to the API using
     * CURL PHP
	 *
	 * @since    1.0.0
	 * @access   private
     * @param    mixed      $args
     * @return   mixed
	 */
    private static function dpl_property_listing_curl( $args = array() ) {

        
        /**
         * Endpoint URL and API Keys
         * 
         */
        $url                =   !empty($args['endpoint_url']) ? $args['endpoint_url'] : "";
        $consumer_key       =   !empty($args['consumer_key']) ? $args['consumer_key'] : "";
        $access_token       =   !empty($args['access_token']) ? $args['access_token'] : "";
        $consumer_secret    =   !empty($args['consumer_secret']) ? $args['consumer_secret'] : "";
        $token_secret       =   !empty($args['token_secret']) ? $args['token_secret'] : "";

        /**
         *  URL Parameters
         */
        $url_params = http_build_query(array(
            'page'          => $args['page'],
            'photo_size'    => $args['photo_size'],
            'search_string' => $args['search_string']
        ));        
       
        $curl = curl_init($url."?".$url_params);
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url."?".$url_params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization: OAuth oauth_consumer_key=".$consumer_key.",oauth_token=".$access_token.",oauth_signature_method=PLAINTEXT,oauth_timestamp=".time().",oauth_nonce=do1buGWpbpv,oauth_version=1.0,oauth_signature=".$consumer_secret."%26".$token_secret
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
        $data_obj = json_decode($response); 

        return curl_errno($curl) ? false : $data_obj;

    }

    /**
	 * Creates the Listings
	 * 
	 *
	 * @since    1.0.0
	 * @access   private
     * @param    int     $ListingId
     * @param    object  $list_obj
     * @param    array   $post_data
	 */
    public static function dpl_property_listing_create_listing( $ListingId, $list_obj, $post_data = array() ){        
        
        global $wpdb;

        $db_post_table = $wpdb->prefix."posts";
        $db_postmeta_table = $wpdb->prefix."postmeta";      

        $meta_key = 'ListingId';

        /**
         * Check if the property already
         * exists
         * 
         */
        $sql = "SELECT * FROM ".$db_postmeta_table." 
                INNER JOIN ".$db_post_table." ON ".$db_post_table.".ID = ".$db_postmeta_table.".post_id 
                WHERE ".$db_post_table.".post_status = 'publish' AND ".$db_postmeta_table.".meta_key = %s AND ".$db_postmeta_table.".meta_value = %s";

        $data_exists = $wpdb->get_row(
            $wpdb->prepare(
                $sql, 
                $meta_key,
                $ListingId
            ), ARRAY_A
        );
        
        if ($data_exists) {

            // Get the post id
            $post_id = $data_exists['post_id'];

            /**
             * Prepare the meta fields to be updated
             * 
             */
            self::dpl_property_listing_map_fields($list_obj,$post_id);

        } else {

            // Set the property data
            $Title = $list_obj->Title;
            $ShortDescription = !empty($list_obj->ShortDescription) ? $list_obj->ShortDescription : "";

            $post_data = array(
                'post_title'    => $Title,
                'post_name'     =>  sanitize_title($Title),
                'post_content'  => !empty($ShortDescription) ? $ShortDescription : $Title,
                'post_status'   => 'publish',
                'post_type'     => 'property',
                'post_author'   => 1
            );

            $post_id = wp_insert_post($post_data);

            /**
             * Prepare the property meta fields to be added
             * 
             */
            self::dpl_property_listing_map_fields($list_obj,$post_id);
        }      
        
        return $post_id > 0 ? $post_id : false;
    }


    /**
	 * Prepare the fields from TradeMe and match them with
	 * the meta_key from the Houze theme
	 *
	 * @since    1.0.0
	 * @access   private
     * @param    object  $list_obj
     * @param    int   $post_id
	 */
    private static function dpl_property_listing_map_fields($list_obj,$post_id){

        if ( !is_object($list_obj) ) {
            return;
        }

        /**
         * Match the fields from TradeMe
         * to the meta_key for the Houze theme
         * 
         * Some meta_keys here are not present from the TradeMe API
         * Most of the fields here are located at the Details part of the property page
         */
        $map_meta_array = array(
            'ListingId'         =>  'ListingID',
            'StartPrice'        =>  'fave_property_price',
            //'StartFrom'         =>  'fave_property_price_prefix',  // not in TradeMe
            //'PricePostfix'      =>  'fave_property_price_postfix', // not in TradeMe
            'RentPerWeek'       =>  'fave_property_price',
            'SizePrefix'        =>  'fave_property_size_prefix',   // not in TradeMe 
            'LandArea'          =>  'fave_property_land',
            'LandPostfix'       =>  'fave_property_land_postfix',  // not in TradeMe 
            'Parking'           =>  'fave_property_garage_size',
            'Has3DTour'         =>  'fave_virtual_tour',
            'Latitude'          =>  'houzez_geolocation_lat',
            'Longitude'         =>  'houzez_geolocation_long',
            'PropertyId'        =>  'fave_property_id',
            'IsFeatured'        =>  'fave_featured',
            'PropertyLocation'  =>  'fave_property_location',      // not in TradeMe 
            'Amenities'         =>  'amenities',
            'AgentDisplayOption'=>  'fave_agent_display_option',
            'DisplayPrice'      =>  'fave_property_price'
            
        );
        
        /**
         * Match the fields from TradeMe
         * to the meta_key for the Houze theme
         * 
         * All of these fields are located at the upper section
         * of the property page
         * 
         */
        $map_details_array = array(
            'Bedrooms'          =>  'fave_property_bedrooms',
            'Bathrooms'         =>  'fave_property_bathrooms',
            'TotalParking'      =>  'fave_property_garage',
            'Area'              =>  'fave_property_size',
        );

        /**
         * Match the fields from TradeMe
         * to the meta_key for the Houze theme
         * 
         * These fields are related to the address
         * of the property 
         * 
         */
        $map_address_array = array(
            'Address'           =>  'fave_property_address',
            'District'          =>  'city',
            'Suburb'            =>  'area',
            'Region'            =>  'state',
            'Country'           =>  'country'       // not in TradeMe
        );

        /**
         * This private function executes the saving
         * of the mapped data
         * 
         */
        self::dpl_property_listing_map_exec(
            $post_id,
            $list_obj,
            $map_meta_array,
            $map_details_array,
            $map_address_array
        );

    }    


    /**
	 * Save the data from the TradeMe API to the 
	 * database
	 *
	 * @since    1.0.0
	 * @access   private
     * @param    int        $post_id
     * @param    object     $list_obj
     * @param    mixed      $map_meta_array
     * @param    mixed      $map_details_array
     * @param    mixed      $map_address_array
	 */
    private static function dpl_property_listing_map_exec( $post_id, $list_obj, $map_meta_array, $map_details_array, $map_address_array ){

        /**
         * Set other meta data
         * 
         */
        foreach($map_meta_array as $prop=>$meta_key) {

            if ( !empty($list_obj->{$prop}) ) {
                update_post_meta($post_id,$meta_key,$list_obj->{$prop});  
            } else {
                if ( $prop == 'Latitude' || $prop == 'Longitude' ) {
                    update_post_meta($post_id,$meta_key,$list_obj->GeographicLocation->{$prop});
                } elseif ( $prop == 'PropertyLocation' ) {
                    update_post_meta($post_id,$meta_key,$list_obj->GeographicLocation->Latitude.','.$list_obj->GeographicLocation->Longitude);
                } elseif ( $prop == 'LandPostfix' ) {
                    update_post_meta($post_id,$meta_key,'square meter'); 
                } elseif ( $prop == 'AgentDisplayOption' ) {
                    if ( !empty($list_obj->Agency->Agents) ) {
                        update_post_meta( $post_id, $meta_key, 'agent_info' );
                    } else {
                        update_post_meta( $post_id, $meta_key, 'agency_info' );
                    }
                } elseif ( $prop == 'StartPrice' ) {
                    $Price = "";
                    if (preg_match("/[0-9]/", $list_obj->PriceDisplay)) {
                        $Price = preg_replace("/[^0-9]/", "", $list_obj->PriceDisplay);
                    }else{
                        $Price = $list_obj->PriceDisplay;
                    }                    
                    update_post_meta($post_id,$meta_key,$Price);
                    // Postfix and Prefix
                    if(isset($_GET['resource']) && $_GET['resource'] == 'Rental') {
                        update_post_meta($post_id,'fave_property_price_postfix','Per Week');                        
                    }
                }
            }
        }

        /**
         * Set primary details like bedroom
         * bathroom, parking, and area
         * 
         */
        foreach($map_details_array as $prop=>$meta_key) {
            if ( !empty($list_obj->{$prop}) ) {
                update_post_meta($post_id,$meta_key,$list_obj->{$prop});
            }
        }
        

        /**
         * Set property photos
         * 
         */
        // Primary Photos
        if ( !empty($list_obj->PictureHref) ) {

            // Check if image is already in the media library
            $photo_name = basename($list_obj->PictureHref);
            $photo_name_array = explode(".",$photo_name);
            $final_photo_name = $photo_name_array[0];
            $image_id = post_exists($final_photo_name, "", "", "attachment");
            if ( !$image_id ) {
                $image_id = media_sideload_image($list_obj->PictureHref, $post_id, $final_photo_name,'id');                 
            }
            set_post_thumbnail($post_id, $image_id); 
                                
        }
        // Other photos : CHECK IF IMAGE ALREADY EXISTS AND CONNECTED TO THE PROPERTY, IF YES, DON"T ADD TO THE PROPERTY
        if ( !empty($list_obj->PhotoUrls) ) {
            foreach($list_obj->PhotoUrls as $photo) {

                // Check if image is already in the media library
                $photo_name = basename($photo);
                $photo_name_array = explode(".",$photo_name);
                $final_photo_name = $photo_name_array[0];
                $image_id = post_exists($final_photo_name, "", "", "attachment");
                if ( !$image_id ) {
                    $image_id = media_sideload_image($photo, $post_id, $final_photo_name,'id');                                   
                }
                add_post_meta( $post_id, 'fave_property_images', $image_id ) || update_post_meta( $post_id, 'fave_property_images', $image_id );                                                                 
            }
        }

        
        /**
         * Assign the property type. Also, check
         * if the term has parent so you can assign the term
         * to the parent
         * 
         */
        $parent_id = "";
        $Type = $list_obj->PropertyType;
        $taxonomy = 'property_type';
        $category_path_array = explode("/",$list_obj->CategoryPath);       
        $PropertyTypeParent = str_replace("/","",$category_path_array[2]); 
        $term_parent = term_exists($PropertyTypeParent,'property_type');
        if ( !$term_parent ) {
            $term_result = wp_insert_term($term_parent, $taxonomy);
            $term = get_term_by('slug', sanitize_title($PropertyTypeParent), $taxonomy);
            $parent_id = $term->term_id;
        }
        $term = term_exists($Type,'property_type');
        if( !$term ) {
            $term_result = wp_insert_term($Type, $taxonomy, $parent_id);
        }
        $term = get_term_by('slug', sanitize_title($Type), $taxonomy);
        $term_id = $term->term_id;
        wp_set_post_terms($post_id, $term_id, $taxonomy);
        
        
        /**
         * Assign the property status
         * 
         */
        $term_name = "";
        $PathArray = explode("/",$list_obj->CategoryPath);
        $EndPath = end($PathArray);
        $taxonomy = 'property_status';
        if ( strpos($EndPath,'Rent') > -1 || strpos($EndPath,'rent') > -1) {
            $term_name = 'For Rent';
        } elseif ( strpos($EndPath,'Sale') > -1 || strpos($EndPath,'sale') > -1 ){
            $term_name = 'For Sale';
        } else {
            // Check if term exists. If not, creat it.            
            $term_name = str_replace("/","",$EndPath);            
        }
        $term = term_exists($term_name, $taxonomy);
        if ( !$term ) {
            $term_result = wp_insert_term($term_name, $taxonomy);
        }
        $Status = $term_name;
        $term_slug = sanitize_title($Status);
        $term = get_term_by('slug', $term_slug, $taxonomy);
        $term_id = $term->term_id;
        wp_set_post_terms($post_id, $term_id, $taxonomy);         
        

        
        /**
         * Assign agency and get agency's agents
         * 
         */
        $AgencyId = !empty($list_obj->Agency->Id) ? $list_obj->Agency->Id : "";
        $AgencyName = !empty($list_obj->Agency->Name) ? $list_obj->Agency->Name : "";     
        $IsRealEstateAgency = !empty($list_obj->Agency->IsRealEstateAgency) ? $list_obj->Agency->IsRealEstateAgency : "";
        $Address = !empty($list_obj->Agency->Address) ? $list_obj->Agency->Address : "";
        $PhoneNumber = !empty($list_obj->Agency->PhoneNumber) ? $list_obj->Agency->PhoneNumber : "";
        $FaxNumber = !empty($list_obj->Agency->FaxNumber) ? $list_obj->Agency->FaxNumber : "";
        $EMail = !empty($list_obj->Agency->EMail) ? $list_obj->Agency->EMail : "";
        $Logo = !empty($list_obj->Agency->Logo) ? $list_obj->Agency->Logo : "";        
        
        $agency_id = post_exists($AgencyName,"","","houzez_agency","publish");
        
        if( !$agency_id ) {

            // Create Agency
            $agency_data = array(
                'post_title' => $AgencyName,
                'post_name' =>  sanitize_title($AgencyName),
                'post_status' => 'publish',
                'post_type' => 'houzez_agency',
                'post_author' => 1
            );
            $agency_id = wp_insert_post($agency_data);

            // Update post author
            //$post_data = get_post($post_id);
            
        }

        // Set agency primary photo
        if ( !empty($Logo) ) {
            $image_id = media_sideload_image($Logo, $agency_id, 'Property Featured Image','id');
            set_post_thumbnail($agency_id, $image_id);            
        }        

        // Agency meta data
        update_post_meta($agency_id,"fave_agency_address",$Address);
        update_post_meta($agency_id,"fave_agency_map_address",$Address);
        update_post_meta($agency_id,"fave_agency_email",$EMail);
        update_post_meta($agency_id,"fave_agency_phone",$PhoneNumber);
        update_post_meta($agency_id,"fave_agency_fax",$FaxNumber);

        // Create agents
        foreach($list_obj->Agency->Agents as $k=>$agent) {

            $agent_data = array(
                'post_title'    => $agent->FullName,
                'post_name'     =>  sanitize_title($agent->FullName),
                'post_status'   => 'publish',
                'post_type'     => 'houzez_agent',
                'post_author'   => 1
            );

            $agent_id = post_exists($agent->FullName,"","","houzez_agent","publish");           

            if ( !$agent_id ) {

                $agent_id = wp_insert_post($agent_data);

                // Set agent primary photo
                if ( !empty($agent->Photo) ) {
                    $image_id = media_sideload_image($agent->Photo, $agent_id, "Agent ".$agent->FullName." featured image",'id');
                    set_post_thumbnail($agent_id, $image_id);                    
                }  
            }            

            $agent_mobile = !empty($agent->MobilePhoneNumber) ? $agent->MobilePhoneNumber : "";
            $agent_email = !empty($agent->EMail) ? $agent->EMail : "";

            // Update agent info if needed
            update_post_meta($agent_id,'fave_agent_agencies',$agency_id);
            update_post_meta($agent_id,'fave_agent_company',$AgencyName);
            update_post_meta($agent_id,'fave_agent_mobile',$agent_mobile);
            update_post_meta($agent_id,'fave_agent_email',$agent_email);
            
            // Set property agents
            add_post_meta($post_id, 'fave_agents', $agent_id) || update_post_meta($post_id, 'fave_agents', $agent_id);
        }

        update_post_meta($post_id,'fave_property_agency',$agency_id);

        
        /**
         * Set address specific data
         * 
         */
        foreach($map_address_array as $prop=>$meta_key) {

            if ( !empty($list_obj->{$prop}) ) {                

                if ( $prop != 'Address' ) {

                    if ( $prop == 'District' ) {                        
                        $taxonomy = 'property_city';                        
                    } elseif ( $prop == 'Suburb' ) {                        
                        $taxonomy = 'property_area'; 
                    } elseif ( $prop == 'Region' ) {
                        $taxonomy = 'property_state'; 
                    }

                    $term_name = $list_obj->{$prop};            
                    $term_slug = sanitize_title($list_obj->{$prop});
                    
                    $term = term_exists($term_name, $taxonomy);
                    if ( !$term ) {
                        $term_result = wp_insert_term($term_name, $taxonomy);
                    }

                    $term = get_term_by('slug', $term_slug, $taxonomy);
                    $term_id = $term->term_id;
                    wp_set_post_terms($post_id, $term_id, $taxonomy);
                    update_post_meta($post_id,$meta_key,$term_name);
                } else {
                    $term = term_exists($term_name, $taxonomy);
                    if ( !$term ) {
                        $term_result = wp_insert_term($term_name, $taxonomy);
                    }
                    update_post_meta($post_id,$meta_key,$list_obj->{$prop});
                }
            } elseif( $prop == 'Country' ) {
                $term_name = 'New Zealand';
                $term_slug = sanitize_title($term_name);
                $taxonomy = 'property_country';

                $term = term_exists($term_name, $taxonomy);
                if ( !$term ) {
                    $term_result = wp_insert_term($term_name, $taxonomy);
                }

                $term = get_term_by('slug', $term_slug, $taxonomy);
                $term_id = $term->term_id;
                wp_set_post_terms($post_id, $term_id, $taxonomy);
                update_post_meta($post_id,$meta_key,$term_name);
            }
        }


        /**
         * Set open homes schedules
         * 
         */
        if ( !empty($list_obj->OpenHomes) )  {
            $property_open_homes = array();
            foreach($list_obj->OpenHomes as $k=>$openhomes){
                $property_open_homes[] = array(
                    'open_homes_start' => $openhomes->Start,
                    'open_homes_end'   => $openhomes->End
                );
            }
            update_post_meta( $post_id, 'property_open_homes', $property_open_homes );   
        }


        
        /**
         * Set attributes as additional features
         * 
         */
        if ( !empty($list_obj->Attributes) ) {
            $additional_features_meta = array();
            foreach($list_obj->Attributes as $attributes){
                foreach($attributes as $k=>$val_obj) {
                    $additional_features_meta[] = array(
                        'fave_additional_feature_title'  => $val_obj->DisplayName,
                        'fave_additional_feature_value'  => $val_obj->DisplayValue
                    );
                }
            }
            update_post_meta( $post_id, 'additional_features', $additional_features_meta );
            update_post_meta( $post_id, 'fave_additional_features_enable', 'enable' );
            
        }

        /**
         * Get Reviews if any
         * 
         */
        //self::dpl_property_listing_review_api( $list_obj->ListingId, $post_id );
        
    }    


    private static function dpl_property_listing_review_api( $ListingId, $post_id ){

        $args = array(
            'consumer_key'      =>  get_option('dpl_property_listing_consumer_key',false) ? get_option('dpl_property_listing_consumer_key',false) : "",
            'access_token'      =>  get_option('dpl_property_listing_final_oauth_token',false) ? get_option('dpl_property_listing_final_oauth_token',false) : "",
            'consumer_secret'   =>  get_option('dpl_property_listing_consumer_secret',false) ? get_option('dpl_property_listing_consumer_secret',false) : "",
            'token_secret'      =>  get_option('dpl_property_listing_final_oauth_token_secret',false) ? get_option('dpl_property_listing_final_oauth_token_secret',false) : "",
            'endpoint_url'      =>  "https://api.".DPL_PROPERTY_LISTING_API_DOMAIN.".co.nz/v1/Listings/". $ListingId."/reviews.json"
        );

        $reviews = self::dpl_property_listing_curl($args);

        //self::dpl_property_print_for_testing($reviews);

        foreach($reviews->List as $review)  {

            $review_title = !empty($review->Nickname) ? $review->Nickname : "Review from member ".$review->MemberId;
            $review_name = sanitize_title($review_title);
            $review_content = $review->ReviewText;

            // Create the review
            $review_post = array(
                'post_title'    =>  $review_title,
                'post_content'  =>  $review_content,
                'post_name'     =>  $review_name,
                'post_status'   => 'publish',
                'post_type'     => 'houzez_reviews',
                'post_author'   => 1
            );

            $review_id = wp_insert_post($review_post);

            if ( $review_id ) {
                $review_post_type = 'property';
                $review_stars = $review->Positive ? 5 : 3;
                $review_by = $review->Member->MemberId;
                $review_to = 1;
                $review_property_id = $post_id;

                update_post_meta( $review_id,'review_post_type', $review_post_type );
                update_post_meta( $review_id,'review_stars', $review_stars );
                update_post_meta( $review_id,'review_by', $review_by );
                update_post_meta( $review_id,'review_to', $review_to );
                update_post_meta( $review_id,'review_property_id', $review_property_id );
            }
        }
    }

    private static function dpl_property_print_for_testing($data){

        echo "<pre>";
        print_r($data);
        echo "</pre>";

    }

} // END: Dpl_Property_Listing_Api