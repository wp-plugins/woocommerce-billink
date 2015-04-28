<?php
/**
 * WooCommerce Billink Gateway Class
 * 
 * @class WC_Billink_Gateway
 */
class WC_Billink_Gateway extends WC_Payment_Gateway
{
	public $extra_fields = array();

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		global $woocommerce;

		$this->id 			= 'billink';
		$this->icon 		= apply_filters( 'woocommerce_billink_icon', plugins_url( 'img/logo.jpg', WooCommerce_Gateway_Billink::ROOT ) );
		$this->has_fields 	= true;
		$this->method_title = __( 'Billink', 'woocommerce-gateway-billink' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title 		= $this->get_option( 'title' );
		$this->description 	= $this->get_option( 'description' );
		$this->user 		= $this->get_option( 'user' );
		$this->userid 		= $this->get_option( 'userid' );
		$this->workflow 	= $this->get_option( 'workflow' );
		$this->testmode 	= $this->get_option( 'testmode' ) == 'yes';
		$this->debug 		= $this->get_option( 'debug' ) == 'yes';
		
		$this->init_extra_fields();

		if ( $this->debug ) $this->log = new WC_Logger();

		// Actions
		add_action( 'woocommerce_thankyou_billink', array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		if ( ! $this->is_valid_for_use() ) $this->enabled = false;
	}


	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @access public
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array(get_woocommerce_currency(), apply_filters('woocommerce_billink_supported_currencies', array('EUR')));
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'Billink', 'woocommerce-gateway-billink' ); ?></h3>
		<p><?php _e( 'Billink is an payment platform which allow customers to pay afterwards.', 'woocommerce-gateway-billink' ); ?></p>

		<?php if ( $this->is_valid_for_use() ): ?>

			<table class="form-table">
			<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
			?>
			</table><!--/.form-table-->

