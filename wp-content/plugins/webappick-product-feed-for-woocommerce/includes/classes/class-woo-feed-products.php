<?php

/**
 * This is used to store all the information about wooCommerce store products
 *
 * @since      1.0.0
 * @package    Woo_Feed
 * @subpackage Woo_Feed/includes
 * @author     Ohidul Islam <wahid@webappick.com>
 */
class Woo_Feed_Products
{

    /**
     * Contain all parent product information for the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $parent Contain all parent product information for the plugin.
     */
    public $parent;

    /**
     * Contain all child product information for the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $parent Contain all child product information for the plugin.
     */
    public $child;

    /**
     * The parent id of current product.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $parentID The current product's Parent ID.
     */
    public $parentID;
    /**
     * The child id of current product.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $parentID The current product's child ID.
     */
    public $childID;

    /**
     * The Variable that contain all products.
     *
     * @since    1.0.0
     * @access   private
     * @var      array $productsList Products list array.
     */
    public $productsList;

    /**
     * The Variable that contain all attributes.
     *
     * @since    1.0.0
     * @access   private
     * @var      array $attributeList attributes list array.
     */
    public $attributeList;

    public $feedRule;
    public $idExist=array();
    /**
     * Get WooCommerce Product
     * @param string $feedRule
     * @return array
     */
    public function woo_feed_get_visible_product($feedRule="")
    {
        try{

            if(!empty($feedRule)){
                $this->feedRule=$feedRule;
            }
            $limit = !empty($feedRule['Limit']) && is_numeric($feedRule['Limit']) ? absint($feedRule['Limit']) : '2000';
            $offset = !empty($feedRule['Offset']) && is_numeric($feedRule['Offset']) ? absint($feedRule['Offset']) : '0';

            if($offset=='0'){
                delete_option("wf_check_duplicate");
            }
            $getIDs=get_option("wf_check_duplicate");
            $arg=array(
                'post_type' => array('product', 'product_variation'),
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'desc',
                'fields' => 'ids',
                'offset' => $offset,
                'cache_results' => false,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            );


            # Query Database for products
            $loop = new WP_Query($arg);

            $i = 0;

            while ($loop->have_posts()) : $loop->the_post();

                $this->childID = get_the_ID();
                $this->parentID = (wp_get_post_parent_id($this->childID))?wp_get_post_parent_id($this->childID):$this->childID;

                global $product;
                if(!is_object($product)|| !$product->is_visible()){
                    continue;
                }

                $type1 = "";
                if (is_object($product) && $product->is_type('simple')) {
                    # No variations to product
                    $type1 = "simple";
                } elseif (is_object($product) && $product->is_type('variable')) {
                    # Product has variations
                    $type1 = "variable";
                } elseif (is_object($product) && $product->is_type('grouped')) {
                    $type1 = "grouped";
                } elseif (is_object($product) && $product->is_type('external')) {
                    $type1 = "external";
                } elseif (is_object($product) && $product->is_downloadable()) {
                    $type1 = "downloadable";
                } elseif (is_object($product) && $product->is_virtual()) {
                    $type1 = "virtual";
                }


                $post = get_post($this->parentID);

                if(!is_object($post)){
                    continue;
                }

                if($post->post_status=='trash'){
                    continue;
                }



                if (get_post_type() == 'product_variation' && $this->feedRule['provider']!='facebook') {
                    if ($this->parentID != 0) {

                        $status=get_post($this->childID);
                        if(!$status || !is_object($status) ){
                            continue;
                        }

                        if($status->post_status=="trash"){
                            continue;
                        }

                        $parentStatus=get_post($this->parentID);
                        if($parentStatus && is_object($parentStatus) && $parentStatus->post_status!='publish'){
                            continue;
                        }

                        # Check Valid URL
                        $mainImage = wp_get_attachment_url($product->get_image_id());
                        $link = $product->get_permalink($this->childID);

                        if($this->feedRule['provider']!='custom'){
                            if (substr(trim($link), 0, 4) !== "http" && substr(trim($mainImage), 0, 4) !== "http") {
                                continue;
                            }
                        }

                        if($getIDs){
                            if(in_array($this->childID,$getIDs)){
                                continue;
                            }else{
                                array_push($this->idExist,$this->childID);
                            }
                        }else{
                            array_push($this->idExist,$this->childID);
                        }


                        $this->productsList[$i]['id'] = $this->childID;
                        $this->productsList[$i]['variation_type'] = "child";
                        $this->productsList[$i]['item_group_id'] = $this->parentID;
                        $this->productsList[$i]['sku'] = $this->getAttributeValue($this->childID, "_sku");
                        $this->productsList[$i]['parent_sku'] = $this->getAttributeValue($this->parentID, "_sku");
                        $this->productsList[$i]['title'] = $post->post_title;
                        $this->productsList[$i]['description'] = $post->post_content;

                        # Short Description to variable description
                        $vDesc = $this->getAttributeValue($this->childID, "_variation_description");
                        if (!empty($vDesc)) {
                            $this->productsList[$i]['short_description'] = $vDesc;
                        } else {
                            $this->productsList[$i]['short_description'] = $post->post_excerpt;
                        }

                        $this->productsList[$i]['product_type'] = $this->get_product_term_list($post->ID, 'product_cat', "", ">");// $this->categories($this->parentID);//TODO
                        $this->productsList[$i]['link'] = $link;
                        $this->productsList[$i]['ex_link'] = "";
                        $this->productsList[$i]['image'] = $this->get_formatted_url($mainImage);

                        # Featured Image
                        if (has_post_thumbnail($post->ID)):
                            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($image[0]);
                        else:
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($mainImage);
                        endif;

                        # Additional Images
                        $imageLinks=array();
                        $images = $this->additionalImages($this->childID);
                        if ($images && is_array($images)) {
                            $mKey=1;
                            foreach ($images as $key => $value) {
                                if ($value != $this->productsList[$i]['image']) {
                                    $imgLink=$this->get_formatted_url($value);
                                    $this->productsList[$i]["image_$mKey"] = $imgLink;
                                    if(!empty($imgLink)){
                                        array_push($imageLinks,$imgLink);
                                    }
                                }
                                $mKey++;
                            }
                        }
                        $this->productsList[$i]['images'] = implode(",",$imageLinks);
                        $this->productsList[$i]['condition'] = "New";
                        $this->productsList[$i]['type'] = $product->get_type();
                        $this->productsList[$i]['visibility'] = $this->getAttributeValue($this->childID,"_visibility");
                        $this->productsList[$i]['rating_total'] = $product->get_rating_count();
                        $this->productsList[$i]['rating_average'] = $product->get_average_rating();
                        $this->productsList[$i]['tags'] = $this->get_product_term_list($post->ID, 'product_tag');
                        $this->productsList[$i]['shipping'] = $product->get_shipping_class();

                        $this->productsList[$i]['availability'] = $this->availability($this->childID);
                        $this->productsList[$i]['quantity'] = $this->get_quantity($this->childID, "_stock");
                        $this->productsList[$i]['sale_price_sdate'] = $this->get_date($this->childID, "_sale_price_dates_from");
                        $this->productsList[$i]['sale_price_edate'] = $this->get_date($this->childID, "_sale_price_dates_to");
                        $this->productsList[$i]['price'] = ($product->get_regular_price()) ? $product->get_regular_price() : $product->get_price();
                        $this->productsList[$i]['sale_price'] = ($product->get_sale_price()) ? $product->get_sale_price() : "";
                        $this->productsList[$i]['weight'] = ($product->get_weight()) ? $product->get_weight() : "";
                        $this->productsList[$i]['width'] = ($product->get_width()) ? $product->get_width() : "";
                        $this->productsList[$i]['height'] = ($product->get_height()) ? $product->get_height() : "";
                        $this->productsList[$i]['length'] = ($product->get_length()) ? $product->get_length() : "";

                        # Sale price effective date
                        $from = $this->sale_price_effective_date($this->childID, '_sale_price_dates_from');
                        $to = $this->sale_price_effective_date($this->childID, '_sale_price_dates_to');
                        if (!empty($from) && !empty($to)) {
                            $from = date("c", strtotime($from));
                            $to = date("c", strtotime($to));
                            $this->productsList[$i]['sale_price_effective_date'] = "$from" . "/" . "$to";
                        } else {
                            $this->productsList[$i]['sale_price_effective_date'] = "";
                        }

                    }
                } elseif (get_post_type() == 'product') {
                    if ($type1 == 'simple') {

                        $mainImage = wp_get_attachment_url($product->get_image_id());
                        $link = get_permalink($post->ID);

                        if($this->feedRule['provider']!='custom'){
                            if (substr(trim($link), 0, 4) !== "http" && substr(trim($mainImage), 0, 4) !== "http") {
                                continue;
                            }
                        }

                        if($getIDs){
                            if(in_array($post->ID,$getIDs)){
                                continue;
                            }else{
                                array_push($this->idExist,$post->ID);
                            }
                        }else{
                            array_push($this->idExist,$post->ID);
                        }

                        $this->productsList[$i]['id'] = $post->ID;
                        $this->productsList[$i]['variation_type'] = "simple";
                        $this->productsList[$i]['title'] = $product->get_title();
                        $this->productsList[$i]['description'] =$post->post_content;

                        $this->productsList[$i]['short_description'] = $post->post_excerpt;
                        $this->productsList[$i]['product_type'] = $this->get_product_term_list($post->ID, 'product_cat', "", ">");// $this->categories($this->parentID);//TODO
                        $this->productsList[$i]['link'] = $link;
                        $this->productsList[$i]['ex_link'] = "";
                        $this->productsList[$i]['image'] = $this->get_formatted_url($mainImage);

                        # Featured Image
                        if (has_post_thumbnail($post->ID)):
                            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($image[0]);
                        else:
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($mainImage);
                        endif;

                        # Additional Images
                        $imageLinks=array();
                        $images = $this->additionalImages($post->ID);
                        if ($images && is_array($images)) {
                            $mKey=1;
                            foreach ($images as $key => $value) {
                                if ($value != $this->productsList[$i]['image']) {
                                    $imgLink=$this->get_formatted_url($value);
                                    $this->productsList[$i]["image_$mKey"] = $imgLink;
                                    if(!empty($imgLink)){
                                        array_push($imageLinks,$imgLink);
                                    }
                                }
                                $mKey++;
                            }
                        }
                        $this->productsList[$i]['images'] = implode(",",$imageLinks);

                        $this->productsList[$i]['condition'] = "New";
                        $this->productsList[$i]['type'] = $product->get_type();
                        $this->productsList[$i]['visibility'] =$this->getAttributeValue($post->ID,"_visibility");
                        $this->productsList[$i]['rating_total'] = $product->get_rating_count();
                        $this->productsList[$i]['rating_average'] = $product->get_average_rating();
                        $this->productsList[$i]['tags'] = $this->get_product_term_list($post->ID, 'product_tag');

                        $this->productsList[$i]['item_group_id'] = $post->ID;
                        $this->productsList[$i]['sku'] = $this->getAttributeValue($post->ID,"_sku");

                        $this->productsList[$i]['availability'] = $this->availability($post->ID);
                        $this->productsList[$i]['quantity'] = $this->get_quantity($post->ID, "_stock");
                        $this->productsList[$i]['sale_price_sdate'] = $this->get_date($post->ID, "_sale_price_dates_from");
                        $this->productsList[$i]['sale_price_edate'] = $this->get_date($post->ID, "_sale_price_dates_to");
                        $this->productsList[$i]['price'] = ($product->get_regular_price()) ? $product->get_regular_price() : $product->get_price();
                        $this->productsList[$i]['sale_price'] = ($product->get_sale_price()) ? $product->get_sale_price() : "";
                        $this->productsList[$i]['weight'] = ($product->get_weight()) ? $product->get_weight() : "";
                        $this->productsList[$i]['width'] = ($product->get_width()) ? $product->get_width() : "";
                        $this->productsList[$i]['height'] = ($product->get_height()) ? $product->get_height() : "";
                        $this->productsList[$i]['length'] = ($product->get_length()) ? $product->get_length() : "";

                        # Sale price effective date
                        $from = $this->sale_price_effective_date($post->ID, '_sale_price_dates_from');
                        $to = $this->sale_price_effective_date($post->ID, '_sale_price_dates_to');
                        if (!empty($from) && !empty($to)) {
                            $from = date("c", strtotime($from));
                            $to = date("c", strtotime($to));
                            $this->productsList[$i]['sale_price_effective_date'] = "$from" . "/" . "$to";
                        } else {
                            $this->productsList[$i]['sale_price_effective_date'] = "";
                        }

                    }
                    else if($type1 == 'external'){

                        $mainImage = wp_get_attachment_url($product->get_image_id());

                        $getLink=new WC_Product_External($post->ID);
                        $EX_link = $getLink->get_product_url();
                        $link=get_permalink($post->ID);
                        if($this->feedRule['provider']!='custom'){
                            if (substr(trim($link), 0, 4) !== "http" && substr(trim($mainImage), 0, 4) !== "http") {
                                continue;
                            }
                        }

                        $this->productsList[$i]['id'] = $post->ID;
                        $this->productsList[$i]['variation_type'] = "external";
                        $this->productsList[$i]['title'] = $product->get_title();
                        $this->productsList[$i]['description'] = do_shortcode($post->post_content);

                        $this->productsList[$i]['short_description'] = $post->post_excerpt;
                        $this->productsList[$i]['product_type'] = $this->get_product_term_list($post->ID, 'product_cat', "", ">");// $this->categories($this->parentID);//TODO
                        $this->productsList[$i]['link'] = $link;
                        $this->productsList[$i]['ex_link'] = $EX_link;
                        $this->productsList[$i]['image'] = $this->get_formatted_url($mainImage);

                        # Featured Image
                        if (has_post_thumbnail($post->ID)):
                            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($image[0]);
                        else:
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($mainImage);
                        endif;

                        # Additional Images
                        $imageLinks=array();
                        $images = $this->additionalImages($post->ID);
                        if ($images && is_array($images)) {
                            $mKey=1;
                            foreach ($images as $key => $value) {
                                if ($value != $this->productsList[$i]['image']) {
                                    $imgLink=$this->get_formatted_url($value);
                                    $this->productsList[$i]["image_$mKey"] = $imgLink;
                                    if(!empty($imgLink)){
                                        array_push($imageLinks,$imgLink);
                                    }
                                }
                                $mKey++;
                            }
                        }
                        $this->productsList[$i]['images'] = implode(",",$imageLinks);

                        $this->productsList[$i]['condition'] = "New";
                        $this->productsList[$i]['type'] = $product->get_type();
                        $this->productsList[$i]['visibility'] = $this->getAttributeValue($post->ID,"_visibility");
                        $this->productsList[$i]['rating_total'] = $product->get_rating_count();
                        $this->productsList[$i]['rating_average'] = $product->get_average_rating();
                        $this->productsList[$i]['tags'] = $this->get_product_term_list($post->ID, 'product_tag');

                        $this->productsList[$i]['item_group_id'] = $post->ID;
                        $this->productsList[$i]['sku'] = $this->getAttributeValue($post->ID,"_sku");

                        $this->productsList[$i]['availability'] =  $this->availability($post->ID);

                        $this->productsList[$i]['quantity'] = $this->get_quantity($post->ID, "_stock");
                        $this->productsList[$i]['sale_price_sdate'] = $this->get_date($post->ID, "_sale_price_dates_from");
                        $this->productsList[$i]['sale_price_edate'] = $this->get_date($post->ID, "_sale_price_dates_to");
                        $this->productsList[$i]['price'] = ($product->get_regular_price()) ? $product->get_regular_price() : $product->get_price();
                        $this->productsList[$i]['sale_price'] = ($product->get_sale_price()) ? $product->get_sale_price() : "";
                        $this->productsList[$i]['weight'] = ($product->get_weight()) ? $product->get_weight() : "";
                        $this->productsList[$i]['width'] = ($product->get_width()) ? $product->get_width() : "";
                        $this->productsList[$i]['height'] = ($product->get_height()) ? $product->get_height() : "";
                        $this->productsList[$i]['length'] = ($product->get_length()) ? $product->get_length() : "";

                        # Sale price effective date
                        $from = $this->sale_price_effective_date($post->ID, '_sale_price_dates_from');
                        $to = $this->sale_price_effective_date($post->ID, '_sale_price_dates_to');
                        if (!empty($from) && !empty($to)) {
                            $from = date("c", strtotime($from));
                            $to = date("c", strtotime($to));
                            $this->productsList[$i]['sale_price_effective_date'] = "$from" . "/" . "$to";
                        } else {
                            $this->productsList[$i]['sale_price_effective_date'] = "";
                        }

                    }elseif($type1 == 'grouped'){

                        $grouped=new WC_Product_Grouped($post->ID);
                        $children=$grouped->get_children();
                        $this->parentID=$post->ID;
                        if($children){
                            foreach ($children as $cKey=>$child){

                                $product=new WC_Product($child);
                                $this->childID=$child;
                                $post=get_post($this->childID);

                                if($post->post_status=='trash'){
                                    continue;
                                }

                                if (!empty($this->ids_in) && !in_array($post->ID, $this->ids_in)) {
                                    continue;
                                }

                                if (!empty($this->ids_not_in) && in_array($post->ID, $this->ids_in)) {
                                    continue;
                                }

                                if (!$product->is_visible()) {
                                    continue;
                                }

                                $i++;

                                $mainImage = wp_get_attachment_url($product->get_image_id());
                                $link = get_permalink($post->ID);
                                if($this->feedRule['provider']!='custom'){
                                    if (substr(trim($link), 0, 4) !== "http" && substr(trim($mainImage), 0, 4) !== "http") {
                                        continue;
                                    }
                                }

                                $this->productsList[$i]['id'] = $post->ID;
                                $this->productsList[$i]['variation_type'] = "grouped";
                                $this->productsList[$i]['title'] = $product->get_title();
                                $this->productsList[$i]['description'] = do_shortcode($post->post_content);

                                $this->productsList[$i]['short_description'] = $post->post_excerpt;
                                $this->productsList[$i]['product_type'] = $this->get_product_term_list($post->ID, 'product_cat', "", ">");// $this->categories($this->parentID);//TODO
                                $this->productsList[$i]['link'] = $link;
                                $this->productsList[$i]['ex_link'] = "";
                                $this->productsList[$i]['image'] = $this->get_formatted_url($mainImage);

                                # Featured Image
                                if (has_post_thumbnail($post->ID)):
                                    $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
                                    $this->productsList[$i]['feature_image'] = $this->get_formatted_url($image[0]);
                                else:
                                    $this->productsList[$i]['feature_image'] = $this->get_formatted_url($mainImage);
                                endif;

                                # Additional Images
                                $imageLinks=array();
                                $images = $this->additionalImages($this->childID);
                                if ($images and is_array($images)) {
                                    $mKey=1;
                                    foreach ($images as $key => $value) {
                                        if ($value != $this->productsList[$i]['image']) {
                                            $imgLink=$this->get_formatted_url($value);
                                            $this->productsList[$i]["image_$mKey"] = $imgLink;
                                            if(!empty($imgLink)){
                                                array_push($imageLinks,$imgLink);
                                            }
                                        }
                                        $mKey++;
                                    }
                                }
                                $this->productsList[$i]['images'] = implode(",",$imageLinks);
                                $this->productsList[$i]['condition'] = "New";
                                $this->productsList[$i]['type'] = $product->get_type();
                                $this->productsList[$i]['visibility'] = $this->getAttributeValue($post->ID,"_visibility");
                                $this->productsList[$i]['rating_total'] = $product->get_rating_count();
                                $this->productsList[$i]['rating_average'] = $product->get_average_rating();
                                $this->productsList[$i]['tags'] = $this->get_product_term_list($post->ID, 'product_tag');

                                $this->productsList[$i]['item_group_id'] = $this->parentID;
                                $this->productsList[$i]['sku'] = $this->getAttributeValue($post->ID,"_sku");

                                $this->productsList[$i]['availability'] =  $this->availability($post->ID);

                                $this->productsList[$i]['quantity'] = $this->get_quantity($post->ID, "_stock");
                                $this->productsList[$i]['sale_price_sdate'] = $this->get_date($post->ID, "_sale_price_dates_from");
                                $this->productsList[$i]['sale_price_edate'] = $this->get_date($post->ID, "_sale_price_dates_to");
                                $this->productsList[$i]['price'] =($product->get_regular_price()) ? $product->get_regular_price() : $product->get_price();
                                $this->productsList[$i]['sale_price'] =($product->get_sale_price()) ? $product->get_sale_price() : "";
                                $this->productsList[$i]['weight'] = ($product->get_weight()) ? $product->get_weight() : "";
                                $this->productsList[$i]['width'] = ($product->get_width()) ? $product->get_width() : "";
                                $this->productsList[$i]['height'] = ($product->get_height()) ? $product->get_height() : "";
                                $this->productsList[$i]['length'] = ($product->get_length()) ? $product->get_length() : "";

                                # Sale price effective date
                                $from = $this->sale_price_effective_date($post->ID, '_sale_price_dates_from');
                                $to = $this->sale_price_effective_date($post->ID, '_sale_price_dates_to');
                                if (!empty($from) && !empty($to)) {
                                    $from = date("c", strtotime($from));
                                    $to = date("c", strtotime($to));
                                    $this->productsList[$i]['sale_price_effective_date'] = "$from" . "/" . "$to";
                                } else {
                                    $this->productsList[$i]['sale_price_effective_date'] = "";
                                }
                            }
                        }
                    }

                    else if ($type1 == 'variable' && $product->has_child()) {

                        # Check Valid URL
                        $mainImage = wp_get_attachment_url($product->get_image_id());
                        $link = get_permalink($post->ID);

                        if($this->feedRule['provider']!='custom'){
                            if (substr(trim($link), 0, 4) !== "http" && substr(trim($mainImage), 0, 4) !== "http") {
                                continue;
                            }
                        }


                        $this->productsList[$i]['id'] = $post->ID;
                        $this->productsList[$i]['variation_type'] = "parent";
                        $this->productsList[$i]['title'] = $post->post_title;
                        $this->productsList[$i]['description'] =$post->post_content;

                        $this->productsList[$i]['short_description'] = $post->post_excerpt;
                        $this->productsList[$i]['product_type'] = $this->get_product_term_list($post->ID, 'product_cat', "", ">");// $this->categories($this->parentID);//TODO
                        $this->productsList[$i]['link'] = $link;
                        $this->productsList[$i]['ex_link'] = "";
                        $this->productsList[$i]['image'] = $this->get_formatted_url($mainImage);

                        # Featured Image
                        if (has_post_thumbnail($post->ID)):
                            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($image[0]);
                        else:
                            $this->productsList[$i]['feature_image'] = $this->get_formatted_url($mainImage);
                        endif;

                        # Additional Images
                        $imageLinks=array();
                        $images = $this->additionalImages($post->ID);
                        if ($images and is_array($images)) {
                            $mKey=1;
                            foreach ($images as $key => $value) {
                                if ($value != $this->productsList[$i]['image']) {
                                    $imgLink=$this->get_formatted_url($value);
                                    $this->productsList[$i]["image_$mKey"] = $imgLink;
                                    if(!empty($imgLink)){
                                        array_push($imageLinks,$imgLink);
                                    }
                                }
                                $mKey++;
                            }
                        }
                        $this->productsList[$i]['images'] = implode(",",$imageLinks);

                        $this->productsList[$i]['condition'] = "New";
                        $this->productsList[$i]['type'] = $product->get_type();
                        $this->productsList[$i]['visibility'] = $this->getAttributeValue($post->ID,"_visibility");
                        $this->productsList[$i]['rating_total'] = $product->get_rating_count();
                        $this->productsList[$i]['rating_average'] = $product->get_average_rating();
                        $this->productsList[$i]['tags'] = $this->get_product_term_list($post->ID, 'product_tag');

                        $this->productsList[$i]['item_group_id'] = $post->ID;
                        $this->productsList[$i]['sku'] =$this->getAttributeValue($post->ID,"_sku");

                        $this->productsList[$i]['availability'] = $this->availability($post->ID);
                        $this->productsList[$i]['quantity'] = $this->get_quantity($post->ID, "_stock");
                        $this->productsList[$i]['sale_price_sdate'] = $this->get_date($post->ID, "_sale_price_dates_from");
                        $this->productsList[$i]['sale_price_edate'] = $this->get_date($post->ID, "_sale_price_dates_to");

                        $price=($product->get_price()) ? $product->get_price() : false;
                        
                        $this->productsList[$i]['price'] = ($product->get_regular_price()) ? $product->get_regular_price() : $price;
                        $this->productsList[$i]['sale_price'] =($product->get_sale_price()) ? $product->get_sale_price() : "";
                        $this->productsList[$i]['weight'] = ($product->get_weight()) ? $product->get_weight() : "";
                        $this->productsList[$i]['width'] = ($product->get_width()) ? $product->get_width() : "";
                        $this->productsList[$i]['height'] = ($product->get_height()) ? $product->get_height() : "";
                        $this->productsList[$i]['length'] = ($product->get_length()) ? $product->get_length() : "";

                        # Sale price effective date
                        $from = $this->sale_price_effective_date($post->ID, '_sale_price_dates_from');
                        $to = $this->sale_price_effective_date($post->ID, '_sale_price_dates_to');
                        if (!empty($from) && !empty($to)) {
                            $from = date("c", strtotime($from));
                            $to = date("c", strtotime($to));
                            $this->productsList[$i]['sale_price_effective_date'] = "$from" . "/" . "$to";
                        } else {
                            $this->productsList[$i]['sale_price_effective_date'] = "";
                        }
                    }
                }
                $i++;
            endwhile;
            wp_reset_query();

            if($getIDs){
                $mergedIds=array_merge($getIDs,$this->idExist);
                update_option("wf_check_duplicate",$mergedIds);
            }else{
                update_option("wf_check_duplicate",$this->idExist);
            }

            return $this->productsList;
        }catch (Exception $e){
            return $this->productsList;
        }
    }

