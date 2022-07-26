<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP version 7
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

/**
 * InvalidArgumentException
 *
 * @package Cathedral\Builder\Exceptions
 *
 * @version 1.0.0
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface {

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
    public function __construct($message, $extra = null, $errorType = 0) {
        $class = $this->getCallingClass();
        $function = $this->callingFunction();

        $message = "{$class}::{$function}:\n\t{$message}";

        parent::__construct($message);
    }
}
