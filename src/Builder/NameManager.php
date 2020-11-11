<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2019 Philip Michael Raab <peep@inane.co.za>
 */

namespace Cathedral\Builder;

use Cathedral\Builder\Exception\DatabaseException;
use Laminas\Db\Metadata\Source\Factory as MetadataFactory;
use Laminas\Db\Sql\TableIdentifier;

use Exception;
use Throwable;

/**
 * Cathedral\Builder\NameManager
 *
 * Used to generate any names used by the builders
 *
 * @package Cathedral\Builder\Managers
 * @version 0.1.0
 */
class NameManager {

	/**
	 * #@+
	 * Constant values
	 */
	const TYPE_BOOLEAN = 'boolean';
	const TYPE_BOOL = 'bool';
	const TYPE_NUMBER = 'number';
	const TYPE_INTEGER = 'integer';
	const TYPE_INT = 'int';
	const TYPE_FLOAT = 'float';
	const TYPE_DOUBLE = 'double';
	const TYPE_STRING = 'string';
	const TYPE_ARRAY = 'array';
	const TYPE_CONSTANT = 'constant';
	const TYPE_NULL = 'null';
	const TYPE_OBJECT = 'object';
	const TYPE_OTHER = 'other';
	const TYPE_JSON = 'json';
	/**
	 * #@-
	 */

	// Configuration
	private $_config = [
		'entitySingular' => [
			'enabled' => true,
			'ignore' => []
		]
	];

	protected $metadata;
	protected $tableNames;
	protected $tableNamesIndex;

	/**
	 * @var string the table name
	 */
    public $tableName;
    /**
	 * @var TableIdentifier the table Identifier
	 */
	public $tableIdentifier;

	/**
	 * @var string the model class
	 */
	public $modelName;
	/**
	 *
	 * @var string the entity class
	 */
	public $entityName;
	/**
	 * @var string the abstract entity class
	 */
	public $entityAbstractName;

	/**
	 * @var string the module path
	 */
	public $modulePath;
	/**
	 * @var string the model path
	 */
	public $modelPath;
	/**
	 * @var string the entity path
	 */
	public $entityPath;
	/**
	 * @var string the abstract entity path
	 */
	public $entityAbstractPath;

	/**
	 *
	 * @var string
	 */
	public $entityVariable;

	/**
	 * Primary key column
	 *
	 * @var string the primary key column
	 */
	public $primary;
	/**
	 * Primary key type
	 *
	 * @var string
	 */
	public $primaryType;
	/**
	 * Table columns
	 *
	 * @var array
	 */
	public $properties = [];
	/**
	 *
	 * @var string
	 */
	public $propertiesCSV;
	/**
	 *
	 * @var array
	 */
	public $relationChildren = [];

	private $partNameModel = 'Model';
	private $partNameEntity = 'Entity';

	/**
	 *
	 * @var string
	 */
	public $namespace;
	/**
	 *
	 * @var string
	 */
	public $namespace_model;
	/**
	 *
	 * @var string
	 */
	public $namespace_entity;

	/**
	 * Create NameManager instance
	 *
	 * @param string $namespace
	 * @param string $tableName
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct($namespace = 'Application', $tableName = null) {
		$this->metadata = MetadataFactory::createSourceFromAdapter(\Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter());
		$this->tableNames = $this->metadata->getTableNames();

		if (! isset($tableName)) {
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
	 * @return NameManager
	 */
	public function setTableName($tableName): NameManager {
		if ($tableName != null && in_array($tableName, $this->getTableNames())) {
            $this->tableName = $tableName;
            $this->tableIdentifier = new TableIdentifier($tableName);
			$this->init();
		}
		return $this;
	}

	/**
	 * Namespace for the created classes
	 *
	 * @param string $namespace
	 * @throws Exception\InvalidArgumentException
	 * @return NameManager
	 */
	public function setNamespace(string $namespace): NameManager {
		$pathBase = getcwd() . "/module/{$namespace}/src";
		if (! file_exists($pathBase)) {
			throw new Exception\InvalidArgumentException('"namespace" should be a valid Module');
		}

		$this->modulePath = $pathBase;
		$this->namespace = $namespace;
		$this->namespace_model = "{$this->namespace}\\{$this->partNameModel}";
		$this->namespace_entity = "{$this->namespace}\\{$this->partNameEntity}";

		if (isset($this->tableName)) $this->processClassNames();

		return $this;
	}

