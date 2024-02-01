<?php

/**
 * Class responsible for syncing data from API to the database.
 *
 * @link       https://digitalpie.co.nz/custom-development/
 * @since      1.0.0
 *
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/admin
 */

/**
 * Class responsible for syncing data from API to the database.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/admin
 * @author     Digital Pie <charlie@digitalpie.co.nz>
 */
class Dpl_Property_Listing_Sync {

    /**
     * Class constructor
     * 
     */
    public function __construct(){

        // Loads the sync page
        add_action('admin_menu',array($this, 'dpl_property_listing_register_sync_submenu'));
    }

    /**
     * DPL Property Listing Sync Property Listings
     * Adds DPL Property Listing Sync menu and page
     * 
     * @since   1.0.0
     * @access  public
     */
	public function dpl_property_listing_register_sync_submenu(){

        add_submenu_page( 
            'dpl-property-listing-settings', 
            'Sync Property Listings', 
            'Sync Property Listings', 
            'manage_options', 
            'sync-property-listings', 
            array($this,'dpl_sync_property_listing_render_page') 
        );
    }

    /**
     * Displays the properties
     * from the TradeMe API in a table.
     * The user will then be able to select which properties
     * to saved into the database.
     * 
     * @since   1.0.0
     * @access  public
     */
    public static function dpl_sync_property_listing_render_page(){  
        
        /**
         * Instantiate Dpl_Property_Listing_Api
         * 
         */
        $plugin_api = new Dpl_Property_Listing_Api(); 

        /**
         * Prepare the parameters for filtering
         */
        $params = array();
        $params['resource'] = !isset($_GET['resource']) ? "" : $_GET['resource'];
        $params['search_string'] = !isset($_GET['search_string']) ? "" : $_GET['search_string'];
        $params['page'] = isset($_GET['page_number']) ? $_GET['page_number'] : 1;
		
        /**
         * Pass the selected listing type
         * and put it to the URL for filtering
         */
        if ( isset( $_POST['search'] ) ) {

            $admin_url_params = http_build_query(
                array(
                    'search_string' => "", // put your string here to filter listings
                    'resource'      => $_POST['listing_type'] // Residential, Rental, NewHomes
                )
            );

            wp_redirect(admin_url("admin.php?page=sync-property-listings&".$admin_url_params));
            exit();
        }

        /**
         * Prepare and create the
         * selected listings
         */
        if ( isset( $_POST['sync_to_db_btn'] ) ) {

            if ( !empty( $_POST['listing_ids'] ) ) {
                
                $listing_ids_array = explode(",",$_POST['listing_ids']);
                
                $response = $plugin_api->dpl_property_listing_search_property_get( $params );

                foreach($response->List as $list) {
                    if ( in_array($list->ListingId,$listing_ids_array) ) {
                       $plugin_api->dpl_property_listing_create_listing($list->ListingId, $list);
                    }
                }
            }
        }

        

        /**
         * Prepare the data to display
         */        
        $response = $plugin_api->dpl_property_listing_search_property_get( $params );
        $total_count = isset($response->TotalCount) && round($response->TotalCount) > 0 ? round($response->TotalCount)  : 0;
        $pages = round($total_count/50);
        

        /**
         * Pagination
         */
        $items_per_page = 50;
        $total_pages = ceil($total_count / $items_per_page);        
        $currentpage = $params['page'];
        $offset = ($currentpage - 1) * $items_per_page;

        $pagination_text = __("Displaying items " . ($offset + 1) . " to " . min($offset + $items_per_page, $total_count) . " of $total_count<br>",'dpl-property-listing');
        

        ?>
        <div class="wrap dpl-property-listing-settings-wrapper">
                <h2><?php echo __('Sync Property Listings');?></h2>
                <form method="POST">
                    <select name="listing_type">
                        <option value="Residential" <?php echo (isset($_GET['resource']) && $_GET['resource'] == "Residential") ? "selected" : ""?>><?php echo __('For Sale','dpl-property-listing');  ?></option>
                        <option value="Rental" <?php echo (isset($_GET['resource']) && $_GET['resource'] == "Rental") ? "selected" : ""?>><?php echo __('For Rent','dpl-property-listing');  ?></option>
                        <option value="NewHomes" <?php echo (isset($_GET['resource']) && $_GET['resource'] == "NewHomes") ? "selected" : ""?>><?php echo __('New Homes','dpl-property-listing');  ?></option>
                    </select>
                    <input type="submit" name="search" class="button button-primary" value="Pull Data from Trademe" />
                </form>
                <div class="dpl-property-listing-pagination">
                
                <?php

                // Previous and Next buttons

                if ( !empty($_GET['resource']) ){                    
                    echo $pagination_text;
                    echo self::dpl_property_listing_pagination($total_count, $items_per_page, $currentpage, 5);
                }                

                ?>
                </div>
                <div class="dpl-custom-property-result-table">
                    <div class="dpl-custom-property-before-table">
                        <h3><strong><?php echo __('Total Count : ');?>&nbsp;</strong><?php echo $total_count;?>&nbsp;<?php echo __('Properties Found');?></strong></h3>
                        
                    </div>
                    <div class="dpl-custom-property-before-table sync-btn-loading-msg">
                        <div class="dpl-custom-property-loading-sync">
                            <img src="<?php echo DPL_PROPERTY_LISTING_URL.'admin/img/loading-image.gif';?>">
                            <?php echo __('Please wait while the sytem is syncing the data to the database.','dpl-property-listing');  ?>                
                            
                        </div>
                        <form method="POST" class="submit_listing_ids_form">
                            <input type="hidden" name="listing_ids" class="dpl-listing-ids">
                            <input type="submit" class="button button-primary sync-to-db-btn" name="sync_to_db_btn" value="Sync Selected Items" <?php if(!$total_count): ?>disabled<?php endif;?>>
                        </form>
                    </div>
                </div>
                <?php          
                    self::dpl_sync_property_listing_table($response);
                ?>
                <br>                
                <div class="dpl-property-listing-pagination">
                
                <?php

                // Previous and Next buttons

                if ( !empty($_GET['resource']) ){                    
                    echo $pagination_text;
                    echo self::dpl_property_listing_pagination($total_count, $items_per_page, $currentpage, 5);
                }                

                ?>
                </div>
            </div>
        <?php
    }

