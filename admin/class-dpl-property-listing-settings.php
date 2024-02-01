<?php

/**
 * The admin settings functionality of the plugin.
 *
 * @link       https://digitalpie.co.nz/custom-development/
 * @since      1.0.0
 *
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/admin
 */

/**
 * The admin settings functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dpl_Property_Listing
 * @subpackage Dpl_Property_Listing/admin
 * @author     Digital Pie <charlie@digitalpie.co.nz>
 */
class Dpl_Property_Listing_Settings {


    /**
     * Temporary Token Endpoint URL
     * 
     * @since   1.0.0
     * @access  private
     * @var     string
     */
    private static $_temp_token_endpoint_url;

    /**
     * API Callback URL
     * 
     * @since   1.0.0
     * @access  private
     * @var     string
     */
    private static $_callback_url;

    /**
     * Final Token Endpoint URL
     * 
     * @since   1.0.0
     * @access  private
     * @var     string
     */
    private static $_final_token_endpoint_url;


    /**
     * Class constructor
     * 
     */
    public function __construct(){

        // Loads the settings page
        add_action('admin_menu',array($this, 'dpl_property_listing_settings_page'));

        // Capture the oauth verifier when present in the URL
        add_action('init',array($this,'dpl_property_listing_settings_get_oauth_verifier'));

        // Get the final acces token and token secret
        add_action('init',array($this,'dpl_property_listing_setting_get_final_token_and_secret'));

        self::$_callback_url                =   DPL_PROPERTY_LISTING_CALLBACK_URL;
        self::$_temp_token_endpoint_url     =   "https://api.".DPL_PROPERTY_LISTING_API_DOMAIN.".co.nz/Oauth/RequestToken?scope=MyTradeMeRead";        
        self::$_final_token_endpoint_url    =   "https://secure.".DPL_PROPERTY_LISTING_API_DOMAIN.".co.nz/Oauth/AccessToken";
    }

    /**
     * DPL Property Listing Settings Page
     * Adds DPL Property Listing Admin menu and page
     * 
     * @since   1.0.0
     * @access  public
     */
	public function dpl_property_listing_settings_page(){

        add_menu_page(
            'Dpl Property Listing Settings',
            'Dpl Property Listing',
            'manage_options',
            'dpl-property-listing-settings',
            array($this, 'dpl_property_listing_settings_render_admin_page')
        );
    }

    /**
     * DPL Property Listing Render Admin Page
     * The actual DPL Property Listing
     * Admin page
     * 
     * @since   1.0.0
     * @access  public
     */
    public static function dpl_property_listing_settings_render_admin_page(){        

        $dpl_property_listing_consumer_key = !empty($_POST['dpl_property_listing_consumer_key']) ? $_POST['dpl_property_listing_consumer_key'] : "";
        $dpl_property_listing_consumer_secret = !empty($_POST['dpl_property_listing_consumer_secret']) ? $_POST['dpl_property_listing_consumer_secret'] : "";
        
        if ( isset( $_POST['submit'] ) ) {

            if ( !empty($dpl_property_listing_consumer_key) && !empty($dpl_property_listing_consumer_secret) ) {

                // Set options
                update_option('dpl_property_listing_consumer_key',$dpl_property_listing_consumer_key);
                update_option('dpl_property_listing_consumer_secret',$dpl_property_listing_consumer_secret);

                // Generate Temp Token
                self::dpl_property_listing_settings_generate_temp_token(
                    $dpl_property_listing_consumer_key,
                    $dpl_property_listing_consumer_secret
                );

                // Redirect to grant app
                $oauth_token = get_option('dpl_property_listing_oauth_token',false);
                self::dpl_property_listing_settings_grant_app($oauth_token);
                
            }
        }

        ?>
            <div class="wrap dpl-property-listing-settings-wrapper">
                <h2><?php echo __('Dpl Property Listing Settings');?></h2>
                <form method="post">
                    <p><span><?php echo __('Consumer Key:');?></span><input type="text" class="dpl_property_listing_input_settings" name="dpl_property_listing_consumer_key" placeholder="Enter your consumer key" value="<?php echo get_option('dpl_property_listing_consumer_key',false) ? get_option('dpl_property_listing_consumer_key',false) : "";?>"/></p>
                    <p><span><?php echo __('Consumer Secret:');?></span><input type="password" class="dpl_property_listing_input_settings" name="dpl_property_listing_consumer_secret" placeholder="Enter your consumer secret" value="<?php echo get_option('dpl_property_listing_consumer_secret',false) ? get_option('dpl_property_listing_consumer_secret',false) : "";?>"/></p>
                    <p><input type="submit" name="submit" class="button button-primary" value="Save & Generate Token" /></p>
                </form>
            </div>
        <?php
    }

