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

/**
 * NameManager
 * 
 * Used to generate any names used by the builders
 * 
 * @author Philip Michael Raab<peep@cathedral.co.za>
 *
 */
class NameManager {
	
	/**#@+
	 * Constant values
	 */
	const TYPE_BOOLEAN  = 'boolean';
	const TYPE_BOOL     = 'bool';
	const TYPE_NUMBER   = 'number';
	const TYPE_INTEGER  = 'integer';
	const TYPE_INT      = 'int';
	const TYPE_FLOAT    = 'float';
	const TYPE_DOUBLE   = 'double';
	const TYPE_STRING   = 'string';
	const TYPE_ARRAY    = 'array';
	const TYPE_CONSTANT = 'constant';
	const TYPE_NULL     = 'null';
	const TYPE_OBJECT   = 'object';
	const TYPE_OTHER    = 'other';
	/**#@-*/
	
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
			$isSequence = false;
			if ($column->getName() == $this->primary ) {
				$isPrimary = true;
				
				$sql = "SHOW COLUMNS FROM {$this->tableName} WHERE Field = '{$column->getName()}'";
				$driver = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->getDriver();
				$stmt = $driver->createStatement($sql);
				$stmt->prepare($parameters);
				$result = $stmt->execute();
				$resultSet = new \Zend\Db\ResultSet\ResultSet();
				$resultSet->initialize($result);
				$row = $resultSet->current();
				if ($row->Extra == 'auto_increment') {
					$isSequence = true;
				}
			}
			
			$type = self::TYPE_STRING;
			$dataType = $column->getDataType();
			if (strpos($dataType, self::TYPE_INT)) {
				$type = self::TYPE_INT;
			} elseif (strpos($dataType, 'bit')) {
				$type = self::TYPE_INT;
			} elseif (strpos($dataType, self::TYPE_FLOAT)) {
				$type = self::TYPE_FLOAT;
			} elseif (strpos($dataType, self::TYPE_DOUBLE)) {
				$type = self::TYPE_DOUBLE;
			} elseif (strpos($dataType, 'decimal')) {
				$type = self::TYPE_NUMBER;
			}
			
			$columnDefault = $column->getColumnDefault();
			$default = $columnDefault;
			if ($columnDefault == "CURRENT_TIMESTAMP") {
				//$default = "CURRENT_TIMESTAMP";
			} elseif (strpos($dataType, 'bit')) {
				$default = (string) $columnDefault;
				$default = (boolean) (int) $default[2];
			}
			
			$this->properties[$column->getName()] = ['type' => $type, 'default' => $default, 'primary' => $isPrimary, 'sequence' => $isSequence];			
	    }
	}
}