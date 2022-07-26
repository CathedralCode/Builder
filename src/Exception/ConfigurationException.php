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
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @version $Id: 0.32.2-9-g96a14cc$
 * $Date: Tue Jul 26 22:45:10 2022 +0200$
 */
declare(strict_types=1);

namespace Cathedral\Builder\Exception;

use Throwable;

/**
 * ConfigurationException
 *
 * @package Cathedral\Builder\Exceptions
 *
 * @version 1.0.0
 */
class ConfigurationException extends \UnexpectedValueException implements ExceptionInterface {

	/**
	 * @see \Cathedral\Builder\Exception\ExceptionInterface::getCallingClass()
	 */
	public function getCallingClass() {
		$d = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		$class = $d[2]["class"];
		if ($class == 'Cathedral\Builder\BuilderAbstract') {
			$class = str_replace('get', '', $d[3]["function"]);
			$class = 'Cathedral\Builder\\'.$class.'Builder';
		}
		return $class;
	}


	/**
	 * @see \Cathedral\Builder\Exception\ExceptionInterface::callingFunction()
	 */
	public function callingFunction() {
		$d = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
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
