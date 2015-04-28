<?php
/**
 * WooCommerce Billink Address Class
 * 
 * @class WC_Billink_Support_Address
 */
class WC_Billink_Support_Address {

	/**
	 * Split address and validate
	 * @param  string $address
	 * @return mixed
	 */
	public function split( $address ) {
		$arAddressFields = explode( ' ', $address );

		$street = $address;
		$number = $extension = '';

		// only a single address word is found
		if ( count( $arAddressFields ) == 1 ) {
			throw new WC_Billink_Exception( 'Your house number cannot be determined. Fill in your address + house number, for example Streetname 12.' );
		}

		// two address words are found
		// for example street 1, street 1a, street 1-3
		elseif ( count( $arAddressFields ) === 2 ) {
			$street = $arAddressFields[1];

			if ( strpos( $arAddressFields[1], '-' ) > 0 ) {
				list( $number, $extension) = explode( '-', $arrAddressFields[1], 2 );
			} elseif ( ctype_digit( $arAddressFields[1] ) ) {
				// only digits
				$number = $arAddressFields[1];
			} elseif ( ctype_alnum( $arAddressFields[1] ) and ! ctype_alpha( $arAddressFields[1] ) ) {
				// there are both letters and digits in the second element
				// check for combinations of digits and chars
				$arMixed = preg_split( '#(?<=\d)(?=[a-z])#i', $arAddressFields[1] );

				if ( count( $arMixed == 2 ) ) {
					list( $number, $extension ) = $arMixed;
				}
			}
		}

		// more than two address words are found
		// for example street 1 a, 2nd street 3 house, 2nd street 3-4 house
		// check which array element starts with a number
		// straat met evt meerdere woorden + huisnummer + evt toevoeging
		// we reversen de array eerst, omdat het huisnummer meestal achteraan staat.
		else {
			$street = $arAddressFields;

			foreach ( array_reverse( $arAddressFields ) as $addressElm ) {
				array_pop( $street );

				if ( ctype_digit( $addressElm ) ) {
					$number = $addressElm;
					break;
				} elseif ( ctype_alpha( $addressElm ) ) {
					$extension .= ' ' . $addressElm;
				} elseif ( strpos( $addressElm, '-' ) > 0 ) {
					list( $number, $extension ) = explode( '-', $addressElm, 2 );
					break;
				}
				elseif ( ctype_alnum( $addressElm ) ) {
					// there are both letters and digits in the element
					// check for characters
					$arMixed = preg_split( '#(?<=\d)(?=[a-z])#i', $addressElm );

					if ( count( $arMixed === 2 ) ) {
						list( $number, $extension ) = $arMixed;
						break;
					}
				}
			}

			$street = implode( ' ', $street );
		}

		if ( $number == '' ) {
			//$this->log( "Unable to split address: {$address}" );

			throw new WC_Billink_Exception( 'Your house number cannot be determined. Fill in your address + house number, for example Streetname 12.' );
		}

		return array( $street, $number, $extension );
	}
}
