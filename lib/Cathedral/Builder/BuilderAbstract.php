<?php
/*
 * This file is part of the Cathedral package.
 *
 * (c) Philip Michael Raab <peep@cathedral.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cathedral\Builder;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\DocBlockGenerator;

/**
 *
 * @author Philip Michael Raab<peep@cathedral.co.za>
 */
abstract class BuilderAbstract implements BuilderInterface {
	
	const version = '0.3';
	
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
					'description' => 'Philip Michael Raab<philip@magbladepropellers.com>'),
				array(
					'name' => 'version',
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
		if (file_exists($this->getPath())) {
			return true;
		}
		return false;
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
		if (!$this->existsFile()||$overwrite) {
			if (file_put_contents($this->getPath(), $this->getCode(), LOCK_EX)) {
				return true;
			}
		}
		return false;
	}
}