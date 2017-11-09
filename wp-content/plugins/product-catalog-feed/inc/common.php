<?php

/**
 * Common class.
 *
 * Holds the config about what fields are available.
 */
class WoocommerceWpwoofCommon {

	private $settings = array();
	private $category_cache = array();
	public $product_fields = array();
    public $fields_organize = array(
        'general' => array(
            'id',
            'item_group_id',
            'title',
            'description',
            'link',
            'availability',
            'product_type',
        ),
        'price' => array(
            'price',
            'sale_price',
            'sale_price_effective_date',
        ),
        'shipping' => array(
            'shipping',
            'shipping_weight',
            'shipping_size',
        ),
        'additional_data' => array(
            'brand',
            'mpn',
        ),
        'additional_images' => array(
            'image_link',
            'product_image',
            'additional_image_link_1',
            'additional_image_link_2',
            'additional_image_link_3',
            'additional_image_link_4',
            'additional_image_link_5',
            'additional_image_link_6',
            'additional_image_link_7',
            'additional_image_link_8',
            'additional_image_link_9',
            'additional_image_link_10',
        ),
    );
    public $fields_organize_name = array(
        'general' => 'Products',
        'price' => 'Price',
        'shipping' => 'Shipping',
        'additional_data' => 'Additional',
        'additional_images' => 'Product images',
        'custom_label' => 'Custom labels',
    );

