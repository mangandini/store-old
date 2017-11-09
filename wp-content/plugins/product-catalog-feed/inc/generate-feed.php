<?php
function wpwoofeed_generate_feed($data, $type = 'xml', $filep = false, $info = array()){
    if($type == 'csv') {
        $info = array_merge(array('delimiter'=>'tab', 'enclosure' => 'double' ), $info);
        $delimiter = $info['delimiter'];
        if ($delimiter == 'tab') {
            $delimiter = "\t";
        }
        $enclosure = $info['enclosure'];
        if ($enclosure == "double")
            $enclosure = chr(34);
        else if ($enclosure == "single")
            $enclosure = chr(39);
        else
            $enclosure = '"';
    }

    global $woocommerce;
    global $woocommerce_wpwoof_common;
    global $store_info;
    global $wpwoofeed_settings;
    global $wpwoofeed_type;

    $wpwoofeed_type = $type;
    $wpwoofeed_settings = $data;

    $field_rules = wpwoof_get_product_fields();
    $fields = $data['field_mapping'];

    $store_info = new stdClass();
    $store_info->feed_type = 'facebook';
    $store_info->site_url = home_url( '/' );
    $store_info->feed_url_base = home_url( '/' );
    $store_info->blog_name = get_option( 'blogname' );
    $store_info->charset = get_option( 'blog_charset' );
    $store_info->currency = get_woocommerce_currency();
    $store_info->weight_units = get_option( 'woocommerce_weight_unit' );
    $store_info->base_country = $woocommerce->countries->get_base_country();
    $store_info = apply_filters( 'wpwoof_store_info', $store_info );

    $store_info->feed_url = $store_info->feed_url_base;

    if ( ! empty( $store_info->base_country ) && substr( 'US' == $store_info->base_country, 0, 2 ) ) {
        $US_feed = true;
        $store_info->US_feed = true;
    } else {
        $store_info->US_feed = false;
    }

    $data = '';
    $columns = array();
    $values = array();

    global $wp_query, $post, $_wp_using_ext_object_cache;

    $wpwoof_feed = -1;

    $args['post_type'] = 'product';
    $args['post_status'] = 'publish';
    $args['fields'] = 'ids';
    $args['order'] = 'ASC';
    $args['orderby'] = 'ID';
    $args['posts_per_page'] = 110;

    $args['paged'] = 1;
    $wpwoof_limit = false;

    $output_count = 0;
    $products = new WP_Query( $args );

    if( $type == 'xml' ) {
        $header = '';
        $item = '';
        $footer = '';

        $header .= "<?xml version=\"1.0\" encoding=\"".$store_info->charset."\" ?>\n";
        $header .= "<rss xmlns:g=\"http://base.google.com/ns/1.0\" version=\"2.0\">\n";
        $header .= "  <channel>\n";
        $header .= "    <title><![CDATA[" . $store_info->blog_name . " Products]]></title>\n";
        $header .= "    <link><![CDATA[" . $store_info->site_url . "]]></link>\n";
        $header .= "    <description>WooCommerce Product List RSS feed</description>\n";
        if( $filep !== false ) {
            fwrite($filep, $header);
            unset($header);
        }
    }

    $columns_name = false;
    while( $products->have_posts() ) {
        $products = $products->get_posts();
        foreach($products as $id_post => $post) {
            $product = wpwoofeed_load_product($post);
            $item = '';
            
            $data = wpwoofeed_item($fields, $product);
            if( $type == 'xml' ) {
                $item .= $data;
            } else {
                if( ! $columns_name ) {
                    $columns  = $data[0];
                    $header = array();
                    foreach ($columns as $column_name => $value) {
                        $header[] = $column_name;
                    }
                    fputcsv($filep, $header, $delimiter, $enclosure);
                    $columns_name = true;
                }
                $fields_item = $data[1];
                if( count($fields_item) == count($columns) ) {
                    fputcsv($filep, $fields_item, $delimiter, $enclosure);
                }
            }
            if ($product->has_child() ) {
                $children = $product->get_children();
                foreach ($children as $child) {
                    $child = $product->get_child( $child );

                    $data = wpwoofeed_item($fields, $child);
                    if( $type == 'xml' ) {
                        $item .= $data;
                    } else {
                        $fields_item = $data[1];
                        if( count($fields_item) == count($columns) ) {
                            fputcsv($filep, $fields_item, $delimiter, $enclosure);
                        }
                    }
                    $output_count++;
                }
            } else {
                $output_count++;
            }
            if( $type == 'xml' && $filep !== false ) {
                fwrite($filep, $item);
                unset($item);
            }
        }
        $args['paged']++;
        $products = new WP_Query( $args );
    }
    if( $type == 'xml' ) {
        $footer = '';
        
        $footer .= "  </channel>\n";
        $footer .= "</rss>";
        if( $filep !== false ) {
            fwrite($filep, $footer);
            unset($footer);
        }
    }

    return true;
}

