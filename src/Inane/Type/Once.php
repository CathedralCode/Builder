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
 * @license http://inane.co.za/license/MIT
 *
 * @copyright 2015-2019 Philip Michael Raab <philip@inane.co.za>
 */

namespace Inane\Type;

/**
 * Holds value till read
 *
 * Thus only letting its value be used once
 *
 * @package Inane\Type
 * @namespace \Inane\Type
 * @method static string value()
 * @version 0.3.0
 */
class Once {
	
	/**
	 * @var string holder for the value that may only be used once
	 */
	private $_value;
	
	/**
	 * @var string the value that may only be used once
	 */
	protected $value;

	/**
	 * Once Factory
	 *
	 * @param string $value the value that may only be used once
	 * 
	 * @return Once
	 */
	public static function getOnce(string $value): Once {
		return new Once($value);
	}

	/**
	 * Once Constructor
	 *
	 * @param string $value the value that may only be used once
	 */
	public function __construct(string $value) {
		$this->value = $this->_value = $value;
	}

	/**
	 * Magic method to get property
	 * 
	 * For using getters when accessing properties
	 * 
	 * @param string $name return the value
	 * @return string|NULL
	 */
	public function __get(string $name): string {
		if ($name == 'value') {
			$value = $this->$name;
			$this->$name = null;
			return $value;
		}
		
		return null;
	}

	/**
	 * Returns the value of the object
	 *
	 * @return string the objects value
	 */
	public function __toString(): string {
		$value = $this->value;
		$this->value = null;
		return $value == null ? '' : $value;
	}

	/**
	 * Reset the once value
	 *
	 * Either to its original value or to the value of $value
	 *
	 * @param string $value the value that may only be used once
	 *
	 * @return Once
	 */
	public function reset(string $value): Once {
		if ($value)
			$this->_value = $value;
		
		$this->value = $this->_value;
		
		return $this;
	}
}
