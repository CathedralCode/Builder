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
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <philip@inane.co.za>
 */
namespace Inane\Http;

use Inane\File\FileInfo;

/**
 * Serve file over http with resume support
 * 
 * @package Inane\Http\FileServer
 * @version 0.5.0
 */
class FileServer {
	/**
	 * File Information
	 * 
	 * @var FileInfo
	 */
	protected $_file;
	
	/**
	 * Alternative file name for download
	 * 
	 * @var string
	 */
	protected $_name;

	/**
	 * Prepare a file for serving
	 * 
	 * @param FileInfo|string $file FileInfo object OR path to file
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
	 * Filename of download
	 * 
	 * @return string
	 */
	public function getName() {
		if (isset($this->_name))
			return $this->_name;
		
		return $this->_file->getFilename();	
	}
	
	/**
	 * Set a different name for download
	 *   or null for realname
	 * 
	 * @param string $name
	 * @return \Inane\Http\FileServer
	 */
	public function setName($name) {
		if ($name === null) {
			unset($this->_name);
		} else {
			$name = preg_replace('([^\w\s\d\.\-_~,;:\[\]\(\]]|[\.]{2,})', '', $name);
			if ($name != '')
				$this->_name = $name;
		}
		return $this;
	}

	/**
	 * Server the file via http
	 * 
	 * @param \Zend\Http\Request $request	zf2 request used to get range
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
		
		$fileSize = $this->_file->getSize();
		
		if ($requestHeaders->has('Range')) { // check if http_range is sent by browser (or download manager)
			$response->setStatusCode(206);
			
			list($unit, $range) = explode('=', $requestHeaders->get('Range')->toString());
			$ranges = explode(',', $range);
			$ranges = explode('-', $ranges[0]);
			
			$byte_from = (int) $ranges[0];
			$byte_to = (int) ($ranges[1] == '' ? $fileSize - 1 : $ranges[1]);
			$download_size = $byte_to - $byte_from + 1; // the download length

			$download_range = 'bytes ' . $byte_from . "-" . $byte_to . "/" . $fileSize; // the download range
			$headers->addHeader(new \Zend\Http\Header\ContentRange($download_range));
		} else {
			$response->setStatusCode(200);
			
			$byte_from = 0; // if no range header is found, download the whole file from byte 0 ...
			$byte_to = $this->_file->getSize() - 1; // -1 ... to the last byte
			$download_size = $fileSize;
		}
		
		// send the headers
		$headers->addHeader(new \Zend\Http\Header\AcceptRanges('bytes'));
		$headers->addHeaderLine('Content-type', $this->_file->getMimetype());
		$headers->addHeaderLine("Pragma", "no-cache");
		$headers->addHeaderLine('Cache-Control', 'public, must-revalidate, max-age=0');
		$headers->addHeaderLine("Content-Length", $download_size);
		$headers->addHeaderLine("Content-Description", 'File Transfer');
		$headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . $this->getName() . '";');
		$headers->addHeaderLine("Content-Transfer-Encoding", "binary");
		
		// send the file content
		$fp = fopen($this->_file->getPathname(), "r"); // open the file
		fseek($fp, $byte_from); // seek to start of missing part
		$countent = fread($fp, $download_size);
		
		fclose($fp); // close the file
		$response->setHeaders($headers);
		$response->setContent($countent);
		
		return $response;
	}
}
