<?php
/*
 * This file is part of the Cathedral package.
 *
 * (c) Philip Michael Raab <peep@cathedral.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cathedral\Builder\Exception;

class PermissionException extends \RuntimeException implements ExceptionInterface {
	
	protected function getCallingClass() {
		$d = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		$class = $d[2]["class"];
		if ($class == 'Cathedral\Builder\BuilderAbstract') {
			$class = str_replace('get', '', $d[3]["function"]);
			$class = 'Cathedral\Builder\\'.$class.'Builder';
		}
		return $class;
	}
	
	private function callingFunction() {
		$d = debug_backtrace();
		$function = $d[2]["function"];
		return $function;
	}
	
	public function __construct($message) {
		$class = $this->getCallingClass();
		$function = $this->callingFunction();
			
		$message = "{$class}::{$function}:\n\t{$message}";
	
		parent::__construct($message);
	}
}