    /**
     * Generate Temporary Token
     * This token is needed to get the
     * final token later
     * 
     * @since   1.0.0
     * @access  private
     * @param   string  $dpl_property_listing_consumer_key
     * @param   string  $dpl_property_listing_consumer_secret
     */
    private static function dpl_property_listing_settings_generate_temp_token($dpl_property_listing_consumer_key,$dpl_property_listing_consumer_secret){

        $oauth_consumer_key = $dpl_property_listing_consumer_key;
        $oauth_signature = $dpl_property_listing_consumer_secret;
        $oauth_signature_method = 'PLAINTEXT';
        $oauth_callback = DPL_PROPERTY_LISTING_CALLBACK_URL;

        // API endpoint
        $url = self::$_temp_token_endpoint_url;

        // Create OAuth1 authentication headers
        $oauth_options = [
            'oauth_consumer_key'        => $oauth_consumer_key,
            'oauth_signature_method'    => $oauth_signature_method,
            'oauth_callback'            => $oauth_callback,
            'oauth_signature'           => $oauth_signature.'&'
        ];

        $oauth = base64_encode(http_build_query($oauth_options, '', '&', PHP_QUERY_RFC3986));
        $authorization_header = 'Authorization: OAuth ' . $oauth;

        // Send CURL request
        $response = self::dpl_property_listing_api_keys_curl($oauth_options);

        // Explode to make the response array
        $response_array             = explode("&",$response);
        $oauth_token                = str_replace('oauth_token=','',$response_array[0]);
        $oauth_token_secret         = str_replace('oauth_token_secret=','',$response_array[1]);
        $oauth_callback_confirmed   = str_replace('oauth_callback_confirmed=','',$response_array[2]);

        // Save oauth token details
        update_option('dpl_property_listing_oauth_token',$oauth_token);
        update_option('dpl_property_listing_oauth_token_secret',$oauth_token_secret);
        update_option('dpl_property_listing_oauth_callback_confirmed',$oauth_callback_confirmed);
    }

    /**
     * DPL Property Listing Settings Grant APP Redirection
     * User will get redirected
     * to the APP approval page
     * 
     * @since   1.0.0
     * @access  private
     * @param   string  $oauth_token
     */
    private static function dpl_property_listing_settings_grant_app($oauth_token){
        wp_redirect("https://".DPL_PROPERTY_LISTING_API_DOMAIN.".co.nz/Oauth/Authorize?oauth_token=".$oauth_token);
        exit();
    }

    /**
     * DPL Property Listing Settings Capture the OAuth Verifier
     * 
     * @since   1.0.0
     * @access  public
     */
    public static function dpl_property_listing_settings_get_oauth_verifier(){
        if ( !isset($_GET['oauth_verifier']) ) {
            return;
        }
        update_option('dpl_property_listing_oauth_verifier', $_GET['oauth_verifier']);
    }

    /**
     * Get the final token and secret
     * 
     * @since   1.0.0
     * @access  public
     */
    public static function dpl_property_listing_setting_get_final_token_and_secret(){

        if ( !isset($_GET['oauth_verifier']) ) {
            return;
        }

        $oauth_consumer_key     = get_option('dpl_property_listing_consumer_key',false);
        $oauth_token            = get_option('dpl_property_listing_oauth_token',false);
        $oauth_verifier         = get_option('dpl_property_listing_oauth_verifier',false);
        $oauth_signature_method = 'PLAINTEXT';
        $oauth_signature        = get_option('dpl_property_listing_consumer_secret',false)."&".get_option('dpl_property_listing_oauth_token_secret',false);
        
        // API endpoint
        $url = self::$_final_token_endpoint_url;

        // Create OAuth1 authentication headers
        $oauth_options = [
            'oauth_consumer_key'        => $oauth_consumer_key,
            'oauth_token'               => $oauth_token,
            'oauth_verifier'            => $oauth_verifier,
            'oauth_signature_method'    => $oauth_signature_method,
            'oauth_signature'           => $oauth_signature
        ];

        // Send CURL request
        $response = self::dpl_property_listing_api_keys_curl($oauth_options);

        // Explode to make the response array
        $response_array             = explode("&",$response);
        $final_oauth_token          = str_replace('oauth_token=','',$response_array[0]);
        $final_oauth_token_secret   = str_replace('oauth_token_secret=','',$response_array[1]);

        update_option('dpl_property_listing_final_oauth_token',$final_oauth_token);
        update_option('dpl_property_listing_final_oauth_token_secret',$final_oauth_token_secret);
        
    }

    /**
     * Get tokens by curl
     * 
     * @since   1.0.0
     * @access  private
     * @param   string      $oauth_options
     * 
     * @return  object      $response
     */
    private static function dpl_property_listing_api_keys_curl($oauth_options){

        $oauth = base64_encode(http_build_query($oauth_options, '', '&', PHP_QUERY_RFC3986));
        $authorization_header = 'Authorization: OAuth ' . $oauth;

        // Prepare CURL options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($oauth_options));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            $authorization_header,
        ]);

        // Execute cURL session
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        return $response;
    }
} // END: Dpl_Property_Listing_Settings