    /**
     * Displays the API results
     * in a table.
     */
    private static function dpl_sync_property_listing_table($response) {

        echo "<table class=\"dpl-property-listing-property-table wp-list-table widefat fixed striped table-view-list\">";
        echo "<thead>
                <tr>
                    <td id=\"cb\" class=\"manage-column column-cb check-column\"><input type=\"checkbox\" id=\"cb-select-all-1\"></td>
                    <th style=\"max-width:100%;width:100px;\">Listing ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Details</th>
                    <th>Database Status</th>
                </tr>
            </thead>";
        echo "<tbody>";

        if ( isset($response->List) ) {

            foreach($response->List as $list) {

                $ListingId = isset($list->ListingId) ? $list->ListingId : "n/a";
                $PictureHref = isset($list->PictureHref) ? "<img src=\"".$list->PictureHref."\" class=\"property-image\"/>" : "";
                $Title = isset($list->Title) ? $list->Title : "n/a";
                $PropertyType = isset($list->PropertyType) ? $list->PropertyType : "n/a";
                $Address = isset($list->Address) ? $list->Address : "n/a";
                $Region = isset($list->Region) ? $list->Region : "n/a";
                $Suburb = isset($list->Suburb) ? $list->Suburb : "n/a";
                $PriceDisplay = isset($list->PriceDisplay) ? $list->PriceDisplay : "n/a";
                $AgencyName = isset($list->Agency->Name) ? $list->Agency->Name : "n/a";
                $Bathrooms = isset($list->Bathrooms) ? $list->Bathrooms : "n/a";
                $Bedrooms = isset($list->Bedrooms) ? $list->Bedrooms : "n/a";
                $LandArea = isset($list->LandArea) ? $list->LandArea : "n/a";
                $Area = isset($list->Area) ? $list->Area : "n/a";
                $Parking = isset($list->Parking) ? $list->Parking : "n/a";
                $Amenities = isset($list->Amenities) ? $list->Amenities : "n/a";

                echo "<tr>
                        <th scope=\"row\" class=\"check-column\"><input id=\"cb-select-".$ListingId."\" class=\"dpl-property-checkbox\" type=\"checkbox\" name=\"property[]\" value=".$ListingId.">                        
                        </th>
                        <td>".$ListingId."</td>
                        <td><div class=\"dpl-img-wrapper\">".$PictureHref."</div></td>
                        <td>".$Title."</td>
                        <td class=\"dpl-table-details\">
                            <p>Property Type: ".$PropertyType."</p>                            
                            <p>Price: ".$PriceDisplay."</p>
                            <p>Agency: ".$AgencyName."</p>
                            <p>Address: ".$Address."</p>
                            <p>Region: ".$Region."</p>
                            <p>Bathrooms: ".$Bathrooms."</p>
                            <p>Bedrooms: ".$Bedrooms."</p>
                            <p>Area: ".$Area."</p>
                            <p>Land Area: ".$LandArea."</p>
                            <p>Parking: ".$Parking."</p>
                            <p>Amenities: ".$Amenities."</p>
                        </td>
                        <td>".self::dpl_property_list_db_status($ListingId,$list)."</td>
                </tr>";
            }
        }
        
        echo "</tbody>";
        echo "</table>";
    }

