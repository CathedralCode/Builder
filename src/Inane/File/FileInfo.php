<?php
namespace Inane\File;

/** 
 * @author philip
 * 
 */
class FileInfo {
	protected $_is_valid = false;
	protected $_name;
	protected $_path;
	protected $_extension;
	protected $_type;
	protected $_size;
	protected $_md5;

	public function __construct($path) {
		if (file_exists($path)) {
			$this->_path = $path;
			$this->_extension = strtolower(substr(strrchr($path, "."), 1));
			$this->_type = (new \finfo())->file($path, FILEINFO_MIME_TYPE);
			$this->_size = filesize($path);
			$this->_name = basename($path);
			$this->_md5 = md5_file($path);
			$this->_is_valid = true;
		}
	}
	
	/**
	 * @return bool
	 */
	public function isValid() {
		return $this->_is_valid;
	}

	/**
	 * @return string|null
	 */
	public function getName() {
		return $this->_name;
	}
	
	/**
	 * @return string|null
	 */
	public function getPath() {
		return $this->_path;
	}

	/**
	 * @return string|null
	 */
	public function getExtension() {
		return $this->_extension;
	}

	/**
	 * @return string|null
	 */
	public function getType() {
		return $this->_type;
	}

	/**
	 * @return int|null
	 */
	public function getSize() {
		return $this->_size;
	}

	/**
	 * @return string|null
	 */
	public function getMd5() {
		return $this->_md5;
	}
}