    /**
     * Get formatted image url
     *
     * @param $url
     * @return bool|string
     */
    public function get_formatted_url($url = "")
    {
        if (!empty($url)) {
            if (substr(trim($url), 0, 4) === "http" || substr(trim($url), 0, 3) === "ftp" || substr(trim($url), 0, 4) === "sftp") {
                return rtrim($url, "/");
            } else {
                $base = get_site_url();
                $url = $base . $url;
                return rtrim($url, "/");
            }
        }
        return $url;
    }


    /**
     * Get formatted product date
     *
     * @param $id
     * @param $name
     * @return bool|string
     */
    public function get_date($id, $name)
    {
        $date = $this->getAttributeValue($id, $name);
        if ($date) {
            return date("Y-m-d", $date);
        }
        return false;
    }

    /**
     * Get formatted product quantity
     *
     * @param $id
     * @param $name
     * @return bool|mixed
     */
    public function get_quantity($id, $name)
    {
        $qty = $this->getAttributeValue($id, $name);
        if ($qty) {
            return $qty + 0;
        }
        return "0";
    }

    /**
     * Retrieve a post's terms as a list with specified format.
     *
     * @since 2.5.0
     *
     * @param int $id Post ID.
     * @param string $taxonomy Taxonomy name.
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     *
     * @return string|false|WP_Error A list of terms on success, false if there are no terms, WP_Error on failure.
     */
    function get_product_term_list($id, $taxonomy, $before = '', $sep = ',', $after = '')
    {
        $terms = get_the_terms($id, $taxonomy);

        if (is_wp_error($terms)) {
            return $terms;
        }

        if (empty($terms)) {
            return false;
        }

        $links = array();

        foreach ($terms as $term) {
            $links[] = $term->name;
        }
        ksort($links);
        return $before . join($sep, $links) . $after;
    }

