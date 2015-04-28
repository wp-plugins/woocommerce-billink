<?php
/**
 * WooCommerce Billink Api Check Class
 * 
 * @class WC_Billink_Api_Check
 */
class WC_Billink_Api_Check extends WC_Billink_Api_Common {

	protected $uri = '/api/check';

	protected $data = array(
		'ACTION' => 'Check',
		'TYPE' => 'P',
		'COMPANYNAME' => '',
		'CHAMBEROFCOMMERCE' => '',
		'WORKFLOWNUMBER' => '',
		'LASTNAME' => '',
		'INITIALS' => '',
		'FIRSTNAME' => '',
		'HOUSENUMBER' => '',
		'HOUSEEXTENSION' => '',
		'POSTALCODE' => '',
		'PHONENUMBER' => '',
		'BIRTHDATE' => '01-01-1980',
		'EMAIL' => '',
		'ORDERAMOUNT' => '',
		'IP' => '',
		'BACKDOOR' => '0',
	);

	protected $UUID;

	/**
	 * Prepare the request
	 */
	protected function prepare_request() {
		unset( $this->data['STREET'] );

		$this->data['IP'] = $this->get_ip();
	}

	/**
	 * Validate order
	 */
	public function do_check() {
		$response = $this->send_request();

		$result = strtoupper( $response->RESULT[0] );

		if ( $result === 'MSG' ) {
			$code = (int) $response->MSG[0]->CODE[0];
			$UUID = $response->UUID[0];

			if ( $code === 500 and strlen($UUID) > 0 ) {
				$this->UUID = $UUID;

				return true;
			} elseif ($code === 501 ) {
				return false;
			} else {
				$this->log( 'XML Response was erroneous.' );

				throw new WC_Billink_Exception( 'Your order can\'t be processed by Billink. Please try another payment method (1).' );
			}
		} elseif ( $result === 'ERROR' ) {
			$this->log( 'XML Response indicates validation error.' );

			throw new WC_Billink_Exception( 'An error occured while consulting Billink (validation-error): ' . $response->ERROR[0]->DESCRIPTION[0] . '.' );
		} else {
			$this->log( 'XML Response was erroneous.' );

			throw new WC_Billink_Exception( 'Your order can\'t be processed by Billink. Please try another payment method (2).' );
		}
	}

	/**
	 * Get the uuid
	 * @return string
	 */
	public function get_uuid() {
		return $this->UUID;
	}

	/**
	 * Set amount
	 * @param string
	 */
	public function set_order_amount( $amount ) {
		$this->data['ORDERAMOUNT'] = $amount;

		return $this;
	}
}
