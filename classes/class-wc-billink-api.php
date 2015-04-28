<?php
/**
 * WooCommerce Billink Api Class
 * 
 * @class WC_Billink_Api
 */
abstract class WC_Billink_Api {

	const VERSION = 'BILLINK2.0';

	const URL_LIVE = 'https://client.billink.nl';

	const URL_TEST = 'https://test.billink.nl';

	protected $url = self::URL_LIVE;

	protected $uri;

	protected $logger;

	protected $username;

	protected $clientid;

	protected $data = array();

	public function __construct( WC_Logger $logger = null ) {
		$this->logger = $logger;
	}

	/**
	 * Set url for live or testing
	 * @param boolean $testing
	 */
	public function set_testing( $testing = true ) {
		$this->url = $testing ? self::URL_TEST : self::URL_LIVE;

		return $this;
	}

	/**
	 * Set Credentials
	 * @param string $username
	 * @param int $id
	 */
	public function set_credentials( $username, $id ) {
		$this->username = $username;
		$this->clientid = $id;

		return $this;
	}

	/**
	 * Get the ip
	 * @return string
	 */
	protected function get_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return $_SERVER['REMOTE_ADDR'];
		}
	}

	/**
	 * Overide
	 */
	protected function prepare_request() {
	}

	/**
	 * Send xml request to Billink
	 * @return xml
	 */
	public function send_request() {
		$this->prepare_request();

		$request = $this->generate_request( $this->data );

		$this->log( "Sending XML request:\n{$request}" );

		$data = wp_remote_post( $this->url(), array(
			'body' => $request,
		) );

		if ( is_wp_error( $data ) ) {
			$this->log( "An error occured while sending XML request:\n" . $data->get_error_message() );

			throw new WC_Billink_Exception( 'Fout tijdens communicatie met Billink: ' . $data->get_error_message(), $data->get_error_code() );
		}

		$this->log( "Received XML response:\n{$data['body']}" );

		return new SimpleXmlElement( $data['body'] );
	}

	/**
	 * Get url
	 * @return string
	 */
	protected function url() {
		return $this->url . $this->uri;
	}

	/**
	 * Prepare the request
	 * @param array $data
	 */
	protected function generate_request( array $data ) {
		$data['VERSION'] = self::VERSION;
		$data['CLIENTUSERNAME'] = $this->username;
		$data['CLIENTID'] = $this->clientid;

		return $this->generate_xml( 'API', $data );
	}

	/**
	 * Generate xml
	 * @param  string $key
	 * @param  mixed $data
	 * @return string
	 */
	protected function generate_xml( $key, $data ) {
		$xml = "<{$key}>";

		if ( is_array( $data )  and array_keys( $data ) === range( 0, count( $data ) - 1) ) {
			foreach ( $data as $item ) {
				$xml .= $this->generate_xml( key( $item ), current( $item ) );
			}
		} elseif ( is_array( $data ) ) {
			foreach ( $data as $subkey => $value ) {
				$xml .= $this->generate_xml( $subkey, $value );
			}
		} else {
			$xml .= $this->escape_xml( $data );
		}

		$xml .= "</{$key}>\n";

		return $xml;
	}

	/**
	 * Replace specialchars
	 * @param  string $value
	 * @return string
	 */
	protected function escape_xml( $value ) {
		return strtr( utf8_encode( $value ), array(
			'<' => '&lt;',
			'>' => '&gt;',
			'"' => '&quot;',
			"'" => '&apos;',
			'&' => '&amp;',
		) );
	}

	/**
	 * Log messages
	 * @param mixed $message
	 */
	protected function log( $message ) {
		if ( $this->logger ) {
			$this->logger->add( 'billink', $message );
		}
	}
}