    /** Return additional image URLs
     *
     * @param int $Id
     *
     * @return array
     */

    public function additionalImages($Id)
    {
        $ids=$this->getAttributeValue($Id,"_product_image_gallery");
        $imgIds=!empty($ids)?explode(",",$ids):"";

        $images = array();
        if (!empty($imgIds)) {
            foreach ($imgIds as $key => $value) {
                if ($key < 10) {
                    $images[$key] = wp_get_attachment_url($value);
                }
            }
            return $images;
        }
        return false;
    }

    /**
     * Give space to availability text
     *
     * @param integer $id
     *
     * @return string
     */
    public function availability($id)
    {
        $status=$this->getAttributeValue($id,"_stock_status");
        if ($status) {
            if ($status == 'instock') {
                return "in stock";
            } elseif ($status == 'outofstock') {
                return "out of stock";
            }
        }
        return "out of stock";
    }


    /**
     * Get Product Attribute Value
     *
     * @param $id
     * @param $name
     *
     * @return mixed
     */
    public function getAttributeValue($id, $name)
    {
        if (strpos($name, 'attribute_pa') !== false) {
            $taxonomy = str_replace("attribute_","",$name);
            $meta = get_post_meta($id,$name, true);
            $term = get_term_by('slug', $meta, $taxonomy);
            return $term->name;
        }else{
            return get_post_meta($id, $name, true);
        }

    }

