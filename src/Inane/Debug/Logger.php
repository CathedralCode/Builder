<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Debug
 *
 * @license MIT
 * @license http://inane.co.za/license/MIT
 *
 * @copyright 2015-2019 Philip Michael Raab <philip@inane.co.za>
 */
namespace Inane\Debug;

/**
 * Log to html with pre & code tags
 *
 * @package Inane\Debug
 * @namespace \Inane\Debug
 * @version 0.4.0
 */
class Logger {
	/**
	 *
	 * @var Logger The reference to *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Logger The *Singleton* instance.
	 */
	public static function log() {
		if (null === static::$instance) {
			static::$instance = new Logger();
		}
		
		return static::$instance;
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup() {
	}
	
	/**
	 * @var bool end execution after dump
	 */
	protected $_die = true;
	
	/**
	 * @var string buffer for building output
	 */
	protected $_output = '';

	/**
	 * Builds the dump header
	 * 
	 * @param string $label for dump
	 * 
	 * @return \Inane\Debug\Logger
	 */
	protected function header($label = '') {
		if ($label != '')
			$label = "<h4 class=\"debug-header\">{$label}</h4>";
		
		$this->_output = "<div class=\"inane-debug\">{$label}<pre class=\"debug-code\"><code>";
		return $this;
	}

	/**
	 * Print
	 * 
	 * @param unknown $var
	 * @param string $label
	 * @return \Inane\Debug\Logger
	 */
	protected function doLogging($var, $label = '') {
		if ($label != '')
			$label .= ': ';
		
		$this->_output = $label . print_r($var, true);
		return $this;
	}

	/**
	 * Print
	 * 
	 * @param unknown $var
	 * @return \Inane\Debug\Logger
	 */
	protected function doPrint($var) {
		$this->_output .= print_r($var, true);
		return $this;
	}

	/**
	 * Dump
	 * 
	 * @param unknown $var
	 * @return \Inane\Debug\Logger
	 */
	protected function doDump($var) {
		echo $this->_output;
		$this->_output = '';
		
		var_dump($var);
		return $this;
	}

	/**
	 * Create footer for dump
	 * 
	 * @param unknown $die
	 * @return \Inane\Debug\Logger
	 */
	protected function footer($die = null) {
		if ($die === null)
			$die = $this->_die;
		
		$out = '</code></pre></div>';
		
		if ($this->_output == '')
			echo $out;
		else
			$this->_output .= $out;
		
		if ($die)
			exit();
		
		$this->_die = false;
		return $this;
	}

	/**
	 * Build out
	 * 
	 * @param string $return
	 * @return string|boolean
	 */
	protected function out($return = false) {
		$out = $this->_output;
		$this->_output = '';
		
		if ($return === true)
			return $out;
		
		if ($out != '')
			echo $out;
		
		return false;
	}
	
	/**
	 * Output variable using var_dump
	 *
	 * @param unknown $var
	 * @param string $label
	 * @param bool $die
	 * @return \Inane\Debug\Logger
	 */
	public static function echo($var, $label = null, $die = null) {
		return static::log()->dumper($var, $label, $die);
	}

	/**
	 * Output variable using log
	 *
	 * @param unknown $var        	
	 * @param string $label        	
	 * @param bool $die        	
	 * @return \Inane\Debug\Logger
	 */
	public function logger($var, $label = null, $die = null) {
		return $this->doLogging($var, $label)->out(true);
	}

	/**
	 * Output variable using print_r
	 *
	 * @param unknown $var        	
	 * @param string $label        	
	 * @param bool $die        	
	 * @return \Inane\Debug\Logger
	 */
	public function printer($var, $label = null, $die = null, $return = false) {
		return $this->header($label)->doPrint($var)->footer($die)->out($return);
	}

	/**
	 * Output variable using var_dump
	 *
	 * @param unknown $var        	
	 * @param string $label        	
	 * @param bool $die        	
	 * @return \Inane\Debug\Logger
	 */
	public function dumper($var, $label = null, $die = null) {
		return $this->header($label)->doDump($var)->footer($die);
	}
}
