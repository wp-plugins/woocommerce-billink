<?php
/**
 * Plugin Name: WooCommerce Billink
 * Plugin URI: http://www.tussendoor.nl/wordpress-plugins/
 * Description: Billink integratie in WooCommerce
 * Version: 1.1.5
 * Author: Tussendoor internet & marketing
 * Author URI: http://www.tussendoor.nl
 * Requires at least: 3.0
 * Tested up to: 3.9
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main WooCommerce Gateway Billink Class
 *
 * @class WooCommerce_Gateway_Billink
 * @version  1.0.0
 */
class WooCommerce_Gateway_Billink {
	const ROOT = __FILE__;

	/**
	 * WooCommerce Gateway Billink Constructor.
	 * @access public
	 * @return WooCommerce Gateway Billink
	 */
	public function __construct() {

		// Called on initialisation
		add_action( 'init', array( $this, 'wordpress_init' ) );

		// Called if all plugins are loaded
		add_action( 'plugins_loaded', array( $this, 'load' ) );
		
		// Add payment method
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_method' ) );
		
		// Add fields to the checkout
		add_filter( 'woocommerce_checkout_fields' , array( $this, 'wc_billink_fields' ) );
		
		// Add validation rules to the checkout
		add_action(	'woocommerce_checkout_process', array( $this, 'wc_billink_fields_process' ) );
	}

	/**
	 * Load on initialisation
	 */
	public function wordpress_init() {
		load_plugin_textdomain( 'woocommerce-gateway-billink', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Run when plugins are loaded
	 */
	public function load() {
		if ( ! class_exists( 'WooCommerce' ) ):
			$message = __('Billink can\'t function without WooCommerce!', 'woocommerce-gateway-billink');
			$this->show_admin_notice( $message, 'error' );
		else:
			require_once 'classes/class-wc-billink-support-address.php';
			require_once 'classes/class-wc-billink-api.php';
			require_once 'classes/class-wc-billink-api-common.php';
			require_once 'classes/class-wc-billink-api-check.php';
			require_once 'classes/class-wc-billink-api-order.php';
			require_once 'classes/class-wc-billink-exception.php';
			require_once 'classes/class-wc-billink-fee.php';
			require_once 'classes/class-wc-billink-gateway.php';
		
		endif;
	}

	/**
	 * Add fields to the checkout
	 * @param array $fields
	 * @return array
	 */
	public function wc_billink_fields( $fields ) {
		$coc 	= array( 'billing_chamber_of_commerce' =>
			array(
				'label' => __('Chamber of Commerce', 'woocommerce-billink'),
				'required' => 0,
				'class' => array(
					'input-text'
				)
			)
		);
		$fields['billing'] = array_slice($fields['billing'], 0, 4, true) + $coc + array_slice($fields['billing'], 4, count($fields['billing']) - 1, true);
		return $fields;
	}

	/**
	 * Add validation rules for the checkout
	 */
	public function wc_billink_fields_process() {
		global $woocommerce;

		$error 		= __('As a company, you are required to fill in the Chamber of Commerce number', 'woocommerce-gateway-billink');

		$company 	= sanitize_text_field($_POST['billing_company']);
		$coc 		= sanitize_text_field($_POST['billing_chamber_of_commerce']);

		if( $company && $company !=='' && ( !$coc || $coc == '') ) {
			$woocommerce->add_error( $error );
		}
	}

	/**
	 * Register a payment method
	 * @param array $methods
	 * @return array
	 */
	public function register_method( $methods ) {
		$methods[] = 'WC_Billink_Gateway';

		return $methods;
	}

	/**
	 * Show the admin notice
	 * @param string $message
	 * @param string $type (default: notice)
	 * @return string
	 */
	private function show_admin_notice( $message, $type = 'notice' ) {
		// Build the error message
		$html = '<div id="message" class="' . $type . '"><p>' . $message . '</p></div>';
		add_action( 'admin_notices', function() use ($html) {
			// Echo the error message in the admin notice area
			echo $html;
		});
	}
}

$GLOBALS['wc_billink'] = new WooCommerce_Gateway_Billink();
