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
	 * 
	 * @return boolean
	 */
	public function existsFile();

	/**
	 * Get the php code for the generated class
	 * 
	 * @return string
	 */
	public function getCode();
}