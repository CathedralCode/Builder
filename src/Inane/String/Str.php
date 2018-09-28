<?php
namespace Inane\String;

/**
 *
 * @author philip
 * @version 0.0.2
 */
class Str {

	protected $_str;
	
	/**
	 */
	public function __construct(string $string = '') {
		if ($string) $this->_str = $string;
	}
	
	public static function str_replace_last(string $search, string $replace, string $str ): string {
		if( ( $pos = strrpos( $str , $search ) ) !== false ) {
			$search_length  = strlen( $search );
			$str    = substr_replace( $str , $replace , $pos , $search_length );
		}
		return $str;
	}
	
	public function replaceLast(string $search, string $replace): Str {
		$this->_str = self::str_replace_last($search, $replace, $this->_str);
		return $this;
	}
	
	public function __toString() {
		return $this->_str;
	}
}

