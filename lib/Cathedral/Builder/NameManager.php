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

use Zend\Db\Metadata\Metadata;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

/**
 * NameManager
 * 
 * Used to generate any names used by the builders
 * 
 * @author Philip Michael Raab<peep@cathedral.co.za>
 *
 */
class NameManager {
	
	protected $metadata;
	protected $tableNames;
	protected $tableNamesIndex;
	
	public $tableName;
	
	public $modelName;
	public $entityName;
	public $entityAbstractName;
	
	public $modulePath;
	public $modelPath;
	public $entityPath;
	public $entityAbstractPath;
	
	public $entityVariable;
	
	public $primary;
	public $properties = array();
	
	private $partNameModel = 'Model';
	private $partNameEntity = 'Entity';
	
	public $namespace;
	public $namespace_model;
	public $namespace_entity;
	
	/**
	 * 
	 * @param string $namespace
	 * @param string $tableName
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct($namespace, $tableName = null) {
		$this->metadata = new Metadata(\Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter());
		$this->tableNames = $this->metadata->getTableNames();
		
		if (!isset($tableName)) {
			$tableName = $this->tableNames[0]; 
		}
		
		try {
			$this->setNamespace($namespace);
		} catch (\InvalidArgumentException $e) {
			throw new Exception\InvalidArgumentException('"namespace" should be a valid Module');
		}
		
		$this->setTableName($tableName);
	}
	
	public function setTableName($tableName) {
		$this->tableName = $tableName;
		
		$this->init();
	}
	
	/**
	 * @param string $namespace
	 * @throws Exception\InvalidArgumentException
	 */
	public function setNamespace($namespace) {
		$pathBase = getcwd()."/module/{$namespace}/src/{$namespace}";
		if (!file_exists($pathBase)) {
			throw new Exception\InvalidArgumentException('"namespace" should be a valid Module');
		}
		
		$this->modulePath = $pathBase;
		$this->namespace = $namespace;
		$this->namespace_model = "{$this->namespace}\\{$this->partNameModel}";
		$this->namespace_entity = "{$this->namespace}\\{$this->partNameEntity}";
		
		$this->processClassNames();
	}
	
	/**
	 * @return string[]
	 */
	public function getTableNames() {
		return $this->tableNames;
	}
	
	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
	public function nextTable() {
		if (!isset($this->tableNamesIndex)) {
			$this->tableNamesIndex = 0;
		} else {
			$this->tableNamesIndex = $this->tableNamesIndex + 1;
		}
		
		if ($this->tableNamesIndex == sizeof($this->tableNames)) {
			return false;
		}
		
		$this->setTableName($this->tableNames[$this->tableNamesIndex]);
		return true;
	}
	
	protected function init() {
		$this->processClassNames();
		$this->processProperties();
	}
	
	protected function processClassNames() {
		$modelBaseName = ucwords($this->tableName);
		
		$this->modelName			= "{$modelBaseName}Table";
		$this->entityName			= rtrim($modelBaseName, 's');
		$this->entityAbstractName	= "{$this->entityName}Abstract";
		$this->entityVariable		= rtrim($this->tableName, 's');
		
		$this->modelPath			= $this->modulePath."/{$this->partNameModel}/{$this->modelName}.php";
		$this->entityPath			= $this->modulePath."/{$this->partNameEntity}/{$this->entityName}.php";
		$this->entityAbstractPath	= $this->modulePath."/{$this->partNameEntity}/{$this->entityAbstractName}.php";
	}
	
	protected function processProperties() {
		try {
			$table = $this->metadata->getTable($this->tableName);
		} catch (\Exception $e) {
			throw new Exception("Table: {$this->tableName}", Exception::ERROR_DB_TABLE);
		}
		$columns = $table->getColumns();
		$constraints = $table->getConstraints();
		
		//PRIMARY
		foreach ($constraints AS $constraint) {
			if ($constraint->isPrimaryKey()) {
				$primaryColumns = $constraint->getColumns();
				$this->primary = $primaryColumns[0];
			}
		}
		
		//PROPERTIES
		$this->properties = array();
		foreach ($columns as $column) {
			$isPrimary = false;
			if ($column->getName() == $this->primary ) {
				$isPrimary = true;
			}
			$this->properties[$column->getName()] = ['type' => $column->getDataType(), 'primary' => $isPrimary];			
	    }
	}
}