    /**
     * Get Sale price effective date for google
     *
     * @param $id
     * @param $name
     * @return string
     */
    public function sale_price_effective_date($id, $name)
    {
        return ($date = $this->getAttributeValue($id, $name)) ? date_i18n('Y-m-d', $date) : "";
    }


    /**
     * Get All Default WooCommerce Attributes
     * @return bool|array
     */
    public function getAllAttributes()
    {
        global $wpdb;

        //Load the main attributes
        $sql = '
			SELECT attribute_name as name, attribute_type as type
			FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $data = $wpdb->get_results($sql);
        if (count($data)) {
            foreach ($data as $key => $value) {
                $info["wf_attr_pa_" . $value->name] = $value->name;
            }
            return $info;
        }
        return false;
    }


    /**
     * Get All Custom Attributes
     * @return array|bool
     */
    public function getAllCustomAttributes()
    {
        global $wpdb;
        $info = array();
        //Load the main attributes
        $sql = "SELECT meta_key as name, meta_value as type
			FROM " . $wpdb->prefix . "postmeta" . "  group by meta_key";
        $data = $wpdb->get_results($sql);
        if ($data) {
            foreach ($data as $key => $value) {
                if (substr($value->name, 0, 1) !== "_") { //&& substr($value->name, 0, 13) !== "attribute_pa_"
                    $info["wf_cattr_" . $value->name] = $value->name;
                }
            }
            return $info;
        }
        return false;
    }

