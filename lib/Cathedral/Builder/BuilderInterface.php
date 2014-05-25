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
 *
 * @author Philip Michael Raab<peep@cathedral.co.za>
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