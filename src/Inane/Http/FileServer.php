<?php
namespace Inane\Http;

use Inane\File\FileInfo;

/** 
 * @author philip
 * 
 */
class FileServer {
	protected $_resume = true;
	protected $_bandwidth = 5;
	
	protected $_file;
	
	protected $_name;

	public function __construct($file) {
		if (! $file instanceof FileInfo)
			$file = new FileInfo($file);
		
		$this->_file = $file;
	}

	public function serve() {
		if (! $this->_file->isValid()) {
			return false;
		}
		
		// DEFAULT send whole file
		$byte_from = 0; // download the whole file from byte 0 ...
		$byte_to = $this->_file->getSize() - 1; // ... to the last byte
		
		if ($this->_resume) {
			if (isset($_SERVER['HTTP_RANGE'])) { // check if http_range is sent by browser (or download manager)
				list($a, $range) = explode("=", $_SERVER['HTTP_RANGE']);
				//ereg("([0-9]+)-([0-9]*)/?([0-9]*)", $range, $range_parts); // parsing Range header
				//$byte_from = $range_parts[1]; // the download range : from $byte_from ...
				//$byte_to = $range_parts[2]; // ... to $byte_to
			} else if (isset($_ENV['HTTP_RANGE'])) { // some web servers do use the $_ENV['HTTP_RANGE'] instead
				list($a, $range) = explode("=", $_ENV['HTTP_RANGE']);
				//ereg("([0-9]+)-([0-9]*)/?([0-9]*)", $range, $range_parts); // parsing Range header
				//$byte_from = $range_parts[1]; // the download range : from $byte_from ...
				//$byte_to = $range_parts[2]; // ... to $byte_to
			}
			
			ereg("([0-9]+)-([0-9]*)/?([0-9]*)", $range, $range_parts); // parsing Range header
			$byte_from = $range_parts[1]; // the download range : from $byte_from ...
			$byte_to = $range_parts[2]; // ... to $byte_to
			
			if ($byte_to == "") // if the end byte is not specified, ...
				$byte_to = $this->_file->getSize() - 1; // ... set it to the last byte of the file
			
			header("HTTP/1.1 206 Patial Content"); // send the partial content header
			// ... else, download the whole file
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
		header("Content-Type: " . $this->_file->getType()); // file type
		header('Content-Disposition: attachment; filename="' . $this->_file->getName() . '";');
		header("Content-Transfer-Encoding: binary"); // transfer method
		header("Content-Range: $download_range"); // download range
		header("Content-Length: $download_size"); // download length
		

		// send the file content
		$fp = fopen($this->_file->getPath(), "r"); // open the file
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
