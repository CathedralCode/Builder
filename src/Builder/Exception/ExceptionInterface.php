<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@cathedral.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2014 Philip Michael Raab <peep@cathedral.co.za>
 */
 
namespace Cathedral\Builder\Exception;

/**
 * ExceptionInterface
 * @package Cathedral\Builder\Interfaces
 */
interface ExceptionInterface {
	
	/**
	 * Get class that created error
	 *
	 * @return string
	 */
	public function getCallingClass();
	
	/**
	 * Get function that created error
	 *
	 * @return string
	 */
	public function callingFunction();
	
	/**
	 * Create exception with message
	 * 
	 * @param string $message
	 */
	public function __construct($message, $extra = null, $errorType = 0);
}