    /**
     * Get All Taxonomy
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAllTaxonomy($name = "color")
    {
        global $wpdb;
        //Load the taxonomies
        $info = false;

        $sql = "SELECT taxo.taxonomy, terms.name, terms.slug FROM $wpdb->term_taxonomy taxo
			LEFT JOIN $wpdb->terms terms ON (terms.term_id = taxo.term_id) GROUP BY taxo.taxonomy";
        $data = $wpdb->get_results($sql);
        if (count($data)) {
            foreach ($data as $key => $value) {
                $info["wf_taxo_" . $value->taxonomy] = $value->taxonomy;
            }
        }
        return $info;
    }

    /**
     * Get Category Mappings
     * @return bool|array
     */
    public function getCustomCategoryMappedAttributes()
    {
        global $wpdb;

        //Load Custom Category Mapped Attributes
        $var = "wf_cmapping_";
        $sql = $wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name LIKE %s;", $var . "%");
        $data = $wpdb->get_results($sql);
        if (count($data)) {
            foreach ($data as $key => $value) {
                $info[$key] = $value->option_name;
            }

            return $info;
        }

        return false;
    }

    /**
     * Get Dynamic Attribute List
     * @return bool|array
     */
    public function dynamicAttributes()
    {
        global $wpdb;

        # Load Custom Category Mapped Attributes
        $var = "wf_dattribute_";
        $sql = $wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name LIKE %s;", $var . "%");
        $data = $wpdb->get_results($sql);
        if (count($data)) {
            foreach ($data as $key => $value) {
                $info[$key] = $value->option_name;
            }
            return $info;
        }
        return false;
    }

