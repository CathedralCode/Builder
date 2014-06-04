<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@cathedral.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2014 Philip Michael Raab <peep@cathedral.co.za>
 */

namespace Cathedral\Builder;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;

/**
 * Abstract for builders
 * @package Cathedral\Builder\Abstracts
 */
abstract class BuilderAbstract implements BuilderInterface {
	
	/**
	 * Generated code version
	 */
	const version = Version::BUILDER_VERSION;
	
	const FILE_MISSING	= -1;
	const FILE_OUTDATED	= 0;
	const FILE_MATCH	= 1;
	
	const TYPE_MODEL = 'DataTable';
	const TYPE_ENTITYABSTRACT = 'EntityAbstract';
	const TYPE_ENTITY = 'Entity';
	
	protected $type;
	
	/**
	 * @var BuilderManager
	 */
	protected $builderManager;
	
	/**
	 * @var \Zend\Code\Generator\FileGenerator
	 */
	protected $_file;
	
	/**
	 * @var \Zend\Code\Generator\ClassGenerator
	 */
	protected $_class;
	
	public function __construct(BuilderManager &$builderManager) {
		if (!isset($this->type)) {
			throw new Exception\ConfigurationException('A class based on BuilderAbstract has an unset type property');
		}
		
		$this->builderManager = $builderManager;
		$this->init();
	}

	/**
	 *
	 * @return NameManager
	 */
	protected function getNames() {
		return $this->builderManager->getNames();
	}

	/**
	 *
	 * @return string
	 */
	protected function getPath() {
		switch ($this->type) {
			case self::TYPE_MODEL :
				$path = $this->getNames()->modelPath;
				break;
			
			case self::TYPE_ENTITYABSTRACT :
				$path = $this->getNames()->entityAbstractPath;
				break;
			
			case self::TYPE_ENTITY :
				$path = $this->getNames()->entityPath;
				break;
			
			default :
				;
				break;
		}
		return $path;
	}

	protected function init() {
		$this->_file = new FileGenerator();
		$this->_class = new ClassGenerator();
		
		$this->setupFile();
		$this->setupFileDocBlock();
		
		$this->setupClass();
		$this->setupMethods();
	}

	protected function setupFileDocBlock() {
		$docBlock = DocBlockGenerator::fromArray(array(
			'shortDescription' => $this->type,
			'longDescription' => "Generated {$this->type}",
			'tags' => array(
				array(
					'name' => 'author',
					'description' => 'Philip Michael Raab<philip@cathedral.co.za>'),
				array(
					'name' => 'builder_version',
					'description' => self::version))));
		$this->_file->setDocBlock($docBlock);
	}

	abstract protected function setupFile();

	abstract protected function setupClass();

	abstract protected function setupMethods();

	protected function buildMethod($name, $flag = MethodGenerator::FLAG_PUBLIC) {
		$method = new MethodGenerator();
		$method->setName($name);
		$method->addFlag($flag);
		return $method;
	}

	/* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderInterface::getCode()
	 */
	public function getCode() {
		return $this->_file->generate();
	}

	/* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderInterface::existsFile()
	 */
	public function existsFile() {
		$file =$this->getPath();
		if (file_exists($file)) {
			if ($this->type == self::TYPE_ENTITY) {
				return self::FILE_MATCH;
			}
			
			$data = file_get_contents($file);
			if (strpos($data, "@builder_version ".Version::BUILDER_VERSION) !== FALSE) {
				return self::FILE_MATCH;
			} else {
				return self::FILE_OUTDATED;
			}
		}
		return self::FILE_MISSING;
	}

	/**
	 * Writes code to file.
	 *  Overwrite Exception:
	 *  Type Entity is never overwitten
	 *  
	 * @param string $overwrite
	 * @return boolean
	 */
	public function writeFile($overwrite = false) {
		$overwrite = ($this->type == self::TYPE_ENTITY) ? false : $overwrite;
		if ($this->existsFile()||$overwrite) {
			$checkPath = dirname($this->getPath());
			if (is_writable($checkPath)) {
				if (file_put_contents($this->getPath(), $this->getCode(), LOCK_EX)) {
					chmod($this->getPath(), 0664);
					return true;
				}
			} else {
				throw new Exception\PermissionException('Write access to Entity OR Model dirs denied');
			}
		}
		return false;
	}
}