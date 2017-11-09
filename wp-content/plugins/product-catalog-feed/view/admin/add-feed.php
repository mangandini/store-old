<div class="wpwoof-box">
    <div class="wpwoof-addfeed-top">

        <div class="addfeed-top-field"><p>
            <label class="addfeed-top-label addfeed-bigger">Feed Name</label>
            <span class="addfeed-top-value">
                <input type="text" name="feed_name" value="<?php echo isset($wpwoof_values['feed_name']) ? $wpwoof_values['feed_name'] : ''; ?>" />
                <?php if( !empty($wpwoofeed_oldname) ) { ?>
                    <input type="hidden" name="old_feed_name" value="<?php echo $wpwoofeed_oldname; ?>" style="display:none" />
                <?php } ?>
            </span>
        </p></div>
        <div class="addfeed-top-field"><p>
            <label class="addfeed-top-label addfeed-bigger">Feed Type</label>
            <span class="addfeed-top-value">
                <select name="feed_type">
                    <option <?php if(isset($wpwoof_values['feed_type'])) { selected( "all", $wpwoof_values['feed_type'], true); } ?> value="all">Facebook Product Catalog</option>
                </select>
            </span>
        </p></div>
    </div>
    <hr class="wpwoof-break" />

    <div class="wpwoof-addfeed-fields">
        <h4 class="wpwoofeed-section-heading">Field Mapping</h4>
        <p>We do our best to do the mapping automatically, but if there something you want to change, use the dropdown to do the re-map.</p>
        <?php  
        $all_fields = wpwoof_get_all_fields();

        $required_fields = $all_fields['required'];
        $extra_fields = $all_fields['extra'];

        $condition_fields = array();
        $meta_keys = wpwoof_get_product_fields();
        $meta_keys_sort = wpwoof_get_product_fields_sort();
        $attributes = wpwoof_get_all_attributes();
        foreach ($required_fields as $fieldkey => $field) {
            if( !empty($field['delimiter']) ) {
                echo '<hr class="wpwoof-break wpwoof-break-small"/>';
            }
            if( !isset($field['define']) ) { ?>
            <div class="wpwoof-requiredfield-settings wpwoof-field-wrap">
                <p>
                    <label class="wpwoof-required-label"><?php echo $fieldkey ?>:</label>
                    <span class="wpwoof-required-value">
                        <select name="field_mapping[<?php echo $fieldkey; ?>][value]" class="wpwoof_mapping wpwoof_mapping_option">
                        <?php 
                        $html = '';
                            if( isset($field['woocommerce_default']) ) {
                                if( empty( $wpwoof_values['field_mapping'][$fieldkey]['value'] ) ) {
                                    if( empty($wpwoof_values['field_mapping']) || !is_array($wpwoof_values['field_mapping']) ) {
                                        $wpwoof_values['field_mapping'] = array();
                                    }
                                    if( empty($wpwoof_values['field_mapping'][$fieldkey]) || !is_array($wpwoof_values['field_mapping'][$fieldkey]) ) {
                                        $wpwoof_values['field_mapping'][$fieldkey] = array();
                                    }
                                    $wpwoof_values['field_mapping'][$fieldkey]['value'] = 'wpwoofdefa_'.$field['woocommerce_default']['value'];
                                }
                            } else {
                                $html .= '<option value="">select</option>';
                            }
                            $meta_keys_remove = $meta_keys;
                            foreach($meta_keys_sort['sort'] as $sort_id => $meta_fields) {
                                $html .= '<optgroup label="'.$meta_keys_sort['name'][$sort_id].'">';
                                foreach($meta_fields as $key) {
                                    $value = $meta_keys[$key];
                                    unset($meta_keys_remove[$key]);
                                    $html .= '<option value="wpwoofdefa_'.$key.'" '.selected('wpwoofdefa_'.$key, $wpwoof_values['field_mapping'][$fieldkey]['value'], false).' >'.$value['label'].'</option>';
                                }
                                $html .= '</optgroup>';
                            }
                            $html .= '<optgroup label="Product Attributes">';
                            foreach ($attributes as $key => $value) {
                                $html .= '<option value="wpwoofattr_'.$key.'" '.selected('wpwoofattr_'.$key, $wpwoof_values['field_mapping'][$fieldkey]['value'], false).' >'.$value.'</option>';
                            }
                            $html .= '</optgroup>';
                            echo $html;
                        ?>
                        </select>
                        <?php wpwoofeed_custom_attribute_input($fieldkey, $field, $wpwoof_values); ?>
                    </span>
                </p>
                <p class="description"><span></span><span><?php echo $field['desc']; ?></span></p>
                <?php 
                if( !empty($field['callback']) && function_exists($field['callback']) ) {
                    $field['callback']($fieldkey, $field, $wpwoof_values);
                }
                ?>
            </div>
            <?php } else {
                $condition_fields[$fieldkey] = $field;
            }
        } ?>

        <?php foreach ($condition_fields as $definekey => $definevalue) { ?>

            <div class="wpwoof-definefield-top">
                <label class="wpwoofeed-section-heading"><?php echo $definevalue['define']['label']; ?></label>
                <p><?php echo $definevalue['define']['desc']; ?></p>
            </div>
            <div class="wpwoof-definefield-settings wpwoof-field-wrap">
                <p>
                <?php
                if( isset($definevalue['woocommerce_default']) && $definekey == "brand" ) {
                    $html2 = '';     
                    $html = '';     
                    $post_type = "product";
                    $check = 0;
                    $default_value = '';
                    foreach( $attributes as $taxonomy_name => $value ) {
                        if( ($taxonomy_name != 'product_cat') && ($taxonomy_name != 'product_tag') && ($taxonomy_name != 'product_type') 
                        && ($taxonomy_name != 'product_shipping_class') ) {
                            if( ! $default_value ) {
                                $default_value = 'wpwoofattr_'.$taxonomy_name;
                            }
                            if( strpos($taxonomy_name, "brand") !== false ) {
                                $default_value = 'wpwoofattr_'.$taxonomy_name;
                            }
                        }
                    }
                    if( empty( $wpwoof_values['field_mapping'][$definekey]['value'] ) ) {
                        if( empty($wpwoof_values['field_mapping']) || !is_array($wpwoof_values['field_mapping']) ) {
                            $wpwoof_values['field_mapping'] = array();
                        }
                        if( empty($wpwoof_values['field_mapping'][$definekey]) || !is_array($wpwoof_values['field_mapping'][$definekey]) ) {
                            $wpwoof_values['field_mapping'][$definekey] = array();
                        }
                        $wpwoof_values['field_mapping'][$definekey]['value'] = $default_value;
                    }
                    foreach( $attributes as $taxonomy_name => $value ) {
                        $check = 1;
                        $val = '';
                        if( isset($wpwoof_values['field_mapping'][$definekey]['value']) )
                            $val = $wpwoof_values['field_mapping'][$definekey]['value'];
                        $html2 .= '<option value="wpwoofattr_'.$taxonomy_name.'" '.selected('wpwoofattr_'.$taxonomy_name, $val, false).'>'.$value.'</option>'; 
                    }
                    $html2 = '<optgroup label="Product Attributes">'.$html2.'</optgroup>';
                    $meta_keys = wpwoof_get_product_fields();
                    $meta_keys_remove = $meta_keys;
                    foreach($meta_keys_sort['sort'] as $sort_id => $meta_fields) {
                        $html .= '<optgroup label="'.$meta_keys_sort['name'][$sort_id].'">';
                        foreach($meta_fields as $key) {
                            $value = $meta_keys[$key];
                            unset($meta_keys_remove[$key]);
                            $html .= '<option value="wpwoofdefa_'.$key.'" '.selected('wpwoofdefa_'.$key, (empty($wpwoof_values['field_mapping'][$definekey]['value']) ? '' : $wpwoof_values['field_mapping'][$definekey]['value']), false).' >'.$value['label'].'</option>';
                        }
                        $html .= '</optgroup>';
                    } ?>
                        <label class="wpwoof-define-label"><?php echo $definekey; ?>:</label>
                        <span class="wpwoof-define-value">
                            <select name="field_mapping[<?php echo $definekey ?>][value]" class="wpwoof_mapping_option">
                                <?php echo $html, $html2; ?>
                            </select>
                        </span>
                    <?php
                }

                if($definekey != "brand") { ?>
                    <label class="wpwoof-define-label"><?php echo $definekey; ?>:</label>
                    <span class="wpwoof-define-value">
                        <select name="field_mapping[<?php echo $definekey ?>][value]" class="wpwoof_mapping_option">
                            <?php 
                            $html = '';
                            if( isset($definevalue['woocommerce_default']) ) {
                                if( empty( $wpwoof_values['field_mapping'][$fieldkey]['value'] ) ) {
                                    if( empty($wpwoof_values['field_mapping']) || !is_array($wpwoof_values['field_mapping']) ) {
                                        $wpwoof_values['field_mapping'] = array();
                                    }
                                    if( empty($wpwoof_values['field_mapping'][$fieldkey]) || !is_array($wpwoof_values['field_mapping'][$fieldkey]) ) {
                                        $wpwoof_values['field_mapping'][$fieldkey] = array();
                                    }
                                    $wpwoof_values['field_mapping'][$fieldkey]['value'] = 'wpwoofdefa_'.$definevalue['woocommerce_default']['value'];
                                }
                            } else {
                                $html .= '<option value="">select</option>';
                            }

                            $meta_keys = wpwoof_get_product_fields();
                            $meta_keys_remove = $meta_keys;
                            foreach($meta_keys_sort['sort'] as $sort_id => $meta_fields) {
                                $html .= '<optgroup label="'.$meta_keys_sort['name'][$sort_id].'">';
                                foreach($meta_fields as $key) {
                                    $value = $meta_keys[$key];
                                    unset($meta_keys_remove[$key]);
                                    $html .= '<option value="wpwoofdefa_'.$key.'" '.selected('wpwoofdefa_'.$key, $wpwoof_values['field_mapping'][$fieldkey]['value'], false).' >'.$value['label'].'</option>';
                                }
                                $html .= '</optgroup>';
                            }
                            $attributes = wpwoof_get_all_attributes();
                            $html .= '<optgroup label="Product Attributes">';
                            foreach ($attributes as $key => $value) {
                                $html .= '<option value="wpwoofattr_'.$key.'" '.selected( 'wpwoofattr_'.$key, $wpwoof_values['field_mapping'][$definekey]['value'], false).' >'.$value.'</option>';
                            }
                            $html .= '</optgroup>';
                            echo $html;
                            ?>
                        </select>
                    </span>
                <?php
                } ?>
                </p>
                <p>
                    <span class="wpwoof-define-label">Use this when <?php echo $definekey; ?> is missing:</span>
                    <?php if( isset($definevalue['define']['values']) ) { ?>    
                        <span class="wpwoof-define-value not_in_free">
                            <select>
                                <?php 
                                $pieces = explode(',', $definevalue['define']['values']);
                                foreach ($pieces as $piecekey => $piecevalue) {
                                    echo '<option 
                                     value="'.$piecevalue.'">
                                    '.$piecevalue.'
                                    </option>';
                                }
                                ?>
                            </select>
                        </span>
                        <span class="unlock_pro_features">PRO Option: <a target="_blank" href="http://www.pixelyoursite.com/product-catalog-facebook">Click to Upgrade</a></span>
                    <?php }  else { ?>
                        <span class="wpwoof-define-value not_in_free">
                            <input type="text" />
                        </span>
                        <span class="unlock_pro_features">PRO Option: <a target="_blank" href="http://www.pixelyoursite.com/product-catalog-facebook">Click to Upgrade</a></span>
                    <?php  } ?>
                </p>
                <p style="text-align: center;"><b>OR define a global value</b></p>
                <p>
                    <span class="wpwoof-define-label wpwoof-defineg-label not_in_free">
                        <input type="checkbox" value="1"/>Global <?php echo $definekey; ?> is:
                    </span>
                    <?php if( isset($definevalue['define']['values']) ) { ?>    
                        <span class="wpwoof-define-value not_in_free">
                        <select>
                            <?php 
                            $pieces = explode(',', $definevalue['define']['values']);
                            foreach ( $pieces as $piecekey => $piecevalue ) {
                                echo '<option value="'.$piecevalue.'">
                                '.$piecevalue.'
                                </option>';
                            }
                            ?>
                        </select>
                        </span>
                        <span class="unlock_pro_features">PRO Option: <a target="_blank" href="http://www.pixelyoursite.com/product-catalog-facebook">Click to Upgrade</a></span>
                    <?php } else { ?>
                        <span class="wpwoof-define-value not_in_free">
                            <input type="text" value="" />
                        </span>
                        <span class="unlock_pro_features">PRO Option: <a target="_blank" href="http://www.pixelyoursite.com/product-catalog-facebook">Click to Upgrade</a></span>
                    <?php } ?>
                </p>
            </div>
            <hr class="wpwoof-break wpwoof-break-small"/>
        <?php } ?>

        <div class="addfeed-top-field">
            <strong class="wpwoofeed-section-heading" style="font-size:1.4em;display: block;text-align:center;">Google Product Taxonomy - optional</strong>
            <p>
                <label class="addfeed-top-label" title='If you leave this blank you might see the following warning when creating the Product Catalog: "The following products were uploaded but have issues that might impact ads: Without google_product_category information, your products may not appear the way you want them to in ads.'>
                    Select Google Product Taxonomy <br>
                    <a href="https://support.google.com/merchants/answer/160081" target="_blank" title='If you leave this blank you might see the following warning when creating the Product Catalog: "The following products were uploaded but have issues that might impact ads: Without google_product_category information, your products may not appear the way you want them to in ads.'>Help</a>
                    <input type="hidden" name="feed_google_category" value="" id="feed_google_category" />
                    <input type="hidden" name="feed_google_category_id" value="" id="feed_google_category_id" />
                </label>
                
                <span class="addfeed-top-value">
                    <input type="text" name="wpwoof_google_category" id="wpwoof_google_category" style='display:none;' />
                </span>
            </p>
        </div>
    </div>
    <hr class="wpwoof-break" />

    <div class="wpwoof-addfeed-button">
        <div class="wpwoof-addfeed-button-inner">
            <!-- <p><b>Important:</b> The Free version is limited to 50 products</p> -->
            <!-- <p><b>Upgrade Now:</b> <a href="http://www.pixelyoursite.com/product-catalog-facebook" target="_blank"><b class="wpwoof-clr-orange wpwoof-15p">Click here for a big discount</b></a></p> -->
            <p class="wpwoof-action-buttons">
                <input <?php if( !isset($_REQUEST['edit']) || empty($_REQUEST['edit']) ) echo 'style="width:100%;" '; ?>type="submit" name="wpwoof-addfeed-submit" class="wpwoof-button wpwoof-button-blue" value="<?php echo $wpwoof_add_button; ?>" />
                <?php  if( isset($_REQUEST['edit']) && !empty($_REQUEST['edit']) ) { ?>
                    <a href="<?php menu_page_url('wpwoof-settings'); ?>" class="wpwoof-button">Back</a>
                <?php } ?>
            </p>
        </div>
    </div>
        
    <?php if( isset($_REQUEST['edit']) && !empty($_REQUEST['edit']) ) { ?>
        <input type="hidden" name="edit_feed" value="<?php echo $_REQUEST['edit']; ?>">
    <?php } ?>
