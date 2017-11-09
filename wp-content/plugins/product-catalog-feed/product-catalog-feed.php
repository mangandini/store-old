<?php
/*
Plugin Name: Product Catalog Feed by PixelYourSite
Description: WooCommerce Products Feed for Facebook Product Catalog. You can create XML feeds for Facebook Dynamic Product Ads.
Plugin URI: http://www.pixelyoursite.com/facebook-product-catalog
Author: PixelYourSite
Author URI: http://www.pixelyoursite.com
Version: 1.0.4
*/ 
/* Following are used for updating plugin */
//Plugin Version
define( 'WPWOOF_VERSION', '1.0.4');
//Plugin Update URL
define( 'WPWOOF_SL_STORE_URL', 'http://www.pixelyoursite.com' );
//Plugin Name
define( 'WPWOOF_SL_ITEM_NAME', 'Product Catalog Feed' );

//Plugin Base
define( 'WPWOOF_BASE', plugin_basename( __FILE__ ) );
//Plugin PAtH
define( 'WPWOOF_PATH', plugin_dir_path( __FILE__ ) );
//Plugin URL
define( 'WPWOOF_URL', plugin_dir_url( __FILE__ ) );
//Plugin assets URL
define( 'WPWOOF_ASSETS_URL', WPWOOF_URL . 'assets/' );
//Plugin
define( 'WPWOOF_PLUGIN', 'wp-woocommerce-feed');

require_once('inc/common.php');
require_once('inc/helpers.php');
include_once('inc/generate-feed.php');
require_once('inc/admin.php');
require_once('inc/feed-list-table.php');
class wpwoof_product_catalog {
    static $interval = '86400';

