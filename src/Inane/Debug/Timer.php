<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\Debug
 *
 * @license MIT
 * @license http://inane.co.za/license/MIT
 *
 * @copyright 2015-2019 Philip Michael Raab <philip@inane.co.za>
 */
namespace Inane\Debug;

/**
 * Timer
 * 
 * Time durration of events
 *
 * @package Inane\Debug
 * @namespace \Inane\Debug
 * @version 0.3.0
 */
class Timer {
	/**
	 *
	 * @var integer
	 */
	
	protected static $precision = 5;
	
	/**
	 *
	 * @var float
	 */
	protected $endTime;
	
	/**
	 *
	 * @var array
	 */
	protected $interval = [];
	
	/**
	 *
	 * @var float
	 */
	protected $startTime;
	
	// Hold the class instance.
	/**
	 *
	 * @var Timer
	 */
	private static $instance = null;

	/**
	 *
	 * @param $label string
	 * @return Timer
	 */
	public static function addInterval($label = ''): Timer {
		self::getInstance()->interval[] = [
			'label' => $label,
			'time' => microtime(true)
		];
		
		return self::getInstance();
	}

	/**
	 * @return Timer
	 */
	public static function end(): Timer {
		self::getInstance()->endTime = microtime(true);
		
		return self::getInstance();
	}

	// The object is created from within the class itself
	// only if the class has no instance.
	/**
	 * @return Timer
	 */
	public static function getInstance(): Timer {
		if (self::$instance == null) {
			self::$instance = new Timer();
		}
		
		return self::$instance;
	}

	/**
	 * 
	 */
	public static function report() {
		$duration = round(self::getInstance()->endTime - self::getInstance()->startTime, self::$precision);
		$intervals = count(self::getInstance()->interval);
		
		echo $duration . ' sec<br/>' . PHP_EOL;
		
		if ($intervals > 0) {
			echo round($duration / $intervals, self::$precision) . ' sec/interval<br/>' . PHP_EOL;
			
			$lastInterval = self::getInstance()->startTime;
			foreach (self::getInstance()->interval as $interval) {
				echo $interval['label'] . ' => ' . $interval['time'] . ' (AT: ' . number_format($interval['time'] - self::getInstance()->startTime, self::$precision) . ', FOR: ' . number_format($lastInterval - self::getInstance()->startTime, self::$precision) . ')<br/>' . PHP_EOL;
				$lastInterval = $interval['time'];
			}
		}
	}

	/**
	 * @param int $precision
	 * @return Timer
	 */
	public static function start(int $precision = 5): Timer {
		self::$instance = null;
		self::$precision = $precision;
		self::getInstance()->startTime = microtime(true);
		
		return self::getInstance();
	}

		// The constructor is private
		// to prevent initiation with outer code.
	/**
	 */
	private function __construct() {
		$this->interval = [];
		$this->startTime = $this->endTime = microtime(true);
	}
}
