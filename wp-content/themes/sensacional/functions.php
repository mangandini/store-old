<?php

/* Template functions */
add_action( 'wp_enqueue_scripts', 'basel_child_enqueue_styles', 1000 );

/* Avoid wrong overriding of template files */
function basel_child_enqueue_styles() {
	if( basel_get_opt( 'minified_css' ) ) {
		wp_enqueue_style( 'basel-style', get_template_directory_uri() . '/style.min.css', array('bootstrap') );
	} else {
		wp_enqueue_style( 'basel-style', get_template_directory_uri() . '/style.css', array('bootstrap') );
	}
	
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('bootstrap') );
}

/* Defines the number of icons in each 'condition' */
function sensacional_condicion_iconos_cantidad() {
	global $product;
    $condicion = $product->get_attribute('condicion');
    $q_canastos_on = 0;
    $q_canastos_off = 0;

    switch($condicion) {
    	case "Sensacional":
    	{
    		$q_canastos_on = 5;
    		$q_canastos_off = 0;
    		break;
    	}
    	case "Casi Sensacional":
    	{
    		$q_canastos_on = 4;
    		$q_canastos_off = 1;
    		break;
    	}
    	case "Piola":
    	{
    		$q_canastos_on = 3;
    		$q_canastos_off = 2;
    		break;
    	}
    	case "Ahí Nomás":
    	{
    		$q_canastos_on = 2;
    		$q_canastos_off = 3;
    		break;
    	}
    	case "Última Vida":
    	{
    		$q_canastos_on = 1;
    		$q_canastos_off = 4;
    		break;
    	}
    }
    
    $canastos = array();
    $canastos["on"] = $q_canastos_on;
    $canastos["off"] = $q_canastos_off;
    return $canastos;
}

/* Shows the icons for condition in Product Page */
function sensacional_condicion_iconos($view, $canastos) {
	if($view == "single"){
	$canasto_on = '<img src="'.get_stylesheet_directory_uri().'/images/canasto_on.png" class="icon">';
	$canasto_off = '<img src="'.get_stylesheet_directory_uri().'/images/canasto_off.png" class="icon">';
	} elseif($view == "category"){
	$canasto_on = '<img src="'.get_stylesheet_directory_uri().'/images/canasto_sm_on.png" class="icon">';
	$canasto_off = '<img src="'.get_stylesheet_directory_uri().'/images/canasto_sm_off.png" class="icon">';	
	}

	$q_canastos_on = $canastos["on"];
	$q_canastos_off = $canastos["off"];

    for($i = 1; $i <= $q_canastos_on; $i++) {
    	echo($canasto_on);
    }
    for($i = 1; $i <= $q_canastos_off; $i++) {
    	echo($canasto_off);
    }
}

/* Shows Condition text in Product Page */
function sensacional_condicion_texto() {
	global $product;
    echo(strtoupper($product->get_attribute('condicion')));
}

/* Moves price below Cart */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 29 );

/* Prints New Price with money format */
function sensacional_precio_nuevo() {
	global $product;
	$precio_nuevo = wc_price($product->get_attribute('precio_nuevo'));
    return $precio_nuevo;
}

/* Prints New Price without money format */
function sensacional_precio_nuevo_nocurr() {
	global $product;
	$precio_nuevo = $product->get_attribute('precio_nuevo');
    return $precio_nuevo;
}

/* Filters Product Attributes that have certain slug first */
function sensacional_atributos_seleccionar() {
	global $product;
	$attributes = $product->get_attributes();
	$attributes_processed = Array();
	if($attributes) {
		foreach ($attributes as $attr) {
			if(isset($attr['name'])){
				if(stripos($attr['name'],'attr_') !== false){
					$key = wc_attribute_label($attr['name'], $product);
					$value = $product->get_attribute($attr['name']);
					$attributes_processed[$key] = $value;
				}
			}
		}
	}
	return $attributes_processed;
}

