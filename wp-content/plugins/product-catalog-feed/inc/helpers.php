<?php
/* Helper functions */

function wpwoof_get_meta_values( $key = '', $type = 'post', $status = 'publish' ) {
    global $wpdb;

    if( empty( $key ) )
        return;

    $r = $wpdb->get_col( $wpdb->prepare( "
        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '%s' 
        AND p.post_status = '%s' 
        AND p.post_type = '%s'
    ", $key, $status, $type ) );

    return $r;
}

function wpwoof_get_meta_labels() {
    global $wpdb;

    $results = $wpdb->get_results("
        SELECT DISTINCT pm.meta_key FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        AND p.post_status = 'publish' 
        AND p.post_type = 'product' ORDER BY pm.meta_key ASC", ARRAY_A);
    
    $labels = array();
    foreach ($results as $key => $value) {
        $labels[ $value['meta_key'] ] = trim(ucwords(str_replace('_', ' ', $value['meta_key'])));
    }

    return $labels;
}

function wpwoof_get_all_attributes(){
    $taxonomy_objects = get_object_taxonomies( 'product', 'objects');
    foreach ($taxonomy_objects as $taxonomy_key => $taxonomy_object) {
        if( $taxonomy_key == 'product_type' ) {
            $attributes[$taxonomy_key]= 'Product Type ('.$taxonomy_key.')';
        } else {
            $attributes[$taxonomy_key]= $taxonomy_object->label.' ('.$taxonomy_key.')';
        }
    }
    return $attributes;
}

function wpwoof_get_attribute_values($name = "color"){
        global $wpdb;
        //Load the taxonomies
        $sql = "
            SELECT taxo.taxonomy, terms.name, terms.slug
            FROM $wpdb->term_taxonomy taxo
            LEFT JOIN $wpdb->terms terms ON (terms.term_id = taxo.term_id)
            WHERE taxo.taxonomy LIKE 'pa_$name%'
        ";
        $data = $wpdb->get_results($sql);

        if (count($data)) {
            foreach ($data as $key => $value) {
                $info[$key] = $value->name;
            }
        }
        return $info;
}

function wpwoof_get_product_fields_sort(){
    global $woocommerce_wpwoof_common;
    $sort = $woocommerce_wpwoof_common->fields_organize;
    $name = $woocommerce_wpwoof_common->fields_organize_name;
    $sort['general'][] = 'description_short';
    $sort['general'][] = 'variation_description';
    $sort['general'][] = 'stock_quantity';
    $sort['general'][] = 'product_type_normal';
    $sort['price'][] = 'sale_start_date';
    $sort['price'][] = 'sale_end_date';
    $sort['additional_data'][] = 'average_rating';
    $sort['additional_data'][] = 'total_rating';
    $sort['additional_data'][] = 'tags';
    $sort['shipping'][] = 'length';
    $sort['shipping'][] = 'width';
    $sort['shipping'][] = 'height';
    return array('sort' => $sort, 'name' => $name);
}
function wpwoof_get_product_fields(){
    global $woocommerce_wpwoof_common;

    $fields = $woocommerce_wpwoof_common->product_fields;
    $all_fields = array();
    foreach ($fields as $fieldkey => $field) {
        $all_fields[$fieldkey] = $field;   
        if( $fieldkey == 'description' ) {
            $all_fields['description_short'] = array(
                'label'         => __('Short Description', 'woocommerce_wpwoof'),
                'desc'          => __( 'A short paragraph describing the product.', 'woocommerce_wpwoof' ),
                'value'         => false,
                'required'      => true,
                'feed_type'     => array('facebook'),
                'facebook_len'  => 5000,
                'text'          => true,
                'woocommerce_default' =>array('label' => 'description_short', 'value' => 'description_short'),
            );
            $all_fields['variation_description'] = array(
                'label'         => __('Variation Description', 'woocommerce_wpwoof'),
                'desc'          => __( 'Descrioption for variation inside woocommerce.', 'woocommerce_wpwoof' ),
                'value'         => false,
                'required'      => true,
                'feed_type'     => array('facebook'),
                'facebook_len'  => 5000,
                'text'          => true,
                'woocommerce_default' =>array('label' => 'variation_description', 'value' => 'variation_description'),
            );
        }
    }
    $all_fields['mpn']['label'] = 'SKU';
    $post_type = "product";
    $taxonomy_names = get_object_taxonomies( $post_type );
    $value_brand = "";
    foreach( $taxonomy_names as $taxonomy_name ) {
        if( ($taxonomy_name != 'product_cat') && ($taxonomy_name != 'product_tag') && ($taxonomy_name != 'product_type') 
        && ($taxonomy_name != 'product_shipping_class') && ($taxonomy_name != 'pa_color') ) {
            if( strpos($taxonomy_name, "brand") !== false ) {
                $value_brand = $taxonomy_name;
                break;
            }
        }
    }
    $all_fields['brand']['label'] = $all_fields['brand']['label'].' '.$value_brand;
    $all_fields['product_type']['label'] = 'Woo Prod Categories';
    $all_fields['use_custom_attribute'] = array(
        'label'         => __('Custom Attribute', 'woocommerce_wpwoof'),
        'desc'          => __( 'Use custom product attribute value.', 'woocommerce_wpwoof' ),
        'value'         => false,
        'required'      => true,
        'feed_type'     => array('facebook'),
        'facebook_len'  => 5000,
        'text'          => true,
    );
    $all_fields['stock_quantity'] = array(
        'label'         => __('Stock Quantity', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['average_rating'] = array(
        'label'         => __('Average Rating', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['total_rating'] = array(
        'label'         => __('Total Rating', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['sale_start_date'] = array(
        'label'         => __('Sale Start Date', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['sale_end_date'] = array(
        'label'         => __('Sale End Date', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['length'] = array(
        'label'         => __('Length', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['width'] = array(
        'label'         => __('Width', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['height'] = array(
        'label'         => __('Height', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['tags'] = array(
        'label'         => __('Tags', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    $all_fields['product_type_normal'] = array(
        'label'         => __('Product Type', 'woocommerce_wpwoof'),
        'desc'          => '',
        'value'         => false,
        'required'      => false,
        'feed_type'     => array('facebook'),
        'facebook_len'  => false,
        'text'          => true,
    );
    return $all_fields;
}

function wpwoof_get_attribute_value($name='') {
    if( empty($name) )
        return '';
    $values = get_terms( wc_attribute_taxonomy_name($name), 'orderby=name&hide_empty=0' );

    return $values;
}


function  wpwoof_get_all_fields(){
    global $woocommerce_wpwoof_common;

    $all_fields = $woocommerce_wpwoof_common->product_fields;
    $required_fields = array();
    $extra_fields = array();
    foreach ($all_fields as $key => $value) {
        if( isset($value['required']) && $value['required'] == true )
            $required_fields[$key] = $value;
        else
            $extra_fields[$key] = $value;
    }
    $return = array('required' => $required_fields, 'extra' => $extra_fields );
    return $return;
}

function wpwoof_fields_dropdown($field=array(), $selected=''){

}

function wpwoofeed_custom_attribute_input($fieldkey, $field, $wpwoof_values){
    if( isset( $wpwoof_values['field_mapping'][$fieldkey]['custom_attribute'] ) ){
        ?>
        <input type="text" name="field_mapping[<?php echo $fieldkey ?>][custom_attribute]" value="<?php echo $wpwoof_values['field_mapping'][$fieldkey]['custom_attribute']; ?>" class="wpwoof_mapping_attribute" />
        <?php
    }
}
function wpwoof_render_description($fieldkey, $field, $wpwoof_values) {
    if( isset($wpwoof_values['field_mapping'][$fieldkey]['use_child']) ) {
        $value = $wpwoof_values['field_mapping'][$fieldkey]['use_child'];
    } else {
        $value = $field['additional_options']['use_child'];
    }
    ?>
    <p>
        <label class="wpwoof-required-label">
        </label>
        <span class="wpwoof-required-value">
            <label>
                <input type="checkbox" name="field_mapping[<?php echo $fieldkey; ?>][use_child]" class="wpwoof_mapping" value="1"<?php if( ! empty($value) ) echo ' checked'; ?>>
                Use variation description when it exists
            </label>
        </span>
    </p>
    <?php
}
function wpwoof_render_title($fieldkey, $field, $wpwoof_values) {
    if( isset($wpwoof_values['field_mapping'][$fieldkey]['uc_every_first']) ) {
        $value = $wpwoof_values['field_mapping'][$fieldkey]['uc_every_first'];
    } else {
        $value = $field['additional_options']['uc_every_first'];
    }
    ?>
    <p>
        <label class="wpwoof-required-label">
        </label>
        <span class="wpwoof-required-value">
            <label>
                <input type="checkbox" name="field_mapping[<?php echo $fieldkey; ?>][uc_every_first]" class="wpwoof_mapping" value="1"<?php if( ! empty($value) ) echo ' checked'; ?>>
                Remove capital letters from product title
            </label>
        </span>
    </p>
    <?php
}
function wpwoof_sentence_case($string) { 
    $sentences = preg_split('/((?:^|[.?!]+)\s*)/', $string, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE); 
    $new_string = ''; 
    foreach ($sentences as $key => $sentence) { 
        $new_string .= ($key & 1) == 0 ? ucfirst(strtolower($sentence)) : $sentence; 
    } 
    return $new_string; 
}
function wpwoof_feed_option_fulled($values) {
    $fields = wpwoof_get_all_fields();

    $fields = $fields['required'];
    foreach( $fields as $key => $field ) {
        if( ! empty($field['additional_options']) && is_array($field['additional_options']) ) {
            foreach( $field['additional_options'] as $option => $default ) {
                if( ! isset($values[$key]) || ! is_array($values[$key]) ) {
                    $values[$key] = array();
                }
                if( empty($values[$key][$option]) ) {
                    $values[$key][$option] = '';
                }
            }
        }
    }
    return $values;
}