    /**
     * Local Attribute List to map product value with merchant attributes
     *
     * @param string $selected
     *
     * @return string
     */
    public function attributeDropdown($selected = "")
    {
        $attributes = array(
            "id" => "Product Id",
            "title" => "Product Title",
            "description" => "Product Description",
            "short_description" => "Product Short Description",
            "product_type" => "Product Local Category",
            "link" => "Product URL",
            "ex_link" => "External Product URL",
            "condition" => "Condition",
            "item_group_id" => "Parent Id [Group Id]",
            "sku" => "SKU",
            "parent_sku" => "Parent SKU",
            "availability" => "Availability",
            "quantity" => "Quantity",
            "price" => "Regular Price",
            "sale_price" => "Sale Price",
            "sale_price_sdate" => "Sale Start Date",
            "sale_price_edate" => "Sale End Date",
            "weight" => "Weight",
            "width" => "Width",
            "height" => "Height",
            "length" => "Length",
            "type" => "Product Type",
            "variation_type" => "Variation Type",
            "visibility" => "Visibility",
            "rating_total" => "Total Rating",
            "rating_average" => "Average Rating",
            "tags" => "Tags",
            "sale_price_effective_date" => "Sale Price Effective Date",
        );

        $images = array(
            "image" => "Main Image",
            "feature_image" => "Featured Image",
            "images" => "Images [Comma Separated]",
            "image_1" => "Additional Image 1",
            "image_2" => "Additional Image 2",
            "image_3" => "Additional Image 3",
            "image_4" => "Additional Image 4",
            "image_5" => "Additional Image 5",
            "image_6" => "Additional Image 6",
            "image_7" => "Additional Image 7",
            "image_8" => "Additional Image 8",
            "image_9" => "Additional Image 9",
            "image_10" => "Additional Image 10",
        );

        # Primary Attributes
        $str = "<option></option>";
        $sltd = "";
        $str .= "<optgroup label='Primary Attributes'>";
        foreach ($attributes as $key => $value) {
            $sltd = "";
            if ($selected == $key) {
                $sltd = 'selected="selected"';
            }
            $str .= "<option $sltd value='$key'>" . $value . "</option>";
        }
        $str .= "</optgroup>";

        # Additional Images
        if ($images) {
            $str .= "<optgroup label='Image Attributes'>";
            foreach ($images as $key => $value) {
                $sltd = "";
                if ($selected == $key) {
                    $sltd = 'selected="selected"';
                }
                $str .= "<option $sltd value='$key'>" . $value . "</option>";
            }
            $str .= "</optgroup>";
        }

        # Get All WooCommerce Attributes
        $vAttributes = $this->getAllAttributes();
        if ($vAttributes) {
            $str .= "<optgroup label='Product Attributes'>";
            foreach ($vAttributes as $key => $value) {
                $sltd = "";
                if ($selected == $key) {
                    $sltd = 'selected="selected"';
                }
                $str .= "<option $sltd value='$key'>" . $value . "</option>";
            }
            $str .= "</optgroup>";
        }

        # Get All Custom Attributes
        $customAttributes = $this->getAllCustomAttributes();
        if ($customAttributes) {
            $str .= "<optgroup label='Variation & Custom Attributes'>";
            foreach ($customAttributes as $key => $value) {
                if (strpos($value, 0, 1) != "_") {
                    $sltd = "";
                    if ($selected == $key) {
                        $sltd = 'selected="selected"';
                    }
                    $str .= "<option $sltd value='$key'>" . $value . "</option>";
                }
            }
            $str .= "</optgroup>";
        }
        return $str;
    }

