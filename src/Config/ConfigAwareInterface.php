<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2019 Philip Michael Raab <peep@inane.co.za>
 */

namespace Cathedral\Builder\Config;

/**
 * ConfigAwareInterface
 *
 * Config Aware Interface
 *
 * @package Cathedral\Config
 */
interface ConfigAwareInterface {

	/**
	 * Builder configuration  array
	 *
	 * @param Array $config
	 */
	public function setConfig($config);
}
