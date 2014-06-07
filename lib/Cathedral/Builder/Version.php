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
 
namespace Cathedral\Builder;

/**
 * Version
 * Builders version information
 * @package Cathedral\Builder\Classes
 */
final class Version {
	/**
	 * Cathedral Builder version identification for releases
	 */
	const VERSION = '0.1.8';
	
	/**
	 * Date of the release
	 */
	const VERSION_DATE = '2014 Jun xx';
	
	/**
	 * Version of the generated class files, this only increments when the generated files change in functionality
	 */
	const BUILDER_VERSION = '6$';
}