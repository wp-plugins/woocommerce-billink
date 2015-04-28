<?php
/**
 * WooCommerce Billink Api Order Class
 * 
 * @class WC_Billink_Api_Order
 */
class WC_Billink_Api_Order extends WC_Billink_Api_Common {

	protected $uri = '/api/order';

	protected $data = array(
		'ACTION' => 'Order',
		'WORKFLOWNUMBER' => '1',
		'ORDERNUMBER' => '',
		'DATE' => '',
		'TYPE' => '',
		'COMPANYNAME' => '',
		'CHAMBEROFCOMMERCE' => '',
		'BANKACCOUNTNUMBER' => '',
		'BANKACCOUNTHOLDER' => '',
		'FIRSTNAME' => '',
		'LASTNAME' => '',
		'SEX' => 'O',
		'BIRTHDATE' => '',
		'STREET' => '',
		'HOUSENUMBER' => '',
		'HOUSEEXTENSION' => '',
		'POSTALCODE' => '',
		'CITY' => '',
		'COUNTRYCODE' => 'NL',
		'PHONENUMBER' => '',
		'EMAIL' => '',
		'ADITIONALTEXT' => '',
		'CHECKUUID' => '',
		'ORDERITEMS' => array(),
	);

	/**
	 * Prepare the request
	 */
	protected function prepare_request() {
		if ( empty ($this->data['DATE'] ) ) $this->data['DATE'] = date('d-m-Y');
	}

	/**
	 * Send order
	 */
	public function do_order() {
		$response = $this->send_request();

		$result = strtoupper( $response->RESULT[0] );

		if ( $result === 'MSG' ) {
			$code = (int) $response->MSG[0]->CODE[0];

			if ( $code === 200 ) {
				return true;
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

		return false;
	}

	/**
	 * Set uuid
	 * @param string $UUID
	 */
	public function set_uuid( $UUID ) {
		$this->data['CHECKUUID'] = $UUID;

		return $this;
	}

	/**
	 * Set Order Number
	 * @param string $orderNumber
	 */
	public function set_order_number( $orderNumber ) {
		$this->data['ORDERNUMBER'] = $orderNumber;

		return $this;
	}

	/**
	 * Set Order Date
	 * @param string $date
	 */
	public function set_order_date( $date ) {
		$this->data['DATE'] = $date ? date( 'd-m-Y', strtotime( $date ) ) : '';

		return $this;
	}

	/**
	 * Set Gender
	 * @param string $gender
	 */
	public function set_gender( $gender ) {
		$this->data['SEX'] = $gender;

		return $this;
	}

	/**
	 * Set City
	 * @param string $city
	 */
	public function set_city( $city ) {
		$this->data['CITY'] = $city;

		return $this;
	}

	/**
	 * Set Country
	 * @param string $country
	 */
	public function set_country( $country ) {
		$this->data['COUNTRYCODE'] = $country;

		return $this;
	}

	/**
	 * Set Additional Text
	 * @param string $additionalText
	 */
	public function set_additional_text( $additionalText ) {
		$this->data['ADITIONALTEXT'] = $additionalText;

		return $this;
	}

	/**
	 * Add items
	 * @param string  $code
	 * @param string  $description
	 * @param mixed $priceExcl
	 * @param mixed $priceIncl
	 * @param mixed $orderQuantity
	 * @param mixed $itemQuantity
	 * @param mixed $tax
	 */
	public function add_item( $code, $description, $priceExcl = 0, $priceIncl = 0, $orderQuantity = 1, $itemQuantity = 1, $tax = 0 ) {

		if ( $priceExcl === 0 and $priceIncl !== 0 and $tax !== 0 ) {
            $priceExcl = round( ( $priceIncl / ( 100 + $tax ) ) * 100, 2 );
        } elseif ( $priceExcl !== 0 and $priceIncl === 0 and $tax !== 0 ) {
            $priceIncl = round( ( $priceExcl / 100 ) * ( 100 + $tax ), 2 );
        } elseif ( $priceExcl !== 0 and $priceIncl !== 0 and $tax === 0 ) {
            $tax = round( ( ( $priceIncl - $priceExcl ) / $priceExcl ) * 100 );
        }

		$this->data['ORDERITEMS'][]['ITEM'] = array(
			'CODE' => $code,
			'DESCRIPTION' => $description,
			'ORDERQUANTITY' => $orderQuantity,
			'ITEMQUANTITY' => $itemQuantity,
			'PRICEEXCL' => number_format( $priceExcl, 2, '.', '' ),
			'PRICEINCL' => number_format( $priceIncl, 2, '.', '' ),
			'BTW' => $tax,
		);

		return $this;
	}
}