    /**
     * Load all WooCommerce attributes into an option
     */
    public function load_attributes()
    {
        # Get All WooCommerce Attributes
        $vAttributes = $this->getAllAttributes();
        update_option("wpfw_vAttributes", $vAttributes);

        # Get All Custom Attributes
        $customAttributes = $this->getAllCustomAttributes();
        update_option("wpfw_customAttributes", $customAttributes);
    }

    /**
     * Local Attribute List to map product value with merchant attributes
     *
     * @param string $selected
     *
     * @return string
     */
    public function loadAttributeDropdown($selected = "")
    {
        $attributes = array(
            "id" => "Product Id",
            "title" => "Product Title",
            "description" => "Product Description",
            "short_description" => "Product Short Description",
            "product_type" => "Product Local Category",
            "link" => "Product URL",
            "ex_link" => "External Product URL",
            "condition" => "Condition",
            "item_group_id" => "Parent Id [Group Id]",
            "sku" => "SKU",
            "parent_sku" => "Parent SKU",
            "availability" => "Availability",
            "quantity" => "Quantity",
            "price" => "Regular Price",
            "sale_price" => "Sale Price",
            "sale_price_sdate" => "Sale Start Date",
            "sale_price_edate" => "Sale End Date",
            "weight" => "Weight",
            "width" => "Width",
            "height" => "Height",
            "length" => "Length",
            "type" => "Product Type",
            "variation_type" => "Variation Type",
            "visibility" => "Visibility",
            "rating_total" => "Total Rating",
            "rating_average" => "Average Rating",
            "tags" => "Tags",
            "sale_price_effective_date" => "Sale Price Effective Date",
        );

        $images = array(
            "image" => "Main Image",
            "feature_image" => "Featured Image",
            "images" => "Images [Comma Separated]",
            "image_1" => "Additional Image 1",
            "image_2" => "Additional Image 2",
            "image_3" => "Additional Image 3",
            "image_4" => "Additional Image 4",
            "image_5" => "Additional Image 5",
            "image_6" => "Additional Image 6",
            "image_7" => "Additional Image 7",
            "image_8" => "Additional Image 8",
            "image_9" => "Additional Image 9",
            "image_10" => "Additional Image 10",
        );

        # Primary Attributes
        $str = "<option></option>";
        $sltd = "";
        $str .= "<optgroup label='Primary Attributes'>";
        foreach ($attributes as $key => $value) {
            $sltd = "";
            if ($selected == $key) {
                $sltd = 'selected="selected"';
            }
            $str .= "<option $sltd value='$key'>" . $value . "</option>";
        }
        $str .= "</optgroup>";

        # Additional Images
        if ($images) {
            $str .= "<optgroup label='Image Attributes'>";
            foreach ($images as $key => $value) {
                $sltd = "";
                if ($selected == $key) {
                    $sltd = 'selected="selected"';
                }
                $str .= "<option $sltd value='$key'>" . $value . "</option>";
            }
            $str .= "</optgroup>";
        }

        # Get All WooCommerce Attributes
        $vAttributes = get_option("wpfw_vAttributes");
        if ($vAttributes) {
            $str .= "<optgroup label='Product Attributes'>";
            foreach ($vAttributes as $key => $value) {
                $sltd = "";
                if ($selected == $key) {
                    $sltd = 'selected="selected"';
                }
                $str .= "<option $sltd value='$key'>" . $value . "</option>";
            }
            $str .= "</optgroup>";
        }

        # Get All Custom Attributes
        $customAttributes = get_option("wpfw_customAttributes");
        if ($customAttributes) {
            $str .= "<optgroup label='Variation & Custom Attributes'>";
            foreach ($customAttributes as $key => $value) {
                $sltd = "";
                if ($selected == $key) {
                    $sltd = 'selected="selected"';
                }
                $str .= "<option $sltd value='$key'>" . $value . "</option>";
            }
            $str .= "</optgroup>";
        }
        return $str;
    }

    /**
     * Check WooCommerce Version
     * @param string $version
     * @return bool
     */
    public static function version_check( $version = '3.0' ) {
        if ( class_exists( 'WooCommerce' ) ) {
            global $woocommerce;
            if ( version_compare( $woocommerce->version, $version, ">=" ) ) {
                return true;
            }
        }
        return false;
    }
}