/* Prints icons for Product Attributes depending on score */
function sensacional_atributos_iconos($nota) {

	$icono_on = '<img src="'.get_stylesheet_directory_uri().'/images/icono_on.png" class="icon">';
	$icono_off = '<img src="'.get_stylesheet_directory_uri().'/images/icono_off.png" class="icon">';
	$nota = intval($nota);

	if ($nota > 5) { 
		$nota = 5; 
	} elseif($nota < 1) {
		$nota = 1;
	}

	$q_iconos_on = $nota;
	$q_iconos_off = 5 - $nota;

    for($i = 1; $i <= $q_iconos_on; $i++) {
    	echo($icono_on);
    }
    for($i = 1; $i <= $q_iconos_off; $i++) {
    	echo($icono_off);
    }
}

/* Prints Value:Key for Product Attributes with grades */
function sensacional_atributos_imprimir() {
	$attributes = sensacional_atributos_seleccionar();
	if($attributes) {
		foreach ($attributes as $attr => $key) {	
			echo('<div class="atributo">');
			echo ('<div class="texto">'. $attr .':</div>');
			echo('<div class="iconos">');
			sensacional_atributos_iconos($key);
			echo('</div>');
			echo('</div>');
		}
	}
}

/* Returns saving % compared to Reference Price */
function sensacional_porcentajeahorro() {
	global $product;
	$precio_actual = $product->get_price();
	$precio_ref = sensacional_precio_nuevo_nocurr();
	$ahorro = round((1-($precio_actual/$precio_ref))*100);
	return $ahorro;
}

/* Removes Back button from Breadcrumbs */
function basel_back_btn() {}

/* Adds 'condicion' in category view */
function sensacional_condicion_categoria(){
}


/* Adds Listo plugin support for Comunas */
add_filter('listo_list_types', 'comunas');
function comunas($lists_types){
  $lists_types['comunas']='Listo_Comunas';
  //listo expects to find your class My_Custom_Listo_Interface_Class to use it
  require_once '/wp-content/plugins/listo/modules/comunas.php';
  return $lists_types;
}

/* Remove fields from Checkout */

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
add_filter( 'woocommerce_billing_fields' , 'custom_override_billing_fields' );
add_filter( 'woocommerce_shipping_fields' , 'custom_override_shipping_fields' );

function custom_override_checkout_fields( $fields ) {
 	unset($fields['billing']['billing_company']);
 	unset($fields['billing']['billing_address_2']);
 	unset($fields['billing']['billing_city']);
 	unset($fields['billing']['billing_postcode']);
 	unset($fields['billing']['billing_country']);
 	unset($fields['shipping']['shipping_company']);
 	unset($fields['shipping']['shipping_address_2']);
 	unset($fields['shipping']['shipping_city']);
 	unset($fields['shipping']['shipping_postcode']);
 	unset($fields['shipping']['shipping_country']);
    return $fields;
}

function custom_override_billing_fields( $fields ) {
 	unset($fields['billing']['billing_company']);
 	unset($fields['billing']['billing_address_2']);
 	unset($fields['billing']['billing_city']);
 	unset($fields['billing']['billing_postcode']);
 	unset($fields['billing']['billing_country']);
    return $fields;
}

function custom_override_shipping_fields( $fields ) {
 	unset($fields['shipping']['shipping_company']);
 	unset($fields['shipping']['shipping_address_2']);
 	unset($fields['shipping']['shipping_city']);
 	unset($fields['shipping']['shipping_postcode']);
 	unset($fields['shipping']['shipping_country']);
    return $fields;
}

/* Remove fields from My Account edit */
function storefront_child_remove_unwanted_form_fields($fields) {
    unset( $fields ['company'] );
    unset( $fields ['address_2'] );
    unset( $fields ['city'] );
    unset( $fields ['postcode'] );
    unset( $fields ['country'] );
    return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'storefront_child_remove_unwanted_form_fields' );

