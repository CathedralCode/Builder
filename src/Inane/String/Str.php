<?php
namespace Inane\String;

/**
 *
 * @author philip
 * @version 0.0.4
 */
class Str {

	/**
	 * @var string
	 */
	protected $_str;
	
	/**
	 * Creates instance of Str object
	 * 
	 * @param string $string
	 */
	public function __construct(string $string = '') {
		if ($string) $this->_str = $string;
	}
	
	/**
	 * Replaces last match of search with replace in str
	 * 
	 * @param string $search
	 * @param string $replace
	 * @param string $str
	 * @return string
	 */
	public static function str_replace_last(string $search, string $replace, string $str ): string {
		if( ( $pos = strrpos( $str , $search ) ) !== false ) {
			$search_length  = strlen( $search );
			$str    = substr_replace( $str , $replace , $pos , $search_length );
		}
		return $str;
	}
	
	/**
	 * Replaces last match of search with replace
	 * 
	 * @param string $search
	 * @param string $replace
	 * @return Str
	 */
	public function replaceLast(string $search, string $replace): Str {
		$this->_str = self::str_replace_last($search, $replace, $this->_str);
		return $this;
	}
	
	/**
	 * Check if haystack contains needle
	 * 
	 * @param string $needle
	 * @param string $haystack
	 * @return bool
	 */
	public static function str_contains(string $needle, string $haystack): bool {
		return strstr($haystack, $needle);
	}
	
	/**
	 * Check if Str contains needle
	 * 
	 * @param string $needle
	 * @return bool
	 */
	public function contains(string $needle): bool {
		return self::str_contains($needle, $this->_str);
	}
	
	/**
	 * Append str to Str
	 * 
	 * @param string $str
	 * @return Str
	 */
	public function append(string $str): Str {
		$this->_str .= $str;
		return $this;
	}
	
	/**
	 * Prepend str to Str
	 * 
	 * @param string $str
	 * @return Str
	 */
	public function prepend(string $str): Str {
		$this->_str = "{$str}{$this->_str}";
		return $this;
	}
	
	/**
	 * Echoing the Str object print out the string
	 * 
	 * @return string
	 */
	public function __toString(): string {
		return $this->_str;
	}
}