function wpwoofeed_item( $fields, $product ) {
    if( empty($fields) || empty($product) )
        return '';

    global $wpwoofeed_type;
    global $woocommerce;
    global $woocommerce_wpwoof_common;
    global $store_info;
    global $wpwoofeed_settings;

    $item = '';

    $field_rules = wpwoof_get_product_fields();

    if( 'xml' == $wpwoofeed_type ) {
        $item    = "    <item>" . "\n";
    } else if( 'csv' == $wpwoofeed_type ) {
        $columns = array();
        $values = array();
    }

    foreach ($fields as $tag => $field) {
        if( $tag == 'product_type' && !empty( $wpwoofeed_settings['feed_google_category']) && $wpwoofeed_settings['feed_google_category'] != '' ) {
            if( 'xml' == $wpwoofeed_type ) {
                $item .="        <g:google_product_category>".htmlspecialchars($wpwoofeed_settings['feed_google_category'])."</g:google_product_category>" . "\n";
            } else if('csv' == $wpwoofeed_type) {
                $columns['google_product_category'] = 1;
                $values[] = htmlspecialchars($wpwoofeed_settings['feed_google_category']);
            }
        }

        if( !isset($field_rules[$tag]['required']) || $field_rules[$tag]['required'] != true ) {
            continue;
        }
        $extra_param = 0;

        if( strpos($tag, 'custom_label_') !== false ) {
            $func = 'custom_label';
        } else if( strpos($tag, 'additional_image_link') !== false ) {
            $func = 'additional_image_link';
            $extra_param = str_replace('additional_image_link_', '', $tag);
        } else {
            $func = $tag;
        }
        $func = trim($func);
        $tagvalue = '';
        $field['rules'] = isset($field_rules[$tag]) ? $field_rules[$tag] : '';
        try{
            $tagvalue = call_user_func('wpwoofeed_' . $func , $product, $item, $field, $tag);
        } catch (Exception $e) {
            $tagvalue = '';
        }

        if( strpos($tag, 'additional_image_link') !== false ) {
            $tag = 'additional_image_link';
        }
        if( 'xml' == $wpwoofeed_type ) {
            if( $tagvalue && !empty($tagvalue) ) {
                $item .="        <g:" . $tag .">".$tagvalue."</g:" . $tag .">" . "\n";
            }
        } else if('csv' == $wpwoofeed_type) {
            $columns[$tag] = 1;
            $values[] = $tagvalue;
        }
    }

    if( 'xml' == $wpwoofeed_type ) {
        $item .= "    </item>" . "\n";
        return $item;
    } else if('csv' == $wpwoofeed_type) {
        return array($columns, $values);
    }
}

function wpwoofeed_meta( $product, $item, $field, $id ) {
    $attribute = str_replace('wpwoofmeta_', '', $field['value']);
    $tagvalue = get_post_meta( $product->id, $attribute, true);
    $tagvalue = is_array($tagvalue) ? '' : $tagvalue;    
    $tagvalue = do_shortcode( $tagvalue );
    
    return $tagvalue;
}

