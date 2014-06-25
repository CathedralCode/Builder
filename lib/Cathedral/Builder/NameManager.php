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

use Zend\Db\Metadata\Metadata;

/**
 * NameManager
 * Used to generate any names used by the builders
 * @package Cathedral\Builder\Managers
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
	
	//Configuration
	private $_config = ['entitySingular' => ['enabled' => true, 'ignore' => []]];
	
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
	public $relationChildren = array();
	
	private $partNameModel = 'Model';
	private $partNameEntity = 'Entity';
	
	public $namespace;
	public $namespace_model;
	public $namespace_entity;
	
	/**
	 * Create NameManager instance
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
	
	/**
	 * Table to process
	 * 
	 * @param string $tableName
	 */
	public function setTableName($tableName) {
		if ($tableName != null) {
			$this->tableName = $tableName;
			$this->init();
		}
	}
	
	/**
	 * Namespace for the created classes
	 * 
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
		
		if (isset($this->tableName))
			$this->processClassNames();
	}
	
	/**
	 * Array of tables in database
	 * 
	 * @return string[]
	 */
	public function getTableNames() {
		return $this->tableNames;
	}
	
	/**
	 * Table Name
	 * 
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
	/**
	 * Load next table
	 * 
	 * @return boolean
	 */
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
	
	
	/**
	 * Enable/Disable the EntitySingular option
	 *  Leave empty to just get current status
	 * 
	 * @param bool $enabled
	 */
	public function entitySingular($enabled = null) {
		if (is_bool($enabled))
			$this->_config['entitySingular']['enabled'] = $enabled;
		
		return $this->_config['entitySingular']['enabled']; 
	}
	
	
	/**
	 * Return array of tables name to skip for entitySingular
	 * 
	 * @return array
	 */
	public function getEntitySingularIgnores() {
		return $this->_config['entitySingular']['ignore'];
	}
	
	
	/**
	 * Array of tables to ignore or string with tables delimited by pipe (|) or FALSE to clear list
     * e.g. array('users', 'towns') or "users|towns"
	 *  
	 * @param array|string|false $table
	 */
	public function setEntitySingularIgnores($tables) {
		if ($tables === false) {
			$this->_config['entitySingular']['ignore'] = [];
			$tables = [];
		} elseif (is_string($tables)) {
			$tables = explode('|', $tables);
		}
		$this->_config['entitySingular']['ignore'] = array_merge($this->_config['entitySingular']['ignore'], $tables);
		
		return $this;
	}
	
	/**
	 * Start processing table
	 */
	protected function init() {
		if (isset($this->tableName) && (isset($this->namespace))) {
			$this->processClassNames();
			$this->processProperties();
		}
	}
	
	/**
	 * Generate the related class names
	 */
	protected function processClassNames() {
		$modelBaseName = ucwords($this->tableName);
		
		$trimWith = $this->entitySingular() ? (in_array($this->tableName, $this->getEntitySingularIgnores()) ? '' : 's') : '';
		
		$this->modelName			= "{$modelBaseName}Table";
		$this->entityName			= rtrim($modelBaseName, $trimWith);
		$this->entityAbstractName	= "{$this->entityName}Abstract";
		$this->entityVariable		= rtrim($this->tableName, $trimWith);
		
		$this->modelPath			= $this->modulePath."/{$this->partNameModel}/{$this->modelName}.php";
		$this->entityPath			= $this->modulePath."/{$this->partNameEntity}/{$this->entityName}.php";
		$this->entityAbstractPath	= $this->modulePath."/{$this->partNameEntity}/{$this->entityAbstractName}.php";
	}
	
	/**
	 * Generate properties
	 * 
	 * @throws Exception
	 */
	protected function processProperties() {
		try {
			$table = $this->metadata->getTable($this->tableName);
		} catch (\Exception $e) {
			throw new Exception("Table: {$this->tableName}", Exception::ERROR_DB_TABLE);
		}
		
		$columns = $table->getColumns();
		$constraints = $table->getConstraints();
		$this->primaryIsSequence = false;
		
		//PRIMARY
		foreach ($constraints AS $constraint) {
			if ($constraint->isPrimaryKey()) {
				$primaryColumns = $constraint->getColumns();
				$this->primary = $primaryColumns[0];
				
				$sql = "SHOW COLUMNS FROM {$this->tableName} WHERE Extra = 'auto_increment' AND Field = '{$this->primary}'";
				$stmt = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
				$result = $stmt->execute();
				if ($result->count())
					$this->primaryIsSequence = true;
			}
		}
		
		//PROPERTIES
		$this->properties = array();
		foreach ($columns as $column) {
			$isPrimary = false;
			if ($column->getName() == $this->primary)
				$isPrimary = true;
			
			$type = self::TYPE_STRING;
			$dataType = $column->getDataType();
			if (strpos($dataType, self::TYPE_INT) !== false) {
				$type = self::TYPE_INT;
			} elseif (strpos($dataType, 'bit') !== false) {
				$type = self::TYPE_INT;
			} elseif (strpos($dataType, self::TYPE_FLOAT) !== false) {
				$type = self::TYPE_FLOAT;
			} elseif (strpos($dataType, self::TYPE_DOUBLE) !== false) {
				$type = self::TYPE_DOUBLE;
			} elseif (strpos($dataType, 'decimal') !== false) {
				$type = self::TYPE_NUMBER;
			}
			
			$default = $column->getColumnDefault();
			if ($default == "CURRENT_TIMESTAMP") {
				$default = null;
			} elseif (strpos($dataType, 'bit') !== false) {
				$default = (string) $default;
				$default = (boolean) (int) $default[2];
			}
			
			//$this->properties[$column->getName()] = ['datatype' => $dataType, 'type' => $type, 'default' => $default, 'primary' => $isPrimary];
			$this->properties[$column->getName()] = ['type' => $type, 'default' => $default, 'primary' => $isPrimary];
	    }
	    
	    # Child tables
	    $this->relationChildren = [];
	    $sql = "SELECT DISTINCT TABLE_NAME AS tablename FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = 'fk_{$this->tableName}' AND TABLE_SCHEMA=(SELECT DATABASE() AS db FROM DUAL)";
	    $stmt = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
	    $result = $stmt->execute();
	    while ($result->next()) {
	    	$this->relationChildren[] = $result->current()['tablename'];
	    }
	}
}