</div>
<div class="wpwoof-content-bottom wpwoof-box">
    <h2>Are you doing Facebook Ads? Get our <span class="red_color">FREE</span> Facebook Pixel Guide</h2>
    <div><strong>After more than 10 000 users and many hours spent on answering questions, we decided to make a comprehensive guide about the new Facebook Pixel.</strong></div>
    <div><strong>And then give it to you for free.</strong></div>
    <p><a target="_blank" href="http://www.pixelyoursite.com/facebook-pixel-pdf-guide" class="wpwoof-button wpwoof-button-red">CLICK TO GET THE FREE GUIDE</a></p>
    <div>Download the Facebook Pixel FREE Guide, because we answer to all the essential questions (and some more)</div>
</div>
<div class="wpwoof-box">
    <h4 class="wpwoofeed-section-heading">Feed Options (PRO)</h4>

    <div class="wpwoof-addfeed-top">

        <div class="addfeed-top-field wpwoof-open-popup-wrap">
            <p>
                <label class="addfeed-top-label">Filter by Category</label>
                <span class="addfeed-top-value not_in_free">
                    <a href="#chose_categories" class="wpwoof-button wpwoof-button-blue wpwoof-open-popup" id="wpwoof-select-categories">Chose WooCommerce Categories for this Feed</a>
                </span>
            </p>
        </div>

        <div class="addfeed-top-field" >
            <p>
                <label class="addfeed-top-label">Filter by Tags</label>
                <span class="addfeed-top-value not_in_free">
                    <textarea></textarea>
                </span>
            </p>
            <p class="description"><span></span><span>Add multiple tags separated by comma.</span></p>
        </div>

        <div class="addfeed-top-field" >
            <p>
                <label class="addfeed-top-label">Filter by Sale</label>
                <span class="addfeed-top-value not_in_free">
                    <select>
                        <option value="all">All Products</option>
                        <option value="sale">Only products on sale</option>
                        <option value="notsale">Only products not on sale</option>
                    </select>
                </span>
            </p>
            <p class="description"><span></span><span>Select all products, only sale products or only non sale products.</span></p>
        </div>

        <div class="addfeed-top-field wpwoof-open-popup-wrap">
            <p>
                <label class="addfeed-top-label">Filter by Product type</label>
                <span class="addfeed-top-value not_in_free">
                    <a href="#chose_product_type" class="wpwoof-button wpwoof-button-blue wpwoof-open-popup" id="wpwoof-select-product_type">Chose WooCommerce Product type for this Feed</a>
                </span>
            </p>
        </div>

        <div class="addfeed-top-field" >
            <p>
                <label class="addfeed-top-label">Filter by Stock</label>
                <span class="addfeed-top-value not_in_free">
                    <select>
                        <option value="all">All Products</option>
                        <option value="instock">Only in stock</option>
                        <option value="outofstock">Only out of stock</option>
                    </select>
                </span>
            </p>
        </div>

        <hr class="wpwoof-break wpwoof-break-small"/>

        <div class="addfeed-top-field" >
            <p>
                <label class="addfeed-top-label">Variable Product Price</label>
                <span class="addfeed-top-value not_in_free">
                    <select>
                        <option value="small">Smaller Price</option>
                        <option value="big">Bigger Price</option>
                        <option value="first">First Variation Price</option>
                    </select>
                </span>
            </p>
            <p class="description"><span></span><span>Select which price to be use for main product when there are variations.</span></p>
        </div>

        <div class="addfeed-top-field wpwoof-open-popup-wrap">
            <p>
                <label class="addfeed-top-label">Send feed via FTP</label>
                <span class="addfeed-top-value not_in_free">
                    <a href="#chose_product_type" class="wpwoof-button wpwoof-button-blue wpwoof-open-popup" id="wpwoof-select-product_type">Setup FTP settings</a>
                </span>
            </p>
        </div>
        <div style="float:right;"><span class="unlock_pro_features">Unlock all PRO features: <a target="_blank" href="http://www.pixelyoursite.com/product-catalog-facebook">Click here for a discount</a></span></div>
        <div style="clear: both;"></div>

    </div>
    <?php
    $taxSrc = admin_url('admin-ajax.php');
    $taxSrc = add_query_arg( array( 'action'=>'wpwoofgtaxonmy'), $taxSrc);

    $google_cats = '';
    if(isset($wpwoof_values['feed_google_category_id']))
        $google_cats = $wpwoof_values['feed_google_category_id'];
    if( strpos($google_cats, ',') !== false ) {
        $google_cats = explode(',', $google_cats);
        $preselect = '';
        if(!empty($google_cats) && is_array($google_cats)){
            foreach ($google_cats as $google_cat) {
                $preselect .= "'".$google_cat."', ";
            }
            $preselect = rtrim($preselect, ', ');
        }
    } else {
        $preselect = "'$google_cats'";
    }
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    var options = {
        empty_value: 'null',
        indexed: true,  // the data in tree is indexed by values (ids), not by labels
        on_each_change: '<?php echo $taxSrc; ?>', // this file will be called with 'id' parameter, JSON data must be returned
        choose: function(level) {
                    if( level < 1 )
                        return 'Select Main Category';
                    else 
                        return 'Select Sub Category';
                },
        loading_image: '<?php echo home_url( '/wp-includes/images/wpspin.gif');?>',
        get_parent_value_if_empty: true,
        set_value_on: 'each',
        preselect: {'wpwoof_google_category': [<?php echo $preselect; ?>]}
    };

    var displayParents = function() {
        var labels = []; // initialize array
        var IDs = []; // initialize array
        $(this).siblings('select') // find all select
        .find(':selected') // and their current options
        .each(function() { 

        if( $(this).text() != 'Select Main Category' &&  $(this).text() != 'Select Sub Category'){     
            if( $(this).val() != ''){
                labels.push($(this).text()); 
                IDs.push($(this).val()); 
            }
        }

        }); // and add option text to array
        $('#wpwoof_google_category_result').text(labels.join(' > ')); // and display the labels
        $('#feed_google_category').val( labels.join(' > ') );
        $('#feed_google_category_id').val( IDs.join(',') );
    }

    $.getJSON('<?php echo $taxSrc; ?>', function(tree) { // initialize the tree by loading the file first
        $('#wpwoof_google_category').optionTree(tree, options).change(displayParents);
    });
});
</script>
</div>