function wpwoofeed_attr( $product, $item, $field, $id ) {
    if( isset( $field['define'] ) && isset($field['define']['option']) )
        $taxonomy = $field['define']['option'];
    else
        $taxonomy = $field['value'];

    $taxonomy = str_replace('wpwoofattr_', '', $taxonomy);
    $taxonomy = str_replace('wpwoofdefa_', '', $taxonomy);
    
    $tagvalue = '';
    if( strpos($taxonomy, 'pa_') !== false && $product->product_type == 'variation' ) {
        $taxonomy = str_replace('pa_', '', $taxonomy);
        $attributes = $product->get_variation_attributes();
        foreach ($attributes as $attribute => $attribute_value) {
            if( strpos($attribute, $taxonomy) !== false ) {
                $attribute_value = do_shortcode( $attribute_value );
                return $attribute_value;
            }
        }
    } else {
        $the_terms = wp_get_post_terms( $product->id, $taxonomy, array( 'fields' => 'names' ));

        $tagvalue = '';
        if( !is_wp_error($the_terms) && !empty($the_terms) ) {
            foreach ($the_terms as $term) {
                $tagvalue .= $term.', ';
            }
            $tagvalue = rtrim($tagvalue, ', ');
        }
    }
   $tagvalue = do_shortcode( $tagvalue );

   return $tagvalue;
}

function wpwoofeed_xml_has_error($message) {
    global $xml_has_some_error;
    if( ! $xml_has_some_error ) {
        add_action( 'admin_notices', create_function( '', 'echo "'.$message.'";' ), 9999 );
        $xml_has_some_error = true;
    }
}

function wpwoofeed_custom_user_function( $product, $item, $field, $id, $data = array() ) {
    $value = $field['value'];
    $tagvalue = '';
    if( empty($value) ) {
        return '';
    } elseif( strpos($value, 'wpwoofdefa_condition') !== false ) {
        return '';
    } elseif( strpos($value, 'wpwoofdefa_brand') !== false ) {
        $post_type = "product";
        $taxonomy_names = get_object_taxonomies( $post_type );
        $exist_brand = false;
        foreach( $taxonomy_names as $taxonomy_name ) {
            if( ($taxonomy_name != 'product_cat') && ($taxonomy_name != 'product_tag') && ($taxonomy_name != 'product_type') 
            && ($taxonomy_name != 'product_shipping_class') ) {

                if( strpos($taxonomy_name, "brand") !== false ) {
                    $value = 'wpwoofattr_'.$taxonomy_name;
                    $field['value'] = $value;
                    $exist_brand = true;
                    break;
                }
            }
        }
        if( ! $exist_brand ) {
            return '';
        }
    }
    $data = array_merge(array(
        'wpwoofdefa' => true,
        'wpwoofmeta' => true,
        'wpwoofattr' => true,
    ), $data);
    if( strpos($value, 'wpwoofdefa_') !== false && $data['wpwoofdefa'] ){
        return wpwoofeed_tagvalue($value, $product, $id, $field);
    } else if( strpos($value, 'wpwoofmeta_') !== false && $data['wpwoofmeta'] ){
        return wpwoofeed_meta($product, $item, $field, $id);
    } else if( strpos($value, 'wpwoofattr_') !== false && $data['wpwoofattr'] ){
        return wpwoofeed_attr($product, $item, $field, $id);
    }

    return false;
}

