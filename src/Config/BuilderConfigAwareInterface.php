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
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @version $Id: 0.32.2-9-g96a14cc$
 * $Date: Tue Jul 26 22:45:10 2022 +0200$
 */
declare(strict_types=1);

namespace Cathedral\Builder\Config;

/**
 * BuilderConfigAwareInterface
 *
 * Builder Config Aware Interface
 *
 * @package Cathedral\Config
 *
 * @version 1.0.0
 */
interface BuilderConfigAwareInterface {

	/**
	 * Builder configuration  array
	 *
	 * @param array $config
	 */
	public function setBuilderConfig(array $config);
}
