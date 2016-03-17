<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@cathedral.co.za>
 * @package Inane\Http
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <peep@cathedral.co.za>
 */

namespace Inane\Http;

use Inane\File\FileInfo;

/**
 * Inane\Http
 * 
 * File metadata
 * @package Inane\Http\FileServer
 * @version 0.2.0
 */
class FileServer {
	protected $_resume = true;
	protected $_bandwidth = 0;
	
	protected $_file;
	protected $_name;
	
	/**
	 * @return number
	 */
	public function getBandwidth() {
		return $this->_bandwidth;
	}
	
	/**
	 * @param number $_bandwidth
	 */
	public function setBandwidth($_bandwidth) {
		$this->_bandwidth = $_bandwidth;
		return $this;
	}

	/**
	 * Prepare a file for serving
	 * 
	 * @param FileInfo $file
	 */
	public function __construct($file) {
		if (! $file instanceof FileInfo)
			$file = new FileInfo($file);
		
		$this->_file = $file;
	}

	/**
	 * Server the file via http
	 * 
	 * @return boolean
	 */
	public function serve() {
		if (! $this->_file->isValid()) {
			return false;
		}
		
		if ($this->_resume) {
			if (isset($_SERVER['HTTP_RANGE'])) { // check if http_range is sent by browser (or download manager)
				list($a, $range) = explode("=", $_SERVER['HTTP_RANGE']);
				ereg("([0-9]+)-([0-9]*)/?([0-9]*)", $range, $range_parts); // parsing Range header
				$byte_from = $range_parts[1]; // the download range : from $byte_from ...
				$byte_to = $range_parts[2]; // ... to $byte_to
			} else if (isset($_ENV['HTTP_RANGE'])) { // some web servers do use the $_ENV['HTTP_RANGE'] instead
				list($a, $range) = explode("=", $_ENV['HTTP_RANGE']);
				ereg("([0-9]+)-([0-9]*)/?([0-9]*)", $range, $range_parts); // parsing Range header
				$byte_from = $range_parts[1]; // the download range : from $byte_from ...
				$byte_to = $range_parts[2]; // ... to $byte_to
			} else {
				$byte_from = 0; // if no range header is found, download the whole file from byte 0 ...
				$byte_to = $this->_file->getSize() - 1; // ... to the last byte
			}
			if ($byte_to == "") // if the end byte is not specified, ...
				$byte_to = $this->_file->getSize() - 1; // ... set it to the last byte of the file
			header("HTTP/1.1 206 Patial Content"); // send the partial content header
			// ... else, download the whole file
		} else {
			$byte_from = 0;
			$byte_to = $this->_file->getSize() - 1;
		}
		
		$download_range = $byte_from . "-" . $byte_to . "/" . $this->_file->getSize(); // the download range
		$download_size = $byte_to - $byte_from; // the download length
		

		// download speed limitation
		if (($speed = $this->_bandwidth) > 0) // determine the max speed allowed ...
			$sleep_time = (8 / $speed) * 1e6; // ... if "max_speed" = 0 then no limit (default)
		else
			$sleep_time = 0;
			
			// send the headers
		header("Pragma: public"); // purge the browser cache
		header("Expires: 0"); // ...
		header("Cache-Control:"); // ...
		header("Cache-Control: public"); // ...
		header("Content-Description: File Transfer"); //
		header("Content-Type: " . $this->_file->getMimetype()); // file type
		header('Content-Disposition: attachment; filename="' . $this->_file->getFilename() . '";');
		header("Content-Transfer-Encoding: binary"); // transfer method
		header("Content-Range: $download_range"); // download range
		header("Content-Length: $download_size"); // download length
		

		// send the file content
		$fp = fopen($this->_file->getPathname(), "r"); // open the file
		if (! fp)
			exit(); // if $fp is not a valid stream resource, exit
		fseek($fp, $byte_from); // seek to start of missing part
		while ( ! feof($fp) ) { // start buffered download
			set_time_limit(0); // reset time limit for big files (has no effect if php is executed in safe mode)
			print(fread($fp, 1024 * 8)); // send 8ko
			flush();
			usleep($sleep_time); // sleep (for speed limitation)
		}
		fclose($fp); // close the file
		exit();
	}
}
