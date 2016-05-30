<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Config
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <philip@inane.co.za>
 */

namespace Inane\Config;

/**
 * ConfigAwareTrait
 *
 * @package Inane\Config
 * @version 0.1.0
 */
trait ConfigAwareTrait {
	protected $config;
	
	/**
	 * {@inheritDoc}
	 * @see \Inane\Config\ConfigAwareInterface::setConfig()
	 */
	public function setConfig($config) {
		$this->config = $config;
	}
}