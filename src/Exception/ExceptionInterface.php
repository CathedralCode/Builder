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

/**
 * ExceptionInterface
 *
 * @package Cathedral\Builder\Interfaces
 * 
 * @version 1.0.0
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
