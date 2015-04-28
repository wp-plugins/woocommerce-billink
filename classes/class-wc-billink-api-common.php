<?php
/**
 * WooCommerce Billink Api Common Class
 * 
 * @class WC_Billink_Api_Common
 */
abstract class WC_Billink_Api_Common extends WC_Billink_Api {

	/**
	 * Set company
	 * @param string
	 */
	public function set_company( $company ) {
		$this->data['COMPANYNAME'] = $company;

		if ( empty( $this->data['TYPE'] ) ) {
			$this->data['TYPE'] = empty( $company ) ? 'P' : 'B';
		}

		return $this;
	}

	/**
	 * Set Type
	 * @param string $type
	 */
	public function set_type( $type ) {
		$this->data['TYPE'] = $type;

		return $this;
	}

	/**
	 * Set Workflow
	 * @param int $workflow
	 */
	public function set_workflow( $workflow ) {
		$this->data['WORKFLOWNUMBER'] = $workflow;

		return $this;
	}

	/**
	 * Set Name
	 * @param string $firstName
	 * @param string $lastName
	 */
	public function set_name( $firstName, $lastName ) {
		$this->data['FIRSTNAME'] = $firstName;
		$this->data['LASTNAME'] = $lastName;
		$this->data['INITIALS'] = preg_replace( array( '~(?<!\b).~', '~\W~' ), '', strtoupper( "{$firstName} {$lastName}" ) );

		return $this;
	}

	/**
	 * Set address
	 * @param string $address
	 */
	public function set_address($address) {
		list( $street, $number, $extension ) = WC_Billink_Support_Address::split( $address );

		$this->data['STREET'] = $street;
		$this->data['HOUSENUMBER'] = $number;
		$this->data['HOUSEEXTENSION'] = $extension;

		return $this;
	}

	/**
	 * Set Postalcode
	 * @param string $postalCode
	 */
	public function set_postal_code( $postalCode ) {
		$this->data['POSTALCODE'] = $postalCode;

		return $this;
	}

	/**
	 * Set Phonenumber
	 * @param string $phoneNumber
	 */
	public function set_phone_number( $phoneNumber ) {
		$this->data['PHONENUMBER'] = str_pad( preg_replace( '~\D~', '', $phoneNumber ), 10, '0', STR_PAD_LEFT );

		return $this;
	}

	/**
	 * Set Email
	 * @param string $email
	 */
	public function set_email( $email ) {
		$this->data['EMAIL'] = $email;

		return $this;
	}

	/**
	 * Set Chamber of Commerce
	 * @param string $chamberOfCommerce
	 */
	public function set_chamber_of_commerce( $chamberOfCommerce ) {
		$this->data['CHAMBEROFCOMMERCE'] = $chamberOfCommerce;

		return $this;
	}

	/**
	 * Set Birthdate
	 * @param string $birthdate
	 */
	public function set_birthdate( $birthdate ) {
		$this->data['BIRTHDATE'] = $birthdate ? date( 'd-m-Y', strtotime( $birthdate ) ) : '';

		return $this;
	}
}
