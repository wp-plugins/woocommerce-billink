<?php
/**
 * WooCommerce Billink Fee Class
 * 
 * @class WC_Billink_Fee
 */
class WC_Billink_Fee {
	protected $fees;

	/**
	 * WooCommerce Billink Fee.
	 * @access public
	 * @return WooCommerce Billink Fee
	 */
	public function __construct( $fees ) {
		$this->fees = $this->parse_fees( $fees );
	}

	/**
	 * Calculate price
	 * @param  string $subtotal
	 * @return array
	 */
	public function calculate( $subtotal ) {
		$feeprice = $lastprice = 0;
		foreach ( $this->fees as $fee ) {
			if ( $subtotal >= $fee['price'] and $lastprice <= $fee['price'] ) {
				$feeprice = $fee['fee'];
				$lastprice = $fee['price'];
			}
		}

		return array(
			'excl' => $this->calculate_excl( $feeprice ),
			'incl' => $this->calculate_incl( $feeprice ),
		);
	}

	/**
	 * Check if Taxable
	 * @return boolean
	 */
	protected function is_taxable() {
		return get_option( 'woocommerce_calc_taxes' ) == 'yes';
	}

	/**
	 * Calculate exclusive price
	 * @param  string $fee
	 * @return string
	 */
	protected function calculate_excl( $fee ) {
		if ( $this->is_taxable() and get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) {
			$tax = new WC_Tax;
			$rates = $tax->get_shop_base_rate( $this->tax_class );
			$taxes = $tax->calc_tax( $fee, $rates, true );
			$taxAmount = $tax->get_tax_total( $taxes );

			$fee -= $taxAmount;
		}

		return round( $fee, 2 );
	}

	/**
	 * Calculate inclusive price
	 * @param  string $fee
	 * @return string
	 */
	protected function calculate_incl( $fee ) {
		global $woocommerce;

		if ( $this->is_taxable() ) {
			$tax = new WC_Tax;
			$rates = $tax->get_rates();

			if ( get_option( 'woocommerce_prices_include_tax' ) == 'no' ) {
				$taxes = $tax->calc_tax( $fee, $rates, false );
				$taxAmount = $tax->get_tax_total( $taxes );

				$fee += $taxAmount;
			} else {
				$baseRates = $tax->get_shop_base_rate();

				if ( $woocommerce->customer->is_vat_exempt() ) {
					$baseTaxes = $tax->calc_tax( $fee, $baseRates, true );
					$baseTaxAmount = array_sum( $baseTaxes );

					$fee -= $baseTaxAmount;
				} elseif ( $rates !== $baseRates ) {
					$baseTaxes = $tax->calc_tax( $fee, $baseRates, true, true );
					$moddedTaxes = $tax->calc_tax( $fee - array_sum($baseTaxes ), $rates, false );

					$fee = $fee - array_sum( $baseTaxes ) + array_sum( $moddedTaxes );
				}
			}
		}

		return round( $fee, 2 );
	}

	/**
	 * Parse fees
	 * @return array
	 */
	protected function parse_fees( $additional ) {
		$additional = str_replace( ',', '.', $additional );

		if ( preg_match('~^\d+(\.\d+)?$~', $additional ) || ! preg_match( '~^[\d\.]+:~', $additional ) ) {
			$additional = '0:' . $additional;
		}

		$fees = array();

		foreach ( explode( ';', $additional ) as $fee ) {
			if ( preg_match( '~^\s*([\d\.]+):([\d\.]+)\s*$~', trim( $fee ), $m ) ) {
				$fees[] = array(
					'price' => (float) $m[1],
					'fee' => (float) $m[2],
				);
			}
		}

		return $fees;
	}
}
