<?php
require_once("common.php");
global $woocommerce_wpwoof_common, $FEEDCOUNT;
$FEEDCOUNT = $woocommerce_wpwoof_common->get_feed_count();
define('FEEDCOUNT',$FEEDCOUNT);

function wpwoof_delete_feed( $id ) {
    global $wpdb;
    wpwoof_delete_feed_file($id);
    return $wpdb->delete(
        "{$wpdb->prefix}options",
        array('option_id' => $id),
        array('%d')
    );
}

function wpwoof_update_feed( $option_value, $option_id ) {
    global $wpdb;
    //wpwoof_delete_feed_file($id);
    $table = "{$wpdb->prefix}options";
    $data = array('option_value'=>$option_value);
    $where = array('option_id'=>$option_id);
    return $wpdb->update( $table, $data, $where);
}


function wpwoof_get_feeds( $search = "" ) {

    global $wpdb;
    $option_name="wpwoof_feedlist_";
    if( $search != '' )
    	$option_name = $search;

    $query = $wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name LIKE %s;", "%".$option_name."%");
    $result = $wpdb->get_results($query, 'ARRAY_A');

    return $result;
}

function wpwoof_get_feed( $option_id ) {
    global $wpdb;

    $query = $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_id='%s';", $option_id);
    $result = $wpdb->get_var($query);

    return $result;
}

function wpwoof_feed_dir( $feedname, $file_type = 'xml', $feedService = 'facebook' ) {
    $feedname = str_replace(' ', '-', $feedname);
    $feedname = strtolower($feedname);
    $upload_dir = wp_upload_dir();
    $base = $upload_dir['basedir'];
    $baseurl = $upload_dir['baseurl'];

    $path = $base . "/wpwoof-feed/" . $feedService . "/" . $file_type;
    $baseurl = $baseurl . "/wpwoof-feed/" . $feedService . "/" . $file_type;
    $file = $path . "/" . $feedname . "." . $file_type;
    $fileurl = $baseurl . "/" . $feedname . "." . $file_type;
    
    return array('path' => $file, 'url'=>$fileurl, 'file' => $feedname . '.'.$file_type);        
}

function wpwoof_create_feed($feedname, $data){
    $upload_dir = wpwoof_feed_dir($feedname);
    $path = $upload_dir['path'];

    $file = $upload_dir['path'];
    $fileurl = $upload_dir['url'];
    $file_name = $upload_dir['file'];

    $dir_path = str_replace( $file_name, '', $path );
    if (wpwoof_checkDir($dir_path)) {
        $fp = fopen($path, "w");
        $string = wpwoofeed_generate_feed($data, 'xml', $fp);
        fclose($fp);
    }

    return $fileurl;
}

function wpwoof_checkDir($path){
    if (!file_exists($path)) {
       return wp_mkdir_p($path);
    }
    return true;
}

function wpwoof_delete_feed_file($id){
    $option_id = $id;
    $feed = wpwoof_get_feed($option_id);
    $wpwoof_values = unserialize($feed);
    $feed_name = sanitize_text_field($wpwoof_values['feed_name']);
    $upload_dir = wpwoof_feed_dir($feed_name);
    $file = $upload_dir['path'];
    $fileurl = $upload_dir['url'];

    if( file_exists($file))
        unlink($file);
}

function wpwoof_refresh($message = '') {
    $settings_page = $_SERVER['REQUEST_URI'];
    if ( strpos( $settings_page, '&' ) !== false ) {
        $settings_page = substr( $settings_page, 0, strpos( $settings_page, '&' ) );
    }
    if ( ! empty( $message ) ) {
        $settings_page .= '&show_msg=true&wpwoof_message=' . $message;
    }
    header("Location:".$settings_page);
}



add_action('wp_ajax_wpwoofgtaxonmy', 'ajax_wpwoofgtaxonmy');
function ajax_wpwoofgtaxonmy(){
    error_reporting(E_ALL & ~E_NOTICE);

    $lang = 'en-US';
    $file = "http://www.google.com/basepages/producttype/taxonomy.{$lang}.txt";

    $reader = new LazyTaxonomyReader();

    $line_no = (isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null);
    $result = $reader->getDirectDescendants($line_no);
    echo json_encode($result);

    die();
}

class LazyTaxonomyReader {

    private $base = null;
    private $separator = ' > ';
    protected $lines;

    public function __construct($file='') {
        if( empty($file) )
            $file = plugin_dir_path(__FILE__) . 'google-taxonomy.en.txt';

        $this->lines = file($file, FILE_IGNORE_NEW_LINES);
        // remove first line that has version number
        if (substr($this->lines[0], 0, 1) == '#')
            unset($this->lines[0]);
    }

    public function setBaseNode($line_no) {
        if (is_null($line_no)) {
            $this->base = null;
            return;
        }
        $this->base = $this->lines[$line_no];
    }

    public function getDirectDescendants($line_no = null) {
        $this->setBaseNode($line_no);
        // select only lines that are directly below current base node
        $direct = array_filter($this->lines, array($this, 'isDirectlyBelowBase'));

        // return only last part of their names
        return array_map(array($this, 'getLastNode'), $direct);
    }

    protected function getLastNode($line) {
        if (strpos($line, $this->separator) === false) {
            // no separator present
            return $line;
        }
        // strip up to and including last separator
        return substr($line, strrpos($line, $this->separator) + strlen($this->separator));
    }

    protected function str_replace_once($search, $replace, $subject) {
        $firstChar = strpos($subject, $search);
        if ($firstChar !== false) {
            $beforeStr = substr($subject, 0, $firstChar);
            $afterStr = substr($subject, $firstChar + strlen($search));
            return $beforeStr . $replace . $afterStr;
        } else {
            return $subject;
        }
    }

    protected function isDirectlyBelowBase($line) {
        // starting text that must be present
        if (is_null($this->base)) {
            $start = '';
        } else {
            $start = $this->base . $this->separator;
        }
        if ($start !== '') {
            $starts_at_base = (strpos($line, $start) === 0);

            if (!$starts_at_base) { // starts with something different
                return false;
            }
            // remove start text AND the following separator
            $line = $this->str_replace_once($start, '', $line);
        }
        // we're direct descendants if we have no separators left on the line
        if (strpos($line, $this->separator) !== false)
            return false;

        return true;
    }

}

function wpwoof_get_interval() {
    return wpwoof_product_catalog::$interval;
}
