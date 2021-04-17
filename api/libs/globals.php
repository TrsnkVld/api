<?php
	/**
	 * Created by PhpStorm.
	 * User: DBobkov
	 * Date: 29.03.2019
	 * Time: 18:51
	 */

	/**
	 * Print_r and die young.
	 */
	function d($a = NULL) {
		if ($a) print_r($a);
		die();
	}

	/**
	 * Shorter print_r.
	 */
	function r($a = NULL) {
		if ($a) print_r($a);
	}

	/**
	 * print_r + "\n".
	 */
	function p($a = NULL) {
		r($a . "\n");
	}

	/**
	 * Returns associated array where keys are properties of items specified by $prop.
	 * @param $list
	 * @param $prop
	 * @return array
	 */
	function by($list, $prop) {
		$items = [];
		foreach ($list as $item) {
			$items[$item[$prop]] = $item;
		}
		return $items;
	}

	/**
	 * Encodes a string, returns a hash.
	 * Warning! Use only for non critical data, as key is static.
	 * The result hash can be passed in URL.
	 * @param $str
	 * @return string
	 * @throws Exception
	 */
	function encode($str) {
		if ( !$str ) return '';
		$method = \Config::ENCODE_METHOD;
		$key = \Config::ENCODE_KEY;
		//p("Method: ".$method);
		//p("String: ".$str);
		$hash = openssl_encrypt($str, $method, $key);
		if ($hash === FALSE) throw new \Exception("Unknown cipher used for encoding");
		//p("Encoded: ".$hash);
		//p("Decoded: ".openssl_decrypt($hash, $method, $key));
		//d("!!!");
		return base64_encode($hash);
	}

	/**
	 * Decode a hash, previously encoded with encode().
	 * Warning! Use only for non critical data, as key is static.
	 * @param $hash
	 * @return string
	 * @throws Exception
	 */
	function decode($hash) {
		if ( !$hash ) return '';
		$method = \Config::ENCODE_METHOD;
		$key = \Config::ENCODE_KEY;
		$str = openssl_decrypt(base64_decode($hash), $method, $key);
		if ($str === FALSE) throw new \Exception("Unknown cipher used for decoding");
		return $str;
	}