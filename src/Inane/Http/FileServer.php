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
namespace Inane\Http;

use Inane\File\FileInfo;
use Inane\Observer\InaneSubject;
use Inane\Observer\InaneObserver;

/**
 * Serve file over http with resume support
 * 
 * @package Inane\Http\FileServer
 * @namespace \Inane\Http
 * @version 0.7.2
 */
class FileServer extends InaneSubject {
	private $observers = [];
	
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
	 * Force download for text type contect
	 *
	 * @var bool
	 */
	protected $_forceDownload = true;
	
	/**
	 * Limit speed of transfer (0 = unlimited)
	 *
	 * @var int
	 */
	protected $_bandwidth = 0;
	
	/**
	 * Sleep time to limit bandwidth
	 *
	 * @var int
	 */
	protected $_sleep = 0;
	
	/**
	 * File size served
	 *
	 * @var int
	 */
	protected $_progress = 0;
	protected $_percent = 0;

	/**
	 * Progress of download
	 *
	 * @return [];
	 */
	public function getProgress() {
		return [
			'filename' => $this->_file->getFilename(),
			'progress' => $this->_progress,
			'total' => $this->_file->getSize()];
	}

	/**
	 * @param int $progress
	 * @return \Inane\Http\FileServer
	 */
	protected function addProgress($progress) {
		$this->_progress += $progress;
		if ($this->_progress > $this->_file->getSize())
			$this->_progress = $this->_file->getSize();
		
		$percent = round($this->_progress / $this->_file->getSize() * 100, 0);
		if ($percent != $this->_percent) {
			$this->notify();
			$this->_percent = $percent;
		}
		return $this;
	}

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
	 * {@inheritDoc}
	 * @see \Inane\Observer\InaneSubject::attach()
	 */
	public function attach(InaneObserver $observer_in) {
		//could also use array_push($this->observers, $observer_in);
		$this->observers[] = $observer_in;
		
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \Inane\Observer\InaneSubject::detach()
	 */
	public function detach(InaneObserver $observer_in) {
		//$key = array_search($observer_in, $this->observers);
		foreach ( $this->observers as $okey => $oval ) {
			if ($oval == $observer_in) {
				unset($this->observers[$okey]);
			}
		}
		
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \Inane\Observer\InaneSubject::notify()
	 */
	public function notify() {
		foreach ( $this->observers as $obs ) {
			$obs->update($this);
		}
		
		return $this;
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
	 * Gets download limit
	 * 
	 * @return the int
	 */
	public function getBandwidth() {
		return $this->_bandwidth;
	}

	/**
	 * Sets download limit 0 = unlimited
	 * 
	 * This is a rough kb/s speed. But very rough
	 * 
	 * @param  $_bandwidth
	 * @return \Inane\Http\FileServer
	 */
	public function setBandwidth($_bandwidth) {
		$this->_sleep = $this->_bandwidth = $_bandwidth * 4.3;
		if ($this->_bandwidth > 0)
			$this->_sleep = (8 / $this->_bandwidth) * 1e6;
		
		return $this;
	}

	/**
	 * Force files to download and not open in browser
	 *
	 * @param bool $state optional true|false, empty returns current state
	 * @return FileServer|bool
	 */
	public function forceDownload($state = null) {
		if ($state === null)
			return $this->_forceDownload;
		
		$this->_forceDownload = $state;
		
		return $this;
	}

	protected function sendHeaders(\Zend\Http\Headers $headers, $status) {
		$headerArray = explode("\n", $headers->toString());
		if ($status == 206)
			header("HTTP/1.1 206 Patial Content");
		else 
			header("HTTP/1.1 200 OK");
		foreach ( $headerArray as $headerLine ) {
			if (strlen($headerLine) > 0) {
				header($headerLine);
			}
		}
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
			
			$byte_from = 0; // no range, download from 0
			$byte_to = $this->_file->getSize() - 1; // the last byte
			$download_size = $fileSize;
		}
		
		// send the headers
		$headers->addHeader(new \Zend\Http\Header\AcceptRanges('bytes'));
		$headers->addHeaderLine('Content-type', $this->_file->getMimetype());
		$headers->addHeaderLine("Pragma", "no-cache");
		$headers->addHeaderLine('Cache-Control', 'public, must-revalidate, max-age=0');
		$headers->addHeaderLine("Content-Length", $download_size);
		
		if ($this->forceDownload()) {
			$headers->addHeaderLine("Content-Description", 'File Transfer');
			$headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . $this->getName() . '";');
			$headers->addHeaderLine("Content-Transfer-Encoding", "binary");
		}
		
		$fp = fopen($this->_file->getPathname(), "r"); // open file
		fseek($fp, $byte_from); // seek to start byte
		$this->_progress = $byte_from;
		
		if ($this->_bandwidth > 0) {
			$this->sendHeaders($headers, $response->getStatusCode());
			
			$buffer_size = 1024 * 8; // 8kb
			
			while ( ! feof($fp) ) { // start buffered download
				set_time_limit(0); // reset time limit for big files
				print(fread($fp, $buffer_size));
				flush();
				$this->addProgress($buffer_size);
				usleep($this->_sleep); // sleep for speed limitation
			}
			$this->addProgress(1);
			fclose($fp); // close file
			
			exit();
		}
		
		$countent = fread($fp, $download_size);
		
		fclose($fp); // close the file
		$response->setHeaders($headers);
		$response->setContent($countent);
		
		return $response;
	}
}