function wpwoofeed_id($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_availability($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_description($product, $item, $field, $id){
    if( isset( $field['use_child'] ) && ! $field['use_child'] ) {
        $product_parent = wpwoofeed_load_product($product->id);
    } else {
        $product_parent = $product;
    }
    $desc = wpwoofeed_custom_user_function($product_parent, $item, $field, $id);
    if( empty($desc) ) {
        wpwoofeed_xml_has_error('Description missing in some products');
    }
    return $desc;
}
function wpwoofeed_product_image($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_description_short($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_variation_description($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_image_product($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_image_link($product, $item, $field, $id){
    $link = wpwoofeed_custom_user_function($product, $item, $field, $id);
    $link = htmlspecialchars($link);
    if( empty($link) ) {
        wpwoofeed_xml_has_error('Image link missing in some products');
    }
    return $link;
}
function wpwoofeed_link($product, $item, $field, $id){
    $link = wpwoofeed_custom_user_function($product, $item, $field, $id);
    $link = htmlspecialchars($link);
    return $link;
}
function wpwoofeed_title($product, $item, $field, $id){
    $title = wpwoofeed_custom_user_function($product, $item, $field, $id);
    if( ! empty($field['uc_every_first']) ) {
        $title = wpwoof_sentence_case($title);
    }
    if( !empty($title) )
        $title = wpwoofeed_text($title);
    return $title;
}
function wpwoofeed_price($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_mpn($product, $item, $field, $id){
    $mpn = wpwoofeed_custom_user_function($product, $item, $field, $id);
    if( ! empty($mpn) ) {
        $mpn = '<![CDATA[' . $mpn . ']]>';
    }
    return $mpn;
}
function wpwoofeed_gtin($product, $item, $field, $id){
    $gtin = wpwoofeed_custom_user_function($product, $item, $field, $id);
    if( ! empty($gtin) ) {
        $gtin = '<![CDATA[' . $gtin . ']]>';
    }
    return $gtin;
}
function wpwoofeed_item_group_id($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_product_type($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_additional_image_link($product, $item, $field, $id){
    $link = wpwoofeed_custom_user_function($product, $item, $field, $id);
    $link = htmlspecialchars($link);
    return $link;
}
function wpwoofeed_sale_price($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_custom_label($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_age_group($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_expiration_date($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_color($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_gender($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_material($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_pattern($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_condition($product, $item, $field, $id){
    $tagvalue = wpwoofeed_custom_user_function($product, $item, $field, $id);
    if( empty($tagvalue) ) {
        $tagvalue = 'new';
    } else {
        $tagvalue = str_replace(',', ' , ', $tagvalue);
        $tagvalue = ' '.$tagvalue.' ';
        $tagvalue = strtolower($tagvalue);
        if( strpos($tagvalue, ' new ') !== false ) {
            $tagvalue = 'new';
        } elseif( strpos($tagvalue, ' used ') !== false ) {
            $tagvalue = 'used';
        } elseif( strpos($tagvalue, ' refurbished ') !== false ) {
            $tagvalue = 'refurbished';
        } else {
            $tagvalue = 'new';
        }
    }
    return $tagvalue;
}
function wpwoofeed_brand($product, $item, $field, $id){
    $tagvalue = wpwoofeed_custom_user_function($product, $item, $field, $id);
    if( empty($tagvalue) ) {
        wpwoofeed_xml_has_error('Brand missing in some products');
    }
    return $tagvalue;
}
function wpwoofeed_sale_price_effective_date($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_shipping($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_shipping_weight($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}
function wpwoofeed_shipping_size($product, $item, $field, $id){
    return wpwoofeed_custom_user_function($product, $item, $field, $id);
}

function wpwoofeed_load_product( $post ) {
    if ( function_exists( 'wc_get_product' ) ) {
        // 2.2 compat.
        return wc_get_product( $post );
    } else if ( function_exists( 'get_product' ) ) {
        // 2.0 compat.
        return get_product( $post );
    } else {
        return new WC_Product( $post->ID );
    }
}


function wpwoofeed_enforce_length($text, $length, $full_words = false){
    if ( $length === false || strlen( $text ) <= $length ) {
        return $text;
    }

    if ( $full_words === true ) {
        $text = substr( $text, 0, $length );
        $pos = strrpos($text, ' ');
        $text = substr( $text, 0, $pos );
    } else {
        $text = substr( $text, 0, $length );
    }

    return $text;
}


function wpwoofeed_text($text){
    global $wpwoofeed_type;
    $text = do_shortcode( $text );
    if('xml' == $wpwoofeed_type)    
        $text = "<![CDATA[" . $text . "]]>";

    return $text;
}

function wpfeed_thumbnail_src( $post_id = null, $size = 'post-thumbnail' ) {
    $post_thumbnail_id = get_post_thumbnail_id( $post_id );
    if ( ! $post_thumbnail_id ) {
        return false;
    }
    list( $src ) = wp_get_attachment_image_src( $post_thumbnail_id, $size, false );

    return $src;
}


function wpwoofeed_product_prices( &$feed_item, $woocommerce_product ) {

    // Grab the price of the main product.
    $prices = wpwoofeed_generate_prices_for_product( $woocommerce_product );

    // Adjust the price if there are cheaper child products.
    $prices = wpwoofeed_adjust_prices_for_children( $prices, $woocommerce_product );

    // Set the selected prices into the feed item.
    $feed_item->regular_price_ex_tax  = $prices->regular_price_ex_tax;
    $feed_item->regular_price_inc_tax = $prices->regular_price_inc_tax;
    $feed_item->sale_price_ex_tax     = $prices->sale_price_ex_tax;
    $feed_item->sale_price_inc_tax    = $prices->sale_price_inc_tax;
    $feed_item->price_inc_tax         = $prices->price_inc_tax;
    $feed_item->price_ex_tax          = $prices->price_ex_tax;
}


function wpwoofeed_generate_prices_for_product( $woocommerce_product ) {

    $prices = new stdClass();
    $prices->sale_price_ex_tax     = null;
    $prices->sale_price_inc_tax    = null;
    $prices->regular_price_ex_tax  = null;
    $prices->regular_price_inc_tax = null;

    // Grab the regular price of the base product.
    $regular_price  = $woocommerce_product->get_regular_price();
    if ( '' != $regular_price ) {
        $prices->regular_price_ex_tax  = $woocommerce_product->get_price_excluding_tax( 1, $regular_price );
        $prices->regular_price_inc_tax = $woocommerce_product->get_price_including_tax( 1, $regular_price );
    }

    // Grab the sale price of the base product.
    $sale_price                    = $woocommerce_product->get_sale_price();
    if ( $sale_price != '' ) {
        $prices->sale_price_ex_tax  = $woocommerce_product->get_price_excluding_tax( 1, $sale_price );
        $prices->sale_price_inc_tax = $woocommerce_product->get_price_including_tax( 1, $sale_price );
    }

    // Populate a "price", using the sale price if there is one, the actual price if not.
    if ( null != $prices->sale_price_ex_tax ) {
        $prices->price_ex_tax  = $prices->sale_price_ex_tax;
        $prices->price_inc_tax = $prices->sale_price_inc_tax;
    } else {
        $prices->price_ex_tax  = $prices->regular_price_ex_tax;
        $prices->price_inc_tax = $prices->regular_price_inc_tax;
    }
    return $prices;
}

function wpwoofeed_adjust_prices_for_children( $prices, $woocommerce_product ) {
    global $wpwoofeed_settings;

    if ( ! $woocommerce_product->has_child() ) {
        return $prices;
    }

    $first_child = true;

    $children = $woocommerce_product->get_children();
    foreach ( $children as $child ) {
        $child_product = $woocommerce_product->get_child( $child );
        if ( ! $child_product ) {
            continue;
        }
        if ( 'variation' == $child_product->product_type ) {
            $child_is_visible = wpwoofeed_variation_is_visible( $child_product );
        } else {
            $child_is_visible = $child_product->is_visible();
        }
        if ( ! $child_is_visible ) {
            continue;
        }
        $child_prices = wpwoofeed_generate_prices_for_product( $child_product );
        if( $first_child ){
            $first_child_prices = $child_prices;
            $first_child = false;
        }
        if ( ( 0 == $prices->price_inc_tax ) && ( $child_prices->price_inc_tax > 0 ) ) {
            $prices = $child_prices;
        } else {
            if ( ($child_prices->price_inc_tax > 0) && ($child_prices->price_inc_tax < $prices->price_inc_tax) )
                $prices = $child_prices;
        }
    }
    return $prices;
}

function wpwoofeed_variation_is_visible($variation) {
    if ( method_exists( $variation, 'variation_is_visible' ) ) {
        return $variation->variation_is_visible();
    }
    $visible = true;
    // Published == enabled checkbox
    if ( 'publish' != get_post_status( $variation->variation_id ) ) {
        $visible = false;
    }
    // Out of stock visibility
    elseif ( 'yes' == get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $variation->is_in_stock() ) {
        $visible = false;
    }
    // Price not set
    elseif ( $variation->get_price() === '' ) {
        $visible = false;
    }
    return $visible;
}

function wpwoofeed_tagvalue($tag, $product, $tagid, $tag_option = array()){
    global $wpwoofeed_settings;
    global $store_info;
    global $woocommerce_wpwoof_common;
    $field_rules = wpwoof_get_product_fields();

    $tag = str_replace('wpwoofdefa_', '', $tag);

    if( strpos($tagid, 'additional_image_link') !== false ) {
        $image_position = str_replace('additional_image_link_', '', $tagid);
        $image_position = (int) $image_position - 1;
    } else if( strpos($tag, 'additional_image_link') !== false ) {
        $image_position = str_replace('additional_image_link_', '', $tag);
        $image_position = (int) $image_position - 1;
    }
    if( strpos($tag, 'additional_image_link') !== false ){
        $tag = 'additional_image_link';
    }

    $length = false;
    if( $field_rules[$tag][$store_info->feed_type.'_len'] != false ){
        $length = $field_rules[$tag][$store_info->feed_type.'_len'];
    } 

    switch ($tag) {
        case 'id':
            $return = $product->id;
            if( $product->product_type == 'variation') {    
                $return = $product->variation_id;
            }
            $return = wpwoofeed_enforce_length( $return, $length );
            return $return;
            break;
        case 'availability':
            if( $product->is_in_stock() ) {
                $stock = 'in stock';
            } else {
                $stock = 'out of stock';
            }
            return $stock;
            break;
        case 'description':
            $description = '';
            if( $product->product_type == 'variation' && isset($product->variation_id)){
                $product_id = $product->variation_id;
                $description = get_post_meta($product_id, '_variation_description', true);
            }
            if( empty($description) ) {
                $description = $product->post->post_content;
            }
            $description = strip_tags($description);
            $description = wpwoofeed_enforce_length($description, $length, true);
            if( !empty($description) )
                $description = wpwoofeed_text($description);
            return $description;
            break;
        case 'description_short':
            $short_description = $product->post->post_excerpt;
            $short_description = strip_tags($short_description);
            $short_description = wpwoofeed_enforce_length($short_description, $length, true);
            if( !empty($short_description) )
                $short_description = wpwoofeed_text($short_description);

            return $short_description;
            break;
        case 'variation_description':
            $variation_description = '';
            if( $product->product_type == 'variation'){
                    $product_id = $product->variation_id;
                    $variation_description = get_post_meta($product_id, '_variation_description', true);
            } 
            $variation_description = wpwoofeed_enforce_length( $variation_description, $length, true );
            if( !empty($variation_description) )
                $variation_description = wpwoofeed_text($variation_description);

            return $variation_description;
            break;
        case 'use_custom_attribute':
            $attribute_value = '';
            if( isset( $wpwoofeed_settings['field_mapping'][$tagid]['custom_attribute'] ) ) {
                $custom_attribute = $wpwoofeed_settings['field_mapping'][$tagid]['custom_attribute'];
                $taxonomy = strtolower($custom_attribute);
                if( !empty($taxonomy) && $product->product_type == 'variation') {
                    $attributes = $product->get_variation_attributes();
                    foreach ($attributes as $attribute => $attribute_value) {
                        $attribute = strtolower($attribute);
                        if( strpos($attribute, $taxonomy) !== false )
                            return $attribute_value;
                    }
                }
            }
            return $attribute_value;
            break;
        case 'image_link':
            return wpfeed_thumbnail_src($product->id);
            break;
        case 'product_image':
            return wpfeed_thumbnail_src($product->id, 'shop_single');
            break;
        case 'link':
            $url = get_permalink( $product->id );
            if( $product->product_type == 'variation' && isset($product->variation_id) ) {
                $wc_product = new WC_Product_Variation($product->variation_id);
                $url = $wc_product->get_permalink();
                unset($wc_product);
            }
            return $url;
            break;
        case 'title':
            $title = '';
            $title = get_the_title( $product->id );
            $title = wpwoofeed_enforce_length( $title, $length, true );
            return $title;
            break;
        case 'price':
            $feed_item = new stdClass();
            wpwoofeed_product_prices($feed_item, $product);
            // Regular price
            if ( $store_info->US_feed ) {
                // US prices have to be submitted excluding tax
                $price = number_format( $feed_item->regular_price_ex_tax, 2, '.', '' );
            } else {
                // Non-US prices have to be submitted including tax
                $price = number_format( $feed_item->regular_price_inc_tax, 2, '.', '' );
            }
            if( empty($price) )
                return false;
            $price = $price . ' ' . $store_info->currency;
            return $price;
            break;
        case 'mpn':
            $return = $product->get_sku();
            $return = wpwoofeed_enforce_length( $return, $length );
            return $return;
            break;
        case 'condition':
            break;
        case 'brand':
            break;
        case 'additional_image_link':
            $tagvalue = '';
            $imgIds = $product->get_gallery_attachment_ids();
                $images = array();
                if (count($imgIds)) {
                    foreach ($imgIds as $key => $value) {
                        if ($key < 9) {
                            $images[$key] = wp_get_attachment_url($value);
                        }
                    }
                }
                if ($images && is_array($images)) {
                    if( isset( $images[$image_position] ) )  
                        $tagvalue .= $images[$image_position] . ',';
                }
                $tagvalue = rtrim($tagvalue, ',');
                return $tagvalue;
            break;
        case 'item_group_id':
            if( $product->product_type == 'variation')
                return $product->id;
            else
                return '';
            break;
        case 'product_type':
            $categories = wp_get_object_terms($product->id, 'product_cat');
            $categories_string = array();
            if( ! is_wp_error($categories) ) {
                foreach($categories as $cat) {
                    $categories_string[] = $cat->name;
                }
            }
            $categories_string = implode(', ', $categories_string);
            $categories_string = wpwoofeed_enforce_length( $categories_string, $length, true );
            return $categories_string;
            break;
        case 'product_type_normal':
            $product_type = $product->get_type();
            return $product_type;
            break;
        case 'sale_price':
            $feed_item = new stdClass();
            wpwoofeed_product_prices($feed_item, $product);
            // If there's no sale price, then we're done.
            if ( empty( $feed_item->sale_price_inc_tax ) )
                return false;
            // Otherwise, include the sale_price tag.
            if ( $store_info->US_feed ) {
                // US prices have to be submitted excluding tax.
                $sale_price = number_format( $feed_item->sale_price_ex_tax, 2, '.', '' );
            } else {
                $sale_price = number_format( $feed_item->sale_price_inc_tax, 2, '.', '' );
            }
            if ( empty( $sale_price) )
                return false;
            $sale_price = $sale_price . ' ' . $store_info->currency;
            return $sale_price;
            break;
        case 'sale_price_effective_date':
            if( isset($product->variation_id) )
                $product_id = $product->variation_id;
            else
                $product_id = $product->id;
            $from = get_post_meta($product_id, '_sale_price_dates_from', true);
            $to = get_post_meta($product_id, '_sale_price_dates_to', true);

            if (!empty($from) && !empty($to)) {
                $from = date_i18n('Y-m-d\TH:iO', $from);
                $to = date_i18n('Y-m-d\TH:iO', $to);
                $date = "$from" . "/" . "$to";
            } else {
                $date = "";
            }
            $tagvalue = $date;
            
            return $tagvalue;
            break;
        case 'shipping':
            $shipping = $product->get_shipping_class();
            return $shipping;
            break;
        case 'shipping_weight':
            $tagvalue = $product->get_weight();
            $unit = get_option( 'woocommerce_weight_unit' );
            if( !empty($tagvalue) ) {
                return $tagvalue . ' ' . esc_attr($unit);
            } else {
                return '';
            }
            break;
        case 'shipping_size':
            $diemensions = $product->get_dimensions();
            return $diemensions;
            break;
        case 'length':
            $length = $product->get_length();
            return $length;
            break;
        case 'width':
            $width = $product->get_width();
            return $width;
            break;
        case 'height':
            $height = $product->get_height();
            return $height;
            break;
        case 'weight':
            $weight = $product->get_weight();
            return $weight;
            break;
        case 'tags':
            $tags = wp_get_object_terms($product->id, 'product_tag');
            $tags_string = array();
            if( ! is_wp_error($tags) ) {
                foreach($tags as $tag) {
                    $tags_string[] = $tag->name;
                }
            }
            $tags_string = implode(', ', $tags_string);
            $tags_string = wpwoofeed_enforce_length( $tags_string, $length, true );
            return $tags_string;
            break;
        case 'custom_label_':
            break;
        case 'stock_quantity':
            $stock_quantity = $product->get_stock_quantity();
            if( empty($stock_quantity) ) {
                $stock_quantity = 0;
            }
            return $stock_quantity;
            break;
        case 'average_rating':
            $average_rating = $product->get_average_rating();
            return $average_rating;
            break;
        case 'total_rating':
            $total_rating = $product->get_rating_count();
            return $total_rating;
            break;
        case 'sale_start_date':
            if( isset($product->variation_id) )
                $product_id = $product->variation_id;
            else
                $product_id = $product->id;
            $from = get_post_meta($product_id, '_sale_price_dates_from', true);

            if (!empty($from)) {
                $tagvalue = date_i18n('Y-m-d\TH:iO', $from);
            } else {
                $tagvalue = "";
            }
            return $tagvalue;
            break;
        case 'sale_end_date':
            if( isset($product->variation_id) )
                $product_id = $product->variation_id;
            else
                $product_id = $product->id;
            $to = get_post_meta($product_id, '_sale_price_dates_to', true);

            if (!empty($to)) {
                $tagvalue = date_i18n('Y-m-d\TH:iO', $from);
            } else {
                $tagvalue = "";
            }
            return $tagvalue;
            break;
        default:
            return '';
            break;
    }
    return '';
}

function wpwoofeed_product_is_excluded($woocommerce_product){
    $excluded = false;
    // Check to see if the product is set as Hidden within WooCommerce.
    if ( 'hidden' == $woocommerce_product->visibility ) {
        $excluded = true;
    }
    // Check to see if the product has been excluded in the feed config.
    if ( $tmp_product_data = wpwoofeed_get_product_meta( $woocommerce_product, 'woocommerce_wpwoof_data' ) ) {
        $tmp_product_data = maybe_unserialize( $tmp_product_data );
    } else {
        $tmp_product_data = array();
    }
    if ( isset ( $tmp_product_data['exclude_product'] ) ) {
        $excluded = true;
    }

    return apply_filters( 'wpwoof_exclude_product', $excluded, $woocommerce_product->id, 'facebook');
}

function wpwoofeed_get_product_meta( $product, $field_name ) {
    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0' ) >= 0 ) {
        // even in WC >= 2.0 product variations still use the product_custom_fields array apparently
        if ( $product->variation_id && isset( $product->product_custom_fields[ '_' . $field_name ][0] ) && $product->product_custom_fields[ '_' . $field_name ][0] !== '' ) {
            return $product->product_custom_fields[ '_' . $field_name ][0];
        }
        // use magic __get
        return $product->$field_name;
    } else {
        // variation support: return the value if it's defined at the variation level
        if ( isset( $product->variation_id ) && $product->variation_id ) {
            if ( ( $value = get_post_meta( $product->variation_id, '_' . $field_name, true ) ) !== '' ) {
                return $value;
            }
            // otherwise return the value from the parent
            return get_post_meta( $product->id, '_' . $field_name, true );
        }
        // regular product
        return isset( $product->product_custom_fields[ '_' . $field_name ][0] ) ? $product->product_custom_fields[ '_' . $field_name ][0] : null;
    }
}
