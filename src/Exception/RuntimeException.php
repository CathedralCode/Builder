<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2019 Philip Michael Raab <peep@inane.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Builder\Exception;

use Throwable;

/**
 * RuntimeException
 *
 * @package Cathedral\Builder\Exceptions
 *
 * @version 1.0.0
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface {

	/**
	 * Get class that created error
	 *
	 * @see \Cathedral\Builder\Exception\ExceptionInterface::getCallingClass()
	 */
	public function getCallingClass() {
		$d = debug_backtrace();
		$class = $d[2]["class"];
		return $class;
	}

	/**
	 * Get function that created error
	 *
	 * @see \Cathedral\Builder\Exception\ExceptionInterface::callingFunction()
	 */
	public function callingFunction() {
		$d = debug_backtrace();
		$function = $d[2]["function"];
		return $function;
	}

	/**
	 * Create exception with message
	 *
	 * @param string $message
	 */
	public function __construct($message = '', $code = 0, Throwable $previous = null) {
		$class = $this->getCallingClass();
		$function = $this->callingFunction();

		$message = "{$class}::{$function}:\n\t{$message}";

		parent::__construct($message);
	}
}
