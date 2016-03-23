<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@cathedral.co.za>
 * @package Inane\File
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <peep@cathedral.co.za>
 */

namespace Inane\File;

/**
 * Inane\File\FileInfo
 * 
 * File metadata
 * @package Inane\File
 * @version 0.4.0
 */
class FileInfo extends \SplFileInfo {
	
	/**
	 * Convert bites to human readable size
	 * 
	 * @param number $size
	 * @param number $decimals
	 * @return string
	 */
	protected function humanSize($size, $decimals = 2) {
		$sizes = ['B','kB','MB','GB','TB','PB','EB','ZB','YB'];
		$factor = floor((strlen($size) - 1) / 3);
		return sprintf("%.{$decimals}f", $size / pow(1024, $factor)) . @$sizes[$factor];
	}
	
	/**
	 * @return bool
	 */
	public function isValid() {
		return file_exists(parent::getPathname());
	}

	/**
	 * @return string|null
	 */
	public function getMimetype() {
		return (new \finfo())->file(parent::getPathname(), FILEINFO_MIME_TYPE);
	}

	/**
	 * @return string|null
	 */
	public function getHumanSize($decimals = 2) {
		return self::humanSize(parent::getSize(), $decimals);
	}
		
	/**
	 * @return string|null
	 */
	public function getMd5() {
		return md5_file(parent::getPathname());
	}
}
