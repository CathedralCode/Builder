<?php

/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP version 8
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @version $Id: 0.32.2-9-g96a14cc$
 * $Date: Tue Jul 26 22:45:10 2022 +0200$
 */

declare(strict_types=1);

namespace Cathedral\Builder\Parser;

use Cathedral\Db\ValueType;
use Exception;
use InvalidArgumentException;
use Laminas\Db\Exception\InvalidArgumentException as DbExceptionInvalidArgumentException;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Exception\RuntimeException;
use Throwable;

use function array_merge;
use function array_unique;
use function file_exists;
use function floatval;
use function getcwd;
use function in_array;
use function intval;
use function strpos;

use Cathedral\Builder\Exception\{
	DatabaseException,
	InvalidArgumentException as ExceptionInvalidArgumentException
};
use Laminas\Db\Metadata\{
	Source\Factory as MetadataFactory,
	MetadataInterface
};

/**
 * Cathedral\Builder\Parser\NameManager
 *
 * Used to generate any names used by the builders
 *
 * @package Cathedral\Builder
 *
 * @version 0.4.1
 */
class NameManager {
	/**
	 * Configuration
	 *
	 * @var array[][]
	 */
	private array $_config = [
		'entitySingular' => [
			'enabled' => true,
			'ignore' => []
		]
	];

	/**
	 * Table Metadata
	 *
	 * @var \Laminas\Db\Metadata\MetadataInterface MetadataInterface
	 */
	protected MetadataInterface $metadata;

	/**
	 * Table Names
	 *
	 * @var string[]
	 */
	protected array $tableNames;

	/**
	 *
	 * @var mixed
	 */
	protected int $tableNamesIndex;

	/**
	 * @var string the table name
	 */
	public string $tableName;
	/**
	 * @var TableIdentifier the table Identifier
	 */
	public TableIdentifier $tableIdentifier;

	/**
	 * @var string the table class
	 */
	public string $modelName;
	/**
	 *
	 * @var string the entity class
	 */
	public string $entityName;
	/**
	 * @var string the abstract entity class
	 */
	public string $entityAbstractName;

	/**
	 * @var string the module path
	 */
	public string $modulePath;
	/**
	 * @var string the model path
	 */
	public string $modelPath;
	/**
	 * @var string the entity path
	 */
	public string $entityPath;
	/**
	 * @var string the abstract entity path
	 */
	public string $entityAbstractPath;

	/**
	 *
	 * @var string
	 */
	public string $entityVariable;

	/**
	 * Primary key column
	 *
	 * @var string the primary key column
	 */
	public string $primary;
	/**
	 * Primary key type
	 *
	 * @var string
	 */
	public string $primaryType;
	/**
	 * Primary Key is sequential
	 * 
	 * @var bool
	 */
	public bool $primaryIsSequence;
	/**
	 * Table columns
	 *
	 * @var array
	 */
	public array $properties = [];
	// public $propertiesCSV;

	private string $partNameModel = 'Model';
	private string $partNameEntity = 'Entity';

	/**
	 * Namespace
	 *
	 * @var string
	 */
	public string $namespace;
	/**
	 * Namespace: Model
	 *
	 * @var string
	 */
	public string $namespace_model;
	/**
	 * Namespace: Entity
	 *
	 * @var string
	 */
	public string $namespace_entity;

	/**
	 * Create NameManager instance
	 *
	 * @param string $namespace
	 * @param null|string $tableName
	 *
	 * @return void
	 *
	 * @throws RuntimeException
	 * @throws DbExceptionInvalidArgumentException
	 * @throws ExceptionInvalidArgumentException
	 * @throws Throwable
	 */
	public function __construct(string $namespace = 'Application', ?string $tableName = null) {
		$this->metadata = MetadataFactory::createSourceFromAdapter(\Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter());
		$this->tableNames = $this->metadata->getTableNames();

		if (!isset($tableName)) $tableName = $this->tableNames[0];

		try {
			$this->setNamespace($namespace);
		} catch (InvalidArgumentException $e) {
			throw new ExceptionInvalidArgumentException('"namespace" should be a valid Module');
		}

		$this->setTableName($tableName);
	}

