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

namespace Cathedral\Builder;

/**
 * Interface for builders
 *
 * @package Cathedral\Builder\Interfaces
 * @namespace \Cathedral\Builder
 */
interface BuilderInterface {

	/**
	 * @param BuilderManager $builderManager
	 */
	public function __construct(BuilderManager &$builderManager);

	/**
	 * Checks if the file already exists
	 * 	returns an int
	 * 	1	exists AND versions match
	 *  0	exists BUT older version
	 *  -1	no file
	 *
	 *  So a boolean false will result from missing or outdated files
	 *
	 *  NB: Entity is not version checked, it just needs to be found
	 *
	 * @return int
	 */
	public function existsFile();

	/**
	 * Get the php code for the generated class
	 *
	 * @return string
	 */
	public function getCode();
}