    function __construct() {
        global $xml_has_some_error;
        $xml_has_some_error = false;
        register_activation_hook(__FILE__, array(__CLASS__, 'activate'));
        register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivate'));

        add_action('init', array(__CLASS__, 'init'));
        add_action('admin_init', array(__CLASS__, 'admin_init'));
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));

        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
        add_action('wpwoof_feed_update', array(__CLASS__, 'wpwoof_feed_update'));

        self::$interval = get_option('wpwoof_schedule', '86400');

        add_filter( 'http_request_host_is_external', array(__CLASS__, 'http_request_host_is_external'), 10, 3 );
    }

    static function init() {
        $is_xml = ( isset($_GET['wpwoofeedxmldownload']) && wp_verify_nonce( $_GET['wpwoofeedxmldownload'], 'wpwoof_download_nonce' ) );
        $is_csv = ( isset($_GET['wpwoofeedcsvdownload']) && wp_verify_nonce( $_GET['wpwoofeedcsvdownload'], 'wpwoof_download_nonce' ) );
        if( $is_xml || $is_csv ){
            $option_id = $_GET['feed'];
            $data = wpwoof_get_feed($option_id);
            $data = unserialize($data);
            $feedname = $data['feed_name'];
            $upload_dir = wpwoof_feed_dir($feedname, ($is_xml ? 'xml' : 'csv'));
            $file = $upload_dir['path'];
            $path = $upload_dir['path'];
            $fileurl = $upload_dir['url'];
            $file_name = $upload_dir['file'];
            if( $is_csv ) {
                $dir_path = str_replace( $file_name, '', $path );
                $create_csv = false;
                if(wpwoof_checkDir($dir_path)) {
                    $fp = fopen($path, "w");
                    $create_csv = wpwoofeed_generate_feed($data, 'csv', $fp);
                    fclose($fp);
                    if( ! empty($data['feed_ftp_csv']) ) {
                        wpwoofeed_send_via_ftp($data, $file, $file_name);
                    }
                }
                if( ! $create_csv )
                    return;
            }
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                if( $is_xml ) {
                    header('Content-Type: text/xml');
                } else {
                    header('Content-Type: application/octet-stream');
                }
                header('Content-Disposition: attachment; filename="'.$file_name.'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
            } else {
                wp_die( 'Error: File not found', ( $is_xml ? 'XML Download' : 'CSV Download' ) );
                exit;
            }
        }
    }

    static function feed_dir($feedname, $file_type='xml'){
        $feedname = str_replace(' ', '-', $feedname);
        $feedname = strtolower($feedname);
        $upload_dir = wp_upload_dir();
        $base = $upload_dir['basedir'];
        $baseurl = $upload_dir['baseurl'];
        $feedService = 'facebook';
        $path = "{$base}/wpwoof-feed/{$feedService}/{$file_type}";
        $baseurl = $baseurl . "/wpwoof-feed/{$feedService}/{$file_type}";
        $file = "{$path}/{$feedname}.{$file_type}";
        $fileurl = "{$baseurl}/{$feedname}.{$file_type}";

        return array('path' => $file, 'url'=>$fileurl, 'file' => $feedname . '.'.$file_type);        
    }

    static function admin_init() {
        global $wpwoof_values, $wpwoof_add_button, $wpwoof_add_tab, $wpwoof_message, $wpwoofeed_oldname;
        $wpwoof_values      = array();
        $wpwoof_add_button  = 'Generate the Feed';
        $wpwoof_add_tab     = 'Add New Feed';
        $wpwoof_message     = '';
        $wpwoofeed_oldname  = '';

        if ( ! isset($_REQUEST['page']) || $_REQUEST['page'] != 'wpwoof-settings' ) {
            return;
        }
        if( isset($_POST['wpwoof-addfeed-submit']) ) {
            $values = $_POST;
            unset($values['wpwoof-addfeed-submit']);

            $values['added_time'] = time();
            $feed_name = sanitize_text_field($values['feed_name']);
            $values['field_mapping'] = wpwoof_feed_option_fulled($values['field_mapping']);
            if( isset($_POST['edit_feed']) && !empty($_POST['edit_feed']) ){

                $url = wpwoof_create_feed($feed_name, $values);
                if( isset($_POST['old_feed_name']) && !empty($_POST['old_feed_name'])) {

                    $oldfile = trim($_POST['old_feed_name']);
                    $oldfile = strtolower($oldfile);

                    $newfile = trim($_POST['feed_name']);
                    $newfile = strtolower($newfile);
                    
                    if( $newfile != $oldfile ) {
                        wpwoof_delete_feed_file($_POST['edit_feed']);
                    }
                }
                $values['url'] = $url;
                $updated = wpwoof_update_feed(serialize($values), $_POST['edit_feed']);
                update_option('wpwoof_message', 'Feed Updated Successully.');
                $wpwoof_message = 'success';
            } else {
                $url = wpwoof_create_feed($feed_name, $values);
                $values['url'] = $url;
                update_option('wpwoof_feedlist_'.$feed_name, $values);
            }
            /* Reload the current page */
            wpwoof_refresh( $wpwoof_message );
        } else if ( isset( $_REQUEST['delete'] ) && !empty( $_REQUEST['delete'] ) ) {
            $id = $_REQUEST['delete'];
            $deleted = wpwoof_delete_feed($id);

            if( $deleted ) { 
                wp_cache_flush();
                update_option('wpwoof_message', 'Feed Deleted Successully.');
                $wpwoof_message = 'success';
            } else {
                update_option('wpwoof_message', 'Failed To Delete Feed.');
                $wpwoof_message = 'error';
            }
            /* Reload the current page */
            wpwoof_refresh( $wpwoof_message );
           
        } else if ( isset($_REQUEST['edit']) && !empty($_REQUEST['edit']) ) {
            $option_id = $_REQUEST['edit'];
            $feed = wpwoof_get_feed($option_id);
            $wpwoof_values = unserialize($feed);
            $wpwoofeed_oldname = isset($wpwoof_values['feed_name']) ? $wpwoof_values['feed_name'] : '';
            $wpwoof_add_button = 'Update the Feed';
            $wpwoof_add_tab = 'Edit Feed : ' . $wpwoof_values['feed_name'];
        } else if ( isset($_REQUEST['update']) && !empty($_REQUEST['update']) ) {
            $option_id = $_REQUEST['update'];
            $feed = wpwoof_get_feed($option_id);
            $wpwoof_values = unserialize($feed);
            $feed_name = sanitize_text_field($wpwoof_values['feed_name']);
            $wpwoof_values['added_time'] = time();
            $url = wpwoof_create_feed($feed_name, $wpwoof_values);
            $wpwoof_values['url'] = $url;
            $updated = wpwoof_update_feed(serialize($wpwoof_values), $option_id);

            update_option('wpwoof_message', 'Feed Regenerated Successully.');
            $wpwoof_message = 'success';

            /* Reload the current page */
            wpwoof_refresh( $wpwoof_message );
        }
    }

    static function admin_menu() {
        add_menu_page( 'Product Catalog', 'Product Catalog', 'manage_options', 'wpwoof-settings', array(__CLASS__, 'menu_page_callback'), WPWOOF_URL . '/assets/img/favicon.png');
    }

    static function menu_page_callback() {
        if( isset($_POST['wpwoof_schedule_submit']) ){
            $option = $_POST['wpwoof_schedule'];
            update_option('wpwoof_schedule', $option);
            $schedule = array(
                '3600'  => 'hourly',
                '43200' => 'twicedaily',
                '86400' => 'daily',
            );
            wp_clear_scheduled_hook('wpwoof_feed_update');
            if( ! empty($schedule[$option]) ) {
                wp_schedule_event(time(), $schedule[$option], 'wpwoof_feed_update');
            }
        }
        require_once('view/admin/settings.php');
    }

    static function admin_enqueue_scripts() {
        if(isset($_GET['page']) && $_GET['page'] == 'wpwoof-settings' ){
            //Admin Style
            wp_enqueue_style( WPWOOF_PLUGIN.'-style', WPWOOF_ASSETS_URL . 'css/admin.css', array(), WPWOOF_VERSION, false );
            //Admin Javascript
            wp_enqueue_script( WPWOOF_PLUGIN.'-script', WPWOOF_ASSETS_URL . 'js/admin.js', array('jquery'), WPWOOF_VERSION, false );
            wp_enqueue_script( WPWOOF_PLUGIN.'-optionTree', WPWOOF_ASSETS_URL . 'js/jquery.optionTree.js', array('jquery'), WPWOOF_VERSION, false );

            wp_localize_script( WPWOOF_PLUGIN.'-script', 'WPWOOF', array( 'ajaxurl'=> admin_url('admin-ajax.php'), 'loading' => admin_url('images/loading.gif') ) );
        }
    }

    static function cron_schedules($schedules) {
        $interval = self::$interval;
        $schedules['wpwoof_feed_cron'] = array(
            'display' => __( $interval . 'seconds interval', 'wp-woocommerce-feed'),
            'interval' => $interval,
        );
        return $schedules;
    }

    static function wpwoof_feed_update() {
        global $wpdb;
        $var = "wpwoof_feedlist_";
        $sql = "SELECT * FROM $wpdb->options WHERE option_name LIKE '%$var%'";    
        $result = $wpdb->get_results($sql, 'ARRAY_A');
        
        foreach ($result as $key => $value) {

            $option_id = $value['option_id'];
            $feed = wpwoof_get_feed($option_id);
            $wpwoof_values = unserialize($feed);
            $feed_name = sanitize_text_field($wpwoof_values['feed_name']);
            $url = wpwoof_create_feed($feed_name, $wpwoof_values);
            $wpwoof_values['url'] = $url;
            $wpwoof_values['added_time'] = time();
            $updated = wpwoof_update_feed(serialize($wpwoof_values), $option_id);

        }
    }

    static function activate() {
        wp_schedule_event(time(), 'twicedaily', 'wpwoof_feed_update');

        $path_upload 	= wp_upload_dir();
        $path_upload 	= $path_upload['basedir'];
        $pathes = array(
            array('wpwoof-feed', 'facebook', 'xml'),
            array('wpwoof-feed', 'facebook', 'csv'),
        );
        foreach($pathes as $path) {
            $path_folder = $path_upload;
            foreach($path as $folder) {
                $path_created = false;
                if( is_writable($path_folder) ) {
                    $path_folder = $path_folder.'/'.$folder;
                    $path_created = is_dir($path_folder);
                    if( ! $path_created ) {
                        $path_created = mkdir($path_folder, 0755);
                    }
                }
                if( ! is_writable($path_folder) || ! $path_created ) {
                    self::deactivate_generate_error('Cannot create folders in uploads folder', true, true);
                    die;
                }
            }
        }
    }

    static function deactivate() {
        wp_clear_scheduled_hook('wpwoof_feed_update');
    }
    
    static function deactivate_generate_error($error_message, $deactivate = true, $echo_error = false) {
        if( $deactivate ) {
            deactivate_plugins(array(__FILE__));
        }
        $message = "<div class='notice notice-error is-dismissible'>
            <p>".$error_message."</p>
        </div>";
        if( $echo_error ) {
            echo $message;
        } else {
            add_action( 'admin_notices', create_function( '', 'echo "'.$message.'";' ), 9999 );
        }
    }

    static function http_request_host_is_external( $allow, $host, $url ) {
        if ( $host == 'woocommerce-5661-12828-90857.cloudwaysapps.com' )
            $allow = true;
        return $allow;
    }
}
new wpwoof_product_catalog;