	/**
	 * Constructor - set up the available product fields
	 *
	 * @access public
	 */
	function __construct() {

		$this->product_fields = array(
			'id' => array(
				'label' 		=> __('ID', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The Product ID. If there are multiple instances of the same ID, all of those entries will be ignored.', 'woocommerce_wpwoof' ) ,
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 100,
				'woocommerce_default' =>array('label' => 'ID', 'value' => 'id'),
			),

			'item_group_id' => array(
				'label' 		=> __('Group ID', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Is this item a variant of a product? If so, all of the items in a group should share an item_group_id.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Group ID', 'value' => 'item_group_id'),
			),

			'title' => array(
				'label' 		=> __('Title', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The title of the product.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'callback'		=> 'wpwoof_render_title',
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 150,
                'delimiter'     => true,
				'woocommerce_default' =>array('label' => 'Title', 'value'=>'title'),
                'additional_options'  => array('uc_every_first' => '')
			),

			'product_type' => array(
				'label' 		=> __('Product Type', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The retailer-defined category of the product as a string.', 'woocommerce_wpwoof' ),
				'value'			=> true,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 100,
				'woocommerce_default' =>array('label' => 'Woo Prod Categories', 'value'=>'product_type')
			),

			'description' => array(
				'label' 		=> __('Description', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Description of the product.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'callback'		=> 'wpwoof_render_description',
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 10000,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Description', 'value' => 'description'),
                'additional_options'  => array('use_child' => '1')
			),

			'link' => array(
				'label' 		=> __('Link', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Link to the merchant’s site where you can buy the item.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Link', 'value' => 'link'),
			),

			'image_link' => array(
				'label' 		=> __('Featured image', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Link to an image of the item. This is the image used in the feed.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Featured image', 'value'=>'image_link'),
			),

			'availability' => array(
				'label' 		=> __('Availability', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Whether or not the item is in stock.', 'woocommerce_wpwoof' ),
				'value'			=> 'in stock,out of stock,preorder,available for order',
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Availability', 'value' => 'availability'),
			),

			'price' => array(
				'label' 		=> __('Price', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The cost of the product and currency', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
                'delimiter'     => true,
				'woocommerce_default' =>array('label' => 'Price', 'value'=>'price'),
			),

			'sale_price' => array(
				'label' 		=> __('Sale Price', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The discounted price if the item is on sale.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Sale Price', 'value' => 'sale_price'),
			),

			'sale_price_effective_date' => array(
				'label' 		=> __('Sale Price Effective Date', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The start and end date/time of the sale, separated by slash.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Sale Price Effective Date', 'value'=>'sale_price_effective_date'),
			),

			'gtin' => array(
				'label' 		=> __('GTIN', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The Global Trade Item Number (GTINs) can include UPC, EAN, JAN and ISBN.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 100,
                'delimiter'     => true,
			),

			'mpn' => array(
				'label' 		=> __('MPN', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The number which uniquely identifies the product to its manufacturer.', 'woocommerce_wpwoof' ),
				'value'			=> true,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 100,
				'woocommerce_default' =>array('label' => 'ID', 'value' => 'id'),
			),



			'product_image' => array(
				'label' 		=> __('Product image', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Product image', 'value'=>'product_image'), 
			),

			'brand' => array(
				'label' 		=> __('Brand', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The name of the brand.', 'woocommerce_wpwoof' ),
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 100,
				'woocommerce_defalut' =>false,
				'define' => array(
						'label' => 'Brand Options:',
						'desc'	=> 'Brand is a requered fled. You can use the brand from your WooCommerce products and define a value for when brand is missing, or you can set a global value that will be used for all the products in this feed',
						'value' => '',
						),
				'woocommerce_default' =>array('label' => 'Brand', 'value'=>'brand'),
			),

			'condition' => array(
				'label' 		=> __('Condition', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The condition of the product.', 'woocommerce_wpwoof' ),
                'value'			=> false,
				'required'		=> true,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'define' => array(
						'label' => 'Define Condition',
						'desc'	=> 'Condition is a required field and you have a few options. You can use the conditions from your WooCommerce products and define a value for when condition is missing (it will be used just for products that don\'t have condition), or you can set a global value that will be used for all the products in this feed',
						'values' => 'new,refurbished,used',
						),
			),

/*			'google_product_category' => array(
				'label' 		=> __('Group ID', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Product Category.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 250,
				'woocommerce_default' =>array('label' => 'google_product_category', 'value'=>'google_product_category'),
			),
*/
			'additional_image_link_1' => array(
				'label' 		=> __('Additional Image Link 1', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 1', 'value'=>'additional_image_link_1'), 
			),

			'additional_image_link_2' => array(
				'label' 		=> __('Additional Image Link 2', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 2', 'value'=>'additional_image_link_2'), 
			),

			'additional_image_link_3' => array(
				'label' 		=> __('Additional Image Link 3', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 3', 'value'=>'additional_image_link_3'), 
			),

			'additional_image_link_4' => array(
				'label' 		=> __('Additional Image Link 4', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 4', 'value'=>'additional_image_link_4'), 
			),

			'additional_image_link_5' => array(
				'label' 		=> __('Additional Image Link 5', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 5', 'value'=>'additional_image_link_5'), 
			),

			'additional_image_link_6' => array(
				'label' 		=> __('Additional Image Link 6', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 6', 'value'=>'additional_image_link_6'), 
			),

			'additional_image_link_7' => array(
				'label' 		=> __('Additional Image Link 7', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 7', 'value'=>'additional_image_link_7'), 
			),

			'additional_image_link_8' => array(
				'label' 		=> __('Additional Image Link 8', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 8', 'value'=>'additional_image_link_8'), 
			),

			'additional_image_link_9' => array(
				'label' 		=> __('Additional Image Link 9', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 9', 'value'=>'additional_image_link_9'), 
			),

			'additional_image_link_10' => array(
				'label' 		=> __('Additional Image Link 10', 'woocommerce_wpwoof'),
				'desc'			=> __( 'More images. You can include up to 10 additional images. If supplying multiple images, send them as comma separated URLs.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> 2000,
				'count' 		=> 10,
				'text'			=> true,
				'woocommerce_default' =>array('label' => 'Additional Image Link 10', 'value'=>'additional_image_link_10'), 
			),

			'shipping' => array(
				'label' 		=> __('Shipping', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Shipping Cost.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Shipping', 'value'=>'shipping')
			),

			'shipping_weight' => array(
				'label' 		=> __('Shipping Weight', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The weight of the product for shipping.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Shipping Weight', 'value' => 'shipping_weight'),
			),

			'shipping_size' => array(
				'label' 		=> __('Shipping Size', 'woocommerce_wpwoof'),
				'desc'			=> __( 'The size of the product for shipping.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Shipping Size', 'value' => 'shipping_size'),
			),

			'custom_label_0' => array(
				'label' 		=> __('Custom Label 0', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Additional information about the item.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Custom Label 0', 'value' => 'custom_label_0'),
			),

			'custom_label_1' => array(
				'label' 		=> __('Custom Label 1', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Additional information about the item.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Custom Label 0', 'value' => 'custom_label_1'),
			),

			'custom_label_2' => array(
				'label' 		=> __('Custom Label 2', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Additional information about the item.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'facebook_len'	=> false,
				'woocommerce_default' =>array('label' => 'Custom Label 2', 'value' => 'custom_label_2'),
			),

			'custom_label_3' => array(
				'label' 		=> __('Custom Label 3', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Additional information about the item.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'woocommerce_default' =>array('label' => 'Custom Label 3', 'value' => 'custom_label_3'),
				'facebook_len'	=> false,
			),

			'custom_label_4' => array(
				'label' 		=> __('Custom Label 4', 'woocommerce_wpwoof'),
				'desc'			=> __( 'Additional information about the item.', 'woocommerce_wpwoof' ),
				'value'			=> false,
				'required'		=> false,
				'feed_type'		=> array('facebook'),
				'woocommerce_default' =>array('label' => 'Custom Label 4', 'value' => 'custom_label_4'),
				'facebook_len'	=> false,
			),


		);

		$this->product_fields = apply_filters( 'woocommerce_wpwoof_all_product_fields', $this->product_fields );
	}

	/**
	 * Helper function to remove blank array elements
	 *
	 * @access public
	 * @param array $array The array of elements to filter
	 * @return array The array with blank elements removed
	 */
	private function remove_blanks( $array ) {
		if ( empty( $array ) || ! is_array( $array ) ) {
			return $array;
		}
		foreach ( array_keys( $array ) as $key ) {
			if ( empty( $array[ $key ] ) || empty( $this->settings['product_fields'][ $key ] ) ) {
				unset( $array[ $key ] );
			}
		}
		return $array;
	}

	/**
	 * Helper function to remove items not needed in this feed type
	 *
	 * @access public
	 * @param array $array The list of fields to be filtered
	 * @param string $feed_format The feed format that should have its fields maintained
	 * @return array The list of fields filtered to only contain elements that apply to the selectedd $feed_format
	 */
	private function remove_other_feeds( $array, $feed_format ) {
		if ( empty( $array ) || ! is_array( $array ) ) {
			return $array;
		}
		foreach ( array_keys( $array ) as $key ) {
			if ( empty( $this->product_fields[ $key ] ) || ! in_array( $feed_format, $this->product_fields[ $key ]['feed_types'] ) ) {
				unset ( $array[ $key ] );
			}
		}
		return $array;
	}

	/**
	 * Retrieve the values that should be output for a particular product
	 * Takes into account store defaults, category defaults, and per-product
	 * settings
	 *
	 * @access public
	 * @param  int  $product_id       The ID of the product to retrieve info for
	 * @param  string  $feed_format   The feed format being generated
	 * @param  boolean $defaults_only Whether to retrieve the
							*         store/category defaults only
	 * @return array                  The values for the product
	 */
	public function get_values_for_product( $product_id = null, $feed_format = 'all', $defaults_only = false ) {
		if ( ! $product_id ) {
			return false;
		}
		// Get Store defaults
		if ( ! isset( $this->settings['product_defaults'] ) ) {
			$this->settings['product_defaults'] = array();
		}
		$settings = $this->remove_blanks( $this->settings['product_defaults'] );
		// Merge category settings
		$categories = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		foreach ( $categories as $category_id ) {
			$category_settings = $this->get_values_for_category( $category_id );
			$category_settings = $this->remove_blanks( $category_settings );
			if ( 'all' != $feed_format ) {
				$category_settings = $this->remove_other_feeds( $category_settings, $feed_format );
			}
			if ( $category_settings ) {
				$settings = array_merge( $settings, $category_settings );
			}
		}
		if ( $defaults_only ) {
			return $settings;
		}
		// Merge prepopulated data if required.
		if ( ! empty( $this->settings['product_prepopulate'] ) ) {
			$prepopulated_values = $this->get_values_to_prepopulate( $product_id );
			$prepopulated_values = $this->remove_blanks( $prepopulated_values );
			$settings            = array_merge( $settings, $prepopulated_values );
		}
		// Merge per-product settings.
		$product_settings = get_post_meta( $product_id, '_woocommerce_wpwoof_data', true );
		if ( $product_settings ) {
			$product_settings = $this->remove_blanks( $product_settings );
			$settings = array_merge( $settings, $product_settings );
		}
		if ( 'all' != $feed_format ) {
			$settings = $this->remove_other_feeds( $settings, $feed_format );
		}
		$settings = $this->limit_max_values( $settings );

		return $settings;
	}

	/**
	 * Make sure that each element does not contain more values than it should.
	 *
	 * @param   array   $data  The data for a product / category.
	 * @return                 The modified data array.
	 */
	private function limit_max_values( $data ) {
		foreach ( $this->product_fields as $key => $element_settings ) {
			if ( empty( $element_settings['max_values'] ) ||
				 empty( $data[ $key ] ) ||
				 ! is_array( $data[ $key ] ) ) {
				continue;
			}
			$limit = intval( $element_settings['max_values'] );
			$data[ $key ] = array_slice( $data[ $key ], 0, $limit );
		}
		return $data;
	}

	/**
	 * Retrieve category defaults for a specific category
	 *
	 * @access public
	 * @param  int $category_id The category ID to retrieve information for
	 * @return array            The category data
	 */
	private function get_values_for_category( $category_id ) {
		if ( ! $category_id ) {
			return false;
		}
		if ( isset ( $this->category_cache[ $category_id ] ) ) {
			return $this->category_cache[ $category_id ];
		}
		$values = get_metadata( 'woocommerce_term', $category_id, '_woocommerce_wpwoof_data', true );
		$this->category_cache[ $category_id ] = &$values;

		return $this->category_cache[ $category_id ];
	}

	/**
	 * Get all of the prepopulated values for a product.
	 *
	 * @param  int    $product_id  The product ID.
	 *
	 * @return array               Array of prepopulated values.
	 */
	private function get_values_to_prepopulate( $product_id = null ) {
		$results = array();
		foreach ( $this->settings['product_prepopulate'] as $gpf_key => $prepopulate ) {
			if ( empty( $prepopulate ) ) {
				continue;
			}
			$value = $this->get_prepopulate_value_for_product( $prepopulate, $product_id );
			if ( ! empty( $value ) ) {
				$results[ $gpf_key ] = $value;
			}
		}
		return $results;
	}

	/**
	 * Gets a specific prepopulated value for a product.
	 *
	 * @param  string  $prepopulate  The prepopulation value for a product.
	 * @param  int     $product_id   The product ID being queried.
	 *
	 * @return string                The prepopulated value for this product.
	 */
	private function get_prepopulate_value_for_product( $prepopulate, $product_id ) {
		$result = array();
		list( $type, $value ) = explode( ':', $prepopulate );
		switch ( $type ) {
			case 'tax':
				$terms = wp_get_object_terms( $product_id, array( $value ), array( 'fields' => 'names' ) );
				if ( ! empty( $terms ) ) {
					$result = $terms;
				}
				break;
			case 'field':
				$result = $this->get_field_prepopulate_value_for_product( $value, $product_id );
				break;
		}
		return $result;
	}

	/**
	 * Get a prepopulate value for a specific field for a product.
	 *
	 * @param  string  $field       Details of the field we want.
	 * @param  int     $product_id  The product ID.
	 *
	 * @return array                The value for this field on this product.
	 */
	private function get_field_prepopulate_value_for_product( $field, $product_id ) {
		global $woocommerce_wpwoof_frontend;

		$product = $woocommerce_wpwoof_frontend->load_product( $product_id );
		if ( ! $product ) {
			return array();
		}
		if ( 'sku' == $field ) {
			$sku = $product->get_sku();
			if ( !empty( $sku ) ) {
				return array( $sku );
			}
		}
		return array();
	}

	/**
	 * Generate a list of choices for the "prepopulate" options.
	 *
	 * @return array  An array of preopulate choices.
	 */
	public function get_prepopulate_options() {
		$options = array();
		$options = array_merge( $options, $this->get_available_taxonomies() );
		$options = array_merge( $options, $this->get_prepopulate_fields() );
		return $options;
	}

	/**
	 * get a list of the available fields to use for prepopulation.
	 *
	 * @return array  Array of the available fields.
	 */
	private function get_prepopulate_fields() {
		$fields = array(
			'field:sku' => 'SKU',
		);
		asort( $fields );
		return array_merge( array( 'disabled:fields' => __( '- Product fields -', 'woo_gpf' ) ), $fields );
	}

	/**
	 * Get a list of the available taxonomies.
	 *
	 * @return array Array of available product taxonomies.
	 */
	private function get_available_taxonomies() {
		$taxonomies = get_object_taxonomies( 'product' );
		$taxes = array();
		foreach ( $taxonomies as $taxonomy ) {
			$tax_details = get_taxonomy( $taxonomy );
			$taxes[ 'tax:' . $taxonomy ] = $tax_details->labels->name;
		}
		asort( $taxes );
		return array_merge( array( 'disabled:taxes' => __( '- Taxonomies -', 'woo_gpf' ) ), $taxes );
	}

    public function get_feed_count(){
        global $wpdb;
        $tablenm = $wpdb->prefix.'options';
        $wpdb->get_results( "SELECT *  FROM ".$tablenm." WHERE option_name LIKE '%wpwoof_feedlist_%'" );
        define("FEED_COUNT", $wpdb->num_rows);
        return $wpdb->num_rows;
    }
}

global $woocommerce_wpwoof_common;
$woocommerce_wpwoof_common = new WoocommerceWpwoofCommon();
