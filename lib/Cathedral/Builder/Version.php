<?php
/*
 * This file is part of the Cathedral package.
 *
 * (c) Philip Michael Raab <peep@cathedral.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cathedral\Builder;

/**
 * Class to store and retrieve the version of Cathedral Builder.
 */
final class Version {
	/**
	 * Cathedral Builder version identification for releases
	 */
	const VERSION = '0.1.5';
	
	/**
	 * Date of the release
	 */
	const VERSION_DATE = '2014 May 29';
	
	/**
	 * Version of the generated class files, this only increments when the generated files change in functionality
	 */
	const BUILDER_VERSION = '3$';
}