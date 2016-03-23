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
 * @version 0.3.0
 */
class FileServer {
	protected $_resume = true;
	
	protected $_file;
	protected $_name;

	/**
	 * Prepare a file for serving
	 * 
	 * @param FileInfo $file
	 */
	public function __construct($file) {
		if (! $file instanceof FileInfo) {
			$file = new FileInfo($file);
			/* @var $file \Inane\File\FileInfo */
			$file->setInfoClass('\Inane\File\FileInfo');
		}
		$this->_file = $file;
	}
	
	/**
	 * Server the file via http
	 * 
	 * @param \Zend\Http\Request $request
	 * @return \Zend\Http\Response
	 */
	public function serve(\Zend\Http\Request $request = null) {
		if ($request == null)
			$request = new \Zend\Http\Request();
		
		$requestHeaders = $request->getHeaders();
		
		$response = new \Zend\Http\Response();
		$headers = new \Zend\Http\Headers();
		
		if (! $this->_file->isValid()) {
			$response->setStatusCode(404);
			$response->setContent('file invalid:' . $this->_file->getPathname());
			return $response;
		}
		
		if ($this->_resume) {
			if ($requestHeaders->has('Range')) { // check if http_range is sent by browser (or download manager)
				list($a, $range) = explode("=", $requestHeaders->get('Range')->toString());
				ereg("([0-9]+)-([0-9]*)/?([0-9]*)", $range, $range_parts); // parsing Range header
				$byte_from = $range_parts[1]; // the download range : from $byte_from ...
				$byte_to = $range_parts[2]; // ... to $byte_to
			} else {
				$byte_from = 0; // if no range header is found, download the whole file from byte 0 ...
				$byte_to = $this->_file->getSize() - 1; // -1 ... to the last byte
			}
			if ($byte_to == "") // if the end byte is not specified, ...
				$byte_to = $this->_file->getSize() - 1; // -1 ... set it to the last byte of the file
			
			$response->setStatusCode(206);
			// ... else, download the whole file
		} else {
			$response->setStatusCode(200);
			$byte_from = 0;
			$byte_to = $this->_file->getSize() - 1; // -1
		}
		
		$download_range = 'bytes ' . $byte_from . "-" . $byte_to . "/" . $this->_file->getSize(); // the download range
		$download_size = $byte_to - $byte_from + 1; // the download length
		$length = $download_size;
			
		// send the headers
		$headers->addHeaderLine('Content-type', $this->_file->getMimetype());
		$headers->addHeaderLine("Pragma", "no-cache");
		$headers->addHeaderLine('Cache-Control','public, must-revalidate, max-age=0');
		$headers->addHeader(new \Zend\Http\Header\AcceptRanges('bytes'));
		$headers->addHeaderLine("Content-Length",$download_size);
		$headers->addHeader(new \Zend\Http\Header\ContentRange($download_range));
		$headers->addHeaderLine("Content-Description",'File Transfer');
		$headers->addHeaderLine('Content-Disposition','attachment; filename="' . $this->_file->getFilename() . '";');
		$headers->addHeaderLine("Content-Transfer-Encoding","binary");
		
		// send the file content
		$fp = fopen($this->_file->getPathname(), "r"); // open the file
		if (! fp) {
			$response->setStatusCode(404);
			$response->setContent('file invalid:' . $this->_file->getPathname());
			return $response;
		}
		fseek($fp, $byte_from); // seek to start of missing part
		$out = '';
		while ( $length ) { // start buffered download
			set_time_limit(0); // reset time limit for big files (has no effect if php is executed in safe mode)
			$read = ($length > 8192) ? 8192 : $length;
			$length -= $read;
			$out .= fread($fp, $read);
		}
		fclose($fp); // close the file
		$response->setHeaders($headers);
		$response->setContent($out);
		
		return $response;
	}
}