	/**
	 * Array of tables in database
	 *
	 * @return string[]
	 */
	public function getTableNames(): array {
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
		if (! isset($this->tableNamesIndex)) {
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
	 * Leave empty to just get current status
	 *
	 * @param bool $enabled
	 * @return boolean
	 */
	public function entitySingular($enabled = null) {
		if (is_bool($enabled)) $this->_config['entitySingular']['enabled'] = $enabled;

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
	 * e.g.
	 * ['users', 'towns'] or "users|towns"
	 *
	 * @param array|string|false $table
	 * @return \Cathedral\Builder\NameManager
	 */
	public function setEntitySingularIgnores($tables) {
		$init = false;
		if ($tables === false) {
			if (in_array($this->getTableName(), $this->_config['entitySingular']['ignore'])) $init = true;

			$this->_config['entitySingular']['ignore'] = [];
			$tables = [];
		} elseif (is_string($tables)) {
			$tables = explode('|', $tables);
		}

		if (in_array($this->getTableName(), $tables)) $init = true;

		$this->_config['entitySingular']['ignore'] = array_unique(array_merge($this->_config['entitySingular']['ignore'], $tables));

		if ($init) $this->init();
		return $this;
	}

	/**
	 * Test of haystack starts with needle
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	private function startsWith($haystack, $needle) {
		return $needle === "" || strpos($haystack, $needle) === 0;
	}

	/**
	 * Test of haystack end with needle
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	private function endsWith($haystack, $needle) {
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}

	/**
	 * Check the word for entitySingular matches and returns a singular string
	 * If EntitySingular disabled it simply returns the plural string
	 *
	 * @param string $word
	 * @return string
	 */
	private function processEntitySingular($word) {
		if ($this->entitySingular()) {
			if (! in_array($this->tableName, $this->getEntitySingularIgnores())) {
				$singular = [
					'/(quiz)zes$/i' => '\1',
					'/(matr)ices$/i' => '\1ix',
					'/(vert|ind)ices$/i' => '\1ex',
					'/^(ox)en/i' => '\1',
					'/(alias|status)es$/i' => '\1',
					'/([octop|vir])i$/i' => '\1us',
					'/(cris|ax|test)es$/i' => '\1is',
					'/(shoe)s$/i' => '\1',
					'/(o)es$/i' => '\1',
					'/(bus)es$/i' => '\1',
					'/([m|l])ice$/i' => '\1ouse',
					'/(x|ch|ss|sh)es$/i' => '\1',
					'/(m)ovies$/i' => '\1ovie',
					'/(s)eries$/i' => '\1eries',
					'/([^aeiouy]|qu)ies$/i' => '\1y',
					'/([lr])ves$/i' => '\1f',
					'/(tive)s$/i' => '\1',
					'/(hive)s$/i' => '\1',
					'/([^f])ves$/i' => '\1fe',
					'/(^analy)ses$/i' => '\1sis',
					'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
					'/([ti])a$/i' => '\1um',
					'/(n)ews$/i' => '\1ews',
					'/s$/i' => ''
				];

				$uncountable = explode(' ', 'catches advice art coal baggage butter clothing cotton currency equipment experience fish flour food furniture gas homework impatience information jeans knowledge leather love luggage money oil patience police polish progress research rice series sheep silk soap species sugar talent toothpaste travel vinegar weather wood wool work');

				$irregular = [
					'octopus' => 'octopuses',
					'virus' => 'viruses',
					'person' => 'people',
					'man' => 'men',
					'child' => 'children',
					'sex' => 'sexes',
					'move' => 'moves',
					'zombie' => 'zombies'
				];

				$lowercased_word = strtolower($word);
				foreach ($uncountable as $_uncountable) {
					if (substr($lowercased_word, (-1 * strlen($_uncountable))) == $_uncountable) {
						return $word;
					}
				}

				$arr = [];
				foreach ($irregular as $_plural => $_singular) {
					if (preg_match('/(' . $_singular . ')$/i', $word, $arr)) {
						return preg_replace('/(' . $_singular . ')$/i', substr($arr[0], 0, 1) . substr($_plural, 1), $word);
					}
				}

				foreach ($singular as $rule => $replacement) {
					if (preg_match($rule, $word)) {
						return preg_replace($rule, $replacement, $word);
					}
				}
			}
		}
		return $word;
	}

	/**
	 * Start processing table
	 *
	 * @return \Cathedral\Builder\NameManager
	 */
	protected function init() {
		if (isset($this->tableName) && (isset($this->namespace))) {
            $this->processClassNames();
            try {
                $this->processProperties();
            } catch (Throwable $th) {
                throw $th;
            }
		}
		return $this;
	}

	/**
	 * Generate the related class names
	 *
	 * @return \Cathedral\Builder\NameManager
	 */
	protected function processClassNames() {
		$modelBaseName = ucwords($this->tableName);

		// ucwords
		$this->modelName = "{$modelBaseName}Table";
		$this->entityName = $this->processEntitySingular($modelBaseName);
		$this->entityAbstractName = "{$this->entityName}Abstract";

		// original case
		$this->entityVariable = $this->processEntitySingular($this->tableName);

		$this->modelPath = $this->modulePath . "/{$this->partNameModel}/{$this->modelName}.php";
		$this->entityPath = $this->modulePath . "/{$this->partNameEntity}/{$this->entityName}.php";
		$this->entityAbstractPath = $this->modulePath . "/{$this->partNameEntity}/{$this->entityAbstractName}.php";

		return $this;
	}

	/**
	 * Generate properties
	 *
	 * @throws \Exception
	 *
	 * @return \Cathedral\Builder\NameManager
	 */
	protected function processProperties() {
		try {
			$table = $this->metadata->getTable($this->tableName);
		} catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $this->tableName, DatabaseException::ERROR_DB_TABLE);
		}

		$columns = $table->getColumns();
		$constraints = $table->getConstraints();
		$this->primaryIsSequence = false;

		// PRIMARY
		foreach ($constraints as $constraint) {
			if ($constraint->isPrimaryKey()) {
				$primaryColumns = $constraint->getColumns();
				$this->primary = $primaryColumns[0];

				$sql = "SHOW COLUMNS FROM {$this->tableName} WHERE Extra = 'auto_increment' AND Field = '{$this->primary}'";
				$stmt = \Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
				$result = $stmt->execute();
				if ($result->count()) $this->primaryIsSequence = true;
			}
		}

		// PROPERTIES
		$this->properties = [];
		foreach ($columns as $column) {
			$isPrimary = false;

			$type = self::TYPE_STRING;
            $dataType = $column->getDataType();
            
			if (strpos($dataType, self::TYPE_INT) !== false) $type = self::TYPE_INT;
			elseif (strpos($dataType, 'bit') !== false) $type = self::TYPE_INT;
			elseif (strpos($dataType, self::TYPE_FLOAT) !== false) $type = self::TYPE_FLOAT;
			elseif (strpos($dataType, self::TYPE_DOUBLE) !== false) $type = self::TYPE_DOUBLE;
			elseif (strpos($dataType, 'decimal') !== false) $type = self::TYPE_NUMBER;

			if ($column->getName() == $this->primary) {
				$isPrimary = true;
				$this->primaryType = $type;
			}

			$default = $column->getColumnDefault();
			if ($default == "CURRENT_TIMESTAMP") $default = null;
			else if ($type == self::TYPE_INT) $default = $default === null ? null : (int)$default;
			elseif (strpos($dataType, 'bit') !== false) {
				$default = (string)$default;
				$default = (boolean)(int)$default[2];
			}

			$this->properties[$column->getName()] = [
				'type' => $type,
				'default' => $default,
				'primary' => $isPrimary
			];
		}
		$this->propertiesCSV = "'" . implode("','", array_keys($this->properties)) . "'";

		// Child tables
		$this->relationChildren = [];
		$sql = "SELECT DISTINCT TABLE_NAME AS tablename FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = 'fk_{$this->tableName}' AND TABLE_SCHEMA=(SELECT DATABASE() AS db FROM DUAL)";
		$stmt = \Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
		$result = $stmt->execute();

		while ($result->next()) $this->relationChildren[] = $result->current()['tablename'];

		return $this;
	}
}