		<?php else: ?>
			<div class="inline error">
				<p>
					<strong><?php _e( 'Gateway Disabled', 'woocommerce-gateway-billink' ); ?></strong>:
					<?php _e( 'Billink doesn\'t support your shops payment method.', 'woocommerce-gateway-billink' ); ?>
				</p>
			</div>
		<?php endif;
	}


	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce-gateway-billink' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Billink', 'woocommerce-gateway-billink' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce-gateway-billink' ),
				'type' => 'text',
				'description' => __( 'The name of this payment method which will be shown to the customer when making the payment.', 'woocommerce-gateway-billink' ),
				'default' => __( 'Billink', 'woocommerce-gateway-billink' ),
				'desc_tip'      => true,
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce-gateway-billink' ),
				'type' => 'textarea',
				'description' => __( 'The description which will be shown when the custoner selects Billink as payment method. <code>%costs%</code> will be replaced by extra costs, <code>%vat%</code> by text or the extra costs incl. or excl. VAT.', 'woocommerce-gateway-billink' ),
				'default' => __( 'Easily pay afterward with Billink. Extra costs are %costs% %vat%', 'woocommerce-gateway-billink' ),
				'desc_tip' => true,
			),
			'error_denied' => array(
				'title' => __( 'Notification when rejected', 'woocommerce-gateway-billink' ),
				'type' => 'text',
				'description' => __( 'Message which will be shown when Billink doens\'t accept an customer.', 'woocommerce-gateway-billink' ),
				'default' => __( 'Sorry, Billink has rejected your payment request.', 'woocommerce-gateway-billink' )
			),
			'thankyou_message' => array(
				'title' => __( 'Message when succesfully processed.', 'woocommerce-gateway-billink' ),
				'type' => 'textarea',
				'description' => __( 'Message which will be shown on the thank you page of placing an order.', 'woocommerce-gateway-billink' ),
				'default' => __( ' ', 'woocommerce-gateway-billink' )
			),
			'additional_cost' => array(
				'title' => __( 'Payment costs', 'woocommerce-gateway-billink' ),
				'type' => 'text',
				'description' => __( 'Costs which will be passed to the customer when paying through Billink. Accepts the format: <code>0:2,00;30:1,50;</code> to set variables.<br>Here <code>;</code> is the separator character, the number before the colon is the minimal order amount and the added costs.', 'woocommerce-gateway-billink' ),
				'default' => '0',
			),
			'user' => array(
				'title' => __( 'Username', 'woocommerce-gateway-billink' ),
				'type' => 'text',
				'description' => __( 'Your username at Billink', 'woocommerce-gateway-billink' ),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => 'username'
			),
			'userid' => array(
				'title' => __( 'Billink ID', 'woocommerce-gateway-billink' ),
				'type' => 'text',
				'description' => __( 'Your user ID at Billink.', 'woocommerce-gateway-billink' ),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => 'd38a3439590889df026367bf01d621e687b8d278'
			),
			'workflow' => array(
				'title' => __( 'Workflow', 'woocommerce-gateway-billink' ),
				'type' => 'select',
				'description' => __( 'The Billink workflow which will be used.', 'woocommerce-gateway-billink' ),
				'default' => '3',
				'desc_tip' => true,
				'options' => array(
					'1' => __( '1: Warranty and validation', 'woocommerce-gateway-billink' ),
					'3' => __( '3: No warranty, no validation', 'woocommerce-gateway-billink' ),
				),
			),
			'testing' => array(
				'title' => __( 'Gateway Testing', 'woocommerce-gateway-billink' ),
				'type' => 'title',
				'description' => '',
			),
			'testmode' => array(
				'title' => __( 'Billink testing', 'woocommerce-gateway-billink' ),
				'type' => 'checkbox',
				'label' => __( 'Enable the test mode', 'woocommerce-gateway-billink' ),
				'default' => 'yes'
			),
			'debug' => array(
				'title' => __( 'Debug Log', 'woocommerce-gateway-billink' ),
				'type' => 'checkbox',
				'label' => __( 'Enable logging', 'woocommerce-gateway-billink' ),
				'default' => 'no',
				'description' => sprintf( __( 'Log Billink events, inside <code>woocommerce/logs/billink-%s.txt</code>', 'woocommerce-gateway-billink' ), sanitize_file_name( wp_hash( 'billink' ) ) ),
			),
		);
	}

	/**
	 * Add fields to the billink checkout
	 */
	protected function init_extra_fields() {
		$this->extra_fields = apply_filters( 'billink_extra_fields', array(
			'billink_birthdate' => array(
				'type' => 'text',
				'label' => __( 'Birthdate', 'woocommerce-gateway-billink' ),
				'placeholder' => 'dd-mm-jjjj',
				'required' => true,
				'custom_attributes' => array(
					'style' => 'width: 200px;',
				),
				'rules' => array(
					'required' => __( 'Birthdate is required.', 'woocommerce-gateway-billink' ),
					'match' => array(
						'pattern' => '~^(?:(?:0?[1-9])|(?:[1-2]\d)|(?:3[0-1]))-(?:(?:0?[1-9])|(?:1[0-2]))-[1-2]\d{3}$~',
						'error' => __( 'Birthdate must me formatted as dd-mm-yyyy.', 'woocommerce-gateway-billink' ),
					),
				),
			),
			'billink_accept' => array(
				'type' => 'checkbox',
				'label' => __( 'I accept the Billink terms', 'woocommerce-gateway-billink' ),
				'required' => true,
				'rules' => array(
					'required' => __( 'You must accept the Billink terms.', 'woocommerce-gateway-billink' ),
				),
			),
		));

		if ( $this->workflow == '3' ) {
			unset( $this->extra_fields['billink_birthdate'] );
		}
	}

	/**
	 * Calculate the fee
	 */
	protected function calculate_fee( $total ) {
		$fee = new WC_Billink_Fee( $this->get_option( 'additional_cost' ) );
		return $fee->calculate( $total );
	}

	/**
	 * Get value
	 * @param  string $key
	 * @return string
	 */
	protected function get_value( $key ) {
		global $woocommerce;

		if ( ! empty( $_POST[$key] ) ) {
			return esc_attr( $_POST[$key] );
		} else {
			return $woocommerce->customer->{$key};
		}
	}

	/**
	 * Get total price of order
	 * @return string
	 */
	protected function get_order_total() {
		global $woocommerce;

		if ( isset( $_GET['order_id'] ) ) {
			$orderId = absint( $_GET['order_id'] );
			$order = new WC_Order( $orderId );

			if ( $order->id == $orderId ) {
				return $order->order_total;
			}
		}

		return $total = $woocommerce->cart->total;
	}

	/**
	 * Payment fields
	 * @return mixed
	 */
	public function payment_fields() {
		global $woocommerce;

		if ( $description = $this->get_description() ) {
			$fee = $this->calculate_fee( $this->get_order_total() );

			if ( get_option( 'woocommerce_prices_include_tax' ) ) {
				$description = strtr($description, array(
					'%costs%' => woocommerce_price( $fee['incl'] ),
					'%vat%' => $woocommerce->countries->inc_tax_or_vat(),
				) );
			} else {
				$description = strtr($description, array(
					'%costs%' => woocommerce_price($fee['excl']),
					'%vat%' => $woocommerce->countries->ex_tax_or_vat(),
				) );
			}

        	echo wpautop( wptexturize( $description ) );
		}

		foreach ( $this->extra_fields as $key => $args ) {
			woocommerce_form_field( $key, $args, $this->get_value( $key ) );
		}
	}

	/**
	 * Field validation
	 */
	public function validate_fields() {
		global $woocommerce;
		
		foreach ( $this->extra_fields as $key => $args ) {
			$value = isset( $_POST[$key] ) ? sanitize_text_field($_POST[$key]) : '';

			if ( isset( $args['rules'] ) ) {
				foreach ( $args['rules'] as $rule => $details ) {
					$error = $this->{"validate_rule_{$rule}"}( $details, $value );

					if ( $error ) {
						wc_add_notice( $error, 'error' );
						continue 2;
					}
				}
			}
			$woocommerce->customer->{$key} = $value;
		}
	}

	/**
	 * Required field validation
	 * @param  string $error
	 * @param  string $value
	 * @return string
	 */
	protected function validate_rule_required( $error, $value ) {
		if ( empty( $value ) ) return $error;
	}

	/**
	 * Patern field validation
	 * @param  array $details
	 * @param  string $value
	 * @return mixed
	 */
	protected function validate_rule_match( array $details, $value ) {
		if ( ! preg_match( $details['pattern'], $value ) ) return $details['error'];
	}

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );
		$check = new WC_Billink_Api_Check( $this->log );
		$api = new WC_Billink_Api_Order( $this->log );

		$billinkFee = $this->calculate_fee( $order->get_total() );

		try
		{
			$check->set_testing( $this->testmode )
			    ->set_workflow( $this->workflow )
			    ->set_credentials( $this->user, $this->userid )
			    ->set_order_amount( $order->get_total() + $billinkFee['incl'] )
			    ->set_company( $order->billing_company )
			    ->set_name( $order->billing_first_name, $order->billing_last_name )
			    ->set_address( $order->billing_address_1 . ' ' . $order->billing_address_2 )
			    ->set_postal_code( $order->billing_postcode )
			    ->set_phone_number( $order->billing_phone )
			    ->set_email( $order->billing_email )
			    ->set_chamber_of_commerce( $order->billing_chamber_of_commerce )
			    ->set_birthdate( $woocommerce->customer->billink_birthdate );

			if ( ! $check->do_check() ) {
				
				return wc_add_notice( $this->get_option( 'error_denied' ), 'error' );
			}

			$api->set_testing( $this->testmode )
			    ->set_workflow( $this->workflow )
			    ->set_credentials( $this->user, $this->userid )
			    ->set_company( $order->billing_company )
			    ->set_name( $order->billing_first_name, $order->billing_last_name )
			    ->set_address( $order->billing_address_1 . ' ' . $order->billing_address_2 )
			    ->set_postal_code( $order->billing_postcode )
			    ->set_phone_number( $order->billing_phone )
			    ->set_birthdate( $woocommerce->customer->billink_birthdate )
			    ->set_uuid( $check->get_uuid() )
			    ->set_order_number( $order->id )
			    ->set_order_date( $order->order_date )
			    ->set_city( $order->billing_city )
			    ->set_country( $order->billing_country )
			    ->set_email( $order->billing_email )
			    ->set_chamber_of_commerce( $order->billing_chamber_of_commerce )
			    ->set_additional_text( $order->customer_note );

			// Apply items
			foreach ( $order->get_items() as $item ) {
				$product 	= $order->get_product_from_item( $item );
				$_tax 		= new WC_Tax();//looking for appropriate vat for specific product
				$rates 		= array_shift($_tax->get_rates( $product->get_tax_class() ));
				if ( isset($rates['rate']) ) {
					$tax_rate	= ($rates['rate'] == 0 ? 0 : round($rates['rate']));

				} else {
					$tax_rate	= 0;
				}

				$api->add_item(
					$product ? $product->id : '0',
					$item['name'],
					$order->get_item_total( $item, false ),
					$order->get_item_total( $item, true ),
					$item['qty'],
					1,
					$tax_rate
				);
			}

			// Apply shipping cost
			if ( (float) $order->get_total_shipping() > 0 ) {
				$api->add_item( __( 'Shipment', 'woocommerce-gateway-billink' ), $order->get_shipping_method(), (float) $order->get_total_shipping(), (float) $order->get_total_shipping() + (float) $order->get_shipping_tax() );
			}

			// Apply payment fee
			if ( $billinkFee['incl'] > 0 ) {
				$api->add_item( 'Billink', $order->payment_method_title, $billinkFee['excl'], $billinkFee['incl'] );
			}

			// Apply fees
			foreach ( $order->get_fees() as $fee ) {
				$api->add_item( __( 'Costs', 'woocommerce-gateway-billink' ), $fee['name'], (float) $fee['line_total'], (float) $fee['line_total'] + (float) $fee['line_tax'] );
			}

			if ( $order->get_order_discount() > 0 ) {
				$api->add_item( __( 'Discount', 'woocommerce-gateway-billink' ), __( 'Discount', 'woocommerce-gateway-billink' ), -1 * (float) $order->get_order_discount(), -1 * (float) $order->get_order_discount() );
			}

			$api->do_order();
		}
		catch ( WC_Billink_Exception $e ) {
			return wc_add_notice( $e->getMessage(), 'error' );
		}

		$this->applyFee( $order, $billinkFee );

		$order->payment_complete();

		$woocommerce->cart->empty_cart();

		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order ),
		);
	}

	/**
	 * Add order
	 * @param  WC_Order $order
	 * @param  array $fee
	 */
	protected function applyFee( WC_Order $order, array $fee ) {
		if ( $fee['incl'] > 0 ) {
			$item_id = woocommerce_add_order_item( $order->id, array(
				'order_item_name' => $order->payment_method_title,
				'order_item_type' => 'fee',
			) );

			$tax = $fee['incl'] - $fee['excl'];

			woocommerce_add_order_item_meta( $item_id, '_tax_class', '' );
			woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $fee['excl'] ) );
			woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $tax ) );

			update_post_meta( $order->id, '_order_tax', woocommerce_format_decimal( $order->order_tax + $tax ) );
			update_post_meta( $order->id, '_order_total', woocommerce_format_decimal( $order->get_order_total( ) + $fee['incl'] ) );
		}
	}

	/**
	 * Show thankyou page
	 */
	public function thankyou_page( $order ) {
		if ( $thankyou = $this->get_option( 'thankyou_message' ) ) {
        	echo wpautop( wptexturize( $thankyou ) );
		}
	}

}
