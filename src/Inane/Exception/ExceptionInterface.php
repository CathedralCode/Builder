<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Exception
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <philip@inane.co.za>
 */

/**
 * Exeptions:
 * 
 * BadFunctionCallException
 * 	Exception thrown if a callback refers to an undefined function or if some arguments are missing.
 * 
 * BadMethodCallException
 * 	Exception thrown if a callback refers to an undefined method or if some arguments are missing.
 * 
 * DomainException
 * 	Exception thrown if a value does not adhere to a defined valid data domain.
 * 
 * InvalidArgumentException
 * 	Exception thrown if an argument is not of the expected type.
 * 
 * LengthException
 * 	Exception thrown if a length is invalid.
 * 
 * LogicException
 * 	Exception that represents error in the program logic. This kind of exception should lead directly to a fix in your code.
 * 
 * OutOfBoundsException
 * 	Exception thrown if a value is not a valid key. This represents errors that cannot be detected at compile time.
 * 
 * OutOfRangeException
 * 	Exception thrown when an illegal index was requested. This represents errors that should be detected at compile time.
 * 
 * OverflowException
 * 	Exception thrown when adding an element to a full container.
 * 
 * RangeException
 * 	Exception thrown to indicate range errors during program execution. Normally this means there was an arithmetic error other than under/overflow. This is the runtime version of DomainException.
 * 
 * RuntimeException
 * 	Exception thrown if an error which can only be found on runtime occurs.
 * 
 * UnderflowException
 * 	Exception thrown when performing an invalid operation on an empty container, such as removing an element.
 * 
 * UnexpectedValueException
 * 	Exception thrown if a value does not match with a set of values. Typically this happens when a function calls another function and expects the return value to be of a certain type or value not including arithmetic or buffer related errors.
 */

namespace Inane\Exception;

interface ExceptionInterface {}