    /**
     * Displays the status
     * of this listing in the database
     */
    private static function dpl_property_list_db_status($ListingId,$list){

        global $wpdb;

        $db_post_table = $wpdb->prefix."posts";
        $db_postmeta_table = $wpdb->prefix."postmeta"; 
        $meta_key = 'ListingId';

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
            return __("<p class=\"dpl-updated-in-db\">Found in Database</p>",'dpl-property-listing');
        } else {
            return __("<p class=\"dpl-not-in-db\">Not in Database</p>",'dpl-property-listing');
        }

    }

    /**
     * Pagination
     * 
     * @since   1.0.0
     * @access  private
     * @param   int     $totalItems
     * @param   int     $itemsPerPage
     * @param   int     $currentPage
     * @param   int     $totalPagesToShow
     * 
     * @return  string
     */

    private static function dpl_property_listing_pagination($totalItems, $itemsPerPage, $currentPage, $totalPagesToShow) {

        $output = '';

        // URL
        $url = admin_url("admin.php?page=sync-property-listings&search_string&resource=".$_GET['resource']."&page_number=");
    
        // Calculate total number of pages
        $totalPages = ceil($totalItems / $itemsPerPage);
    
        // Ensure current page is within valid range
        $currentPage = max(1, min($totalPages, $currentPage));
    
        // Calculate starting and ending page numbers to display
        $startPage = max(1, min($totalPages - $totalPagesToShow + 1, $currentPage - floor($totalPagesToShow / 2)));
        $endPage = min($totalPages, $startPage + $totalPagesToShow - 1);
    
        // Output "Previous" link if applicable
        if ($currentPage > 1) {
            $output .= '<a href="'.$url . ($currentPage - 1) . '" class="dpl-nav-link-item">< Previous</a> ';
        }
    
        // Output page numbers
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $currentPage) {
                $output .= '<strong>' . $i . '</strong> ';
            } else {
                $output .= '<a href="'.$url .  $i . '" class="dpl-nav-link-item">' . $i . '</a> ';
            }
        }
    
        // Output "Next" link if applicable
        if ($currentPage < $totalPages) {
            $output .= '<a href="' .$url. ($currentPage + 1) . '" class="dpl-nav-link-item">Next ></a>';
        }
    
        return "<div class=\"dpl-property-listing-pagination-links\">".$output."</div>";
    }
    
}// END: Dpl_Property_Listing_Sync
