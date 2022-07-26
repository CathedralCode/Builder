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