	/**
	 * Table to process
	 *
	 * @param null|string $tableName
	 * @return NameManager
	 */
	public function setTableName(?string $tableName): NameManager {
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
	 *
	 * @return NameManager
	 *
	 * @throws ExceptionInvalidArgumentException
	 */
	public function setNamespace(string $namespace): NameManager {
		$pathBase = getcwd() . "/module/{$namespace}/src";

		if (!file_exists($pathBase)) throw new ExceptionInvalidArgumentException('"namespace" should be a valid Module');

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
	 * @return bool
	 */
	public function nextTable(): bool {
		if (!isset($this->tableNamesIndex)) $this->tableNamesIndex = 0;
		if (++$this->tableNamesIndex == sizeof($this->tableNames)) return false;

		$this->setTableName($this->tableNames[$this->tableNamesIndex]);
		return true;
	}

	/**
	 * Enable/Disable the EntitySingular option
	 * Leave empty to just get current status
	 *
	 * @param null|bool $enabled
	 * @return bool
	 */
	public function entitySingular(?bool $enabled = null): bool {
		if ($enabled !== null) $this->_config['entitySingular']['enabled'] = $enabled;

		return (bool)$this->_config['entitySingular']['enabled'];
	}

	/**
	 * Return array of tables name to skip for entitySingular
	 *
	 * @return array
	 */
	public function getEntitySingularIgnores(): array {
		return $this->_config['entitySingular']['ignore'];
	}

	/**
	 * Array of tables to ignore
	 * e.g.
	 * ['users', 'towns']
	 *
	 * @param array $table
	 *
	 * @return \Cathedral\Builder\Parser\NameManager
	 */
	public function setEntitySingularIgnores(array $tables): NameManager {
		$init = false;
		if (in_array($this->getTableName(), $tables)) $init = true;

		$this->_config['entitySingular']['ignore'] = array_unique(array_merge($this->_config['entitySingular']['ignore'], $tables));

		if ($init) $this->init();
		return $this;
	}

	/** processEntitySingular
	 * Check the word for entitySingular matches and returns a singular string
	 * If EntitySingular disabled it simply returns the plural string
	 *
	 * @param string $word
	 * @return string
	 */
	private function processEntitySingular(string $word): string {
		if ($this->entitySingular() && !in_array($this->tableName, $this->getEntitySingularIgnores())) return \Inane\Stdlib\String\Inflector::singularise($word);

		return $word;
	}

	/**
	 * Start processing table
	 *
	 * @return \Cathedral\Builder\Parser\NameManager
	 */
	protected function init(): NameManager {
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
	 * @return \Cathedral\Builder\Parser\NameManager
	 */
	protected function processClassNames(): NameManager {
		// $modelBaseName = ucwords($this->tableName);
		$baseName = \Inane\Stdlib\String\Inflector::camelise($this->tableName, true);

		// ucwords
		$this->modelName = "{$baseName}Table";
		$this->entityName = $this->processEntitySingular($baseName);
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
	 * @since 0.3.0 Uses \Cathedral\Db\ValueType
	 *
	 * @return \Cathedral\Builder\Parser\NameManager
	 */
	protected function processProperties(): NameManager {
		try {
			$table = $this->metadata->getTable($this->tableName);
		} catch (Exception $e) {
			throw new DatabaseException($this->tableName . ':' . $e->getMessage());
		}

		$columns = $table->getColumns();
		$constraints = $table->getConstraints();
		$this->primaryIsSequence = false;

		// PRIMARY
		foreach ($constraints as $constraint) {
			if ($constraint->isPrimaryKey()) {
				$primaryColumns = $constraint->getColumns();
				$this->primary = $primaryColumns[0];

				$sql = "SHOW COLUMNS FROM `{$this->tableName}` WHERE Extra = 'auto_increment' AND Field = '{$this->primary}'";
				$stmt = \Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
				$result = $stmt->execute();
				if ($result->count()) $this->primaryIsSequence = true;
			}
		}

		// PROPERTIES
		$this->properties = [];
		foreach ($columns as $column) {
			/**
			 * @var \Laminas\Db\Metadata\Object\ColumnObject $column
			 */
			/**
			 * Column Info
			 */
			$info = [
				'vt' => ValueType::STRING,
				'dataType' => $column->getDataType(),
				'primary' => false,
				'nullable' => $column->getIsNullable(),
			];

			$dataType = $column->getDataType();

			if (strpos($dataType, ValueType::INT->value) !== false) $info['vt'] = ValueType::INT;
			elseif (strpos($dataType, ValueType::BIT->value) !== false) $info['vt'] = ValueType::BIT;
			elseif (strpos($dataType, ValueType::FLOAT->value) !== false) $info['vt'] = ValueType::FLOAT;
			elseif (strpos($dataType, ValueType::JSON->value) !== false) $info['vt'] = ValueType::JSON;
			elseif (strpos($dataType, ValueType::DOUBLE->value) !== false) $info['vt'] = ValueType::DOUBLE;
			elseif (strpos($dataType, ValueType::DECIMAL->value) !== false) $info['vt'] = ValueType::DECIMAL;

			$info['type'] = $info['vt']->type();

			if ($column->getName() == $this->primary) {
				$info['primary'] = true;
				$this->primaryType = $info['vt']->type();
			}

			$info['default'] = $column->getColumnDefault();

			if ($info['default'] == "CURRENT_TIMESTAMP") $info['default'] = null;
			else if ($info['vt'] == ValueType::INT) $info['default'] = $info['default'] === null ? null : intval($info['default']);
			else if ($info['vt']->type() == ValueType::FLOAT->value) $info['default'] = $info['default'] === null ? null : floatval($info['default']);
			elseif ($info['vt'] == ValueType::BIT) {
				$tmp = (string)$info['default'];
				$info['default'] = (int)$tmp[2];
			}

			$this->properties[$column->getName()] = $info;
		}

		return $this;
	}
}
