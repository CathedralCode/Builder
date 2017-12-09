<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Type
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <philip@inane.co.za>
 */

namespace Inane\Type;

/**
 * Holds value till read
 * 
 * @package Inane\Type
 * @version 0.2.1
 * @method static mixed value()
 */
class Once {
	
	/**
	 * @var mixed
	 */
	private $_value;
	
	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * Once Factory
	 * 
	 * @param mixed $value
	 * @return \Inane\Type\Once
	 */
	public static function getOnce($value) {
		return new Once($value);
	}

	/**
	 * Once Constructor
	 * 
	 * @param mixed $value
	 */
	public function __construct($value) {
		$this->value = $this->_value = $value;
	}

	public function __get($name) {
		if ($name == 'value') {
			$value = $this->$name;
			$this->$name = null;
			return $value;
		}
		
		return null;
	}
	
	public function __toString() {
	    $value = $this->value;
	    $this->value = null;
	    return $value == null ? '' : $value;
	}

	/**
	 * Reset the once value
	 * Either to its original value or to the value of $value
	 * 
	 * @param mixed $value
	 * @return \Inane\Type\Once
	 */
	public function reset($value = null) {
		if ($value)
			$this->_value = $value;
		
		$this->value = $this->_value;
		
		return $this;
	}
}