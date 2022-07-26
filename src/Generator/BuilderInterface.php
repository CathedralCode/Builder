<?php

/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP version 8
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 *
 * @version $Id: 0.32.2-9-g96a14cc$
 * $Date: Tue Jul 26 22:45:10 2022 +0200$
 */

declare(strict_types=1);

namespace Cathedral\Builder\Generator;

/**
 * Interface for builders
 *
 * @package Cathedral\Builder\Interfaces
 *
 * @version 1.1.0
 */
interface BuilderInterface {

	/**
	 * @param BuilderManager $builderManager
	 */
	public function __construct(BuilderManager &$builderManager);

	/**
	 * Checks if the file exists and is up to date
	 *
	 *  NB: Entity is not version checked, it just needs to be found
	 *
	 * @return \Cathedral\Builder\Generator\FileStatus
	 */
	public function getFileStatus(): FileStatus;

	/**
	 * Get the php code for the generated class
	 *
	 * @return string
	 */
	public function getCode(): string;
}
