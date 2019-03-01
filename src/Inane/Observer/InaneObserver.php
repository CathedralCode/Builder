<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Http
 *
 * @license MIT
 * @license http://inane.co.za/license/MIT
 *
 * @copyright 2015-2019 Philip Michael Raab <philip@inane.co.za>
 */
namespace Inane\Observer;

/**
 * Observer pattern: Observer
 * 
 * @package Inane\Observer\InaneObserver
 * @namespace \Inane\Observer
 * @version 0.1.0
 */
abstract class InaneObserver {
	abstract function update(InaneSubject $subject_in);
}
