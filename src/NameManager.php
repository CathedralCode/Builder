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
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2019 Philip Michael Raab <peep@inane.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Builder;

use Cathedral\Db\ValueType;
use Exception;
use InvalidArgumentException;
use Laminas\Db\Exception\InvalidArgumentException as DbExceptionInvalidArgumentException;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\TableGateway\Exception\RuntimeException;
use Throwable;

use Cathedral\Builder\Exception\{
    DatabaseException,
    InvalidArgumentException as ExceptionInvalidArgumentException
};
use Laminas\Db\Metadata\{
    Source\Factory as MetadataFactory,
    MetadataInterface
};

/**
 * Cathedral\Builder\NameManager
 *
 * Used to generate any names used by the builders
 *
 * @package Cathedral\Builder
 *
 * @version 0.3.0
 */
class NameManager {

    /**
     * #@+
     * Constant values
     */
    // const TYPE_BOOLEAN = 'boolean';
    // const TYPE_BOOL = 'bool';
    // const TYPE_INTEGER = 'integer';
    // const TYPE_INT = 'int';
    // const TYPE_FLOAT = 'float';
    // const TYPE_DOUBLE = 'double';
    // const TYPE_STRING = 'string';
    // const TYPE_ARRAY = 'array';
    // const TYPE_CONSTANT = 'constant';
    // const TYPE_NULL = 'null';
    // const TYPE_OBJECT = 'object';
    // const TYPE_OTHER = 'other';
    // const TYPE_JSON = 'array';
    /**
     * #@-
     */

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
     * Singular Data
     *
     * @var string[][]
     */
    private static array $singularData = [
        'singular' => [
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
        ],
        'uncountable' => ['advice', 'art', 'baggage', 'butter', 'catches', 'clothing', 'coal', 'cotton', 'currency', 'equipment', 'experience', 'fish', 'flour', 'food', 'furniture', 'gas', 'homework', 'impatience', 'information', 'jeans', 'knowledge', 'leather', 'love', 'luggage', 'money', 'oil', 'patience', 'police', 'polish', 'progress', 'research', 'rice', 'series', 'sheep', 'silk', 'soap', 'species', 'sugar', 'talent', 'toothpaste', 'travel', 'vinegar', 'weather', 'wood', 'wool', 'work'],
        'irregular' => [
            'child' => 'children',
            'man' => 'men',
            'move' => 'moves',
            'octopus' => 'octopuses',
            'person' => 'people',
            'sex' => 'sexes',
            // 'stadium' => 'stadiums',
            'virus' => 'viruses',
            'zombie' => 'zombies',
        ],
    ];

    /**
     * Table Metadata
     *
     * @var \Laminas\Db\Metadata\MetadataInterface MetadataInterface
     */
    protected $metadata;

    /**
     * Table Names
     *
     * @var string[]
     */
    protected $tableNames;

    /**
     *
     * @var mixed
     */
    protected $tableNamesIndex;

    /**
     * @var string the table name
     */
    public string $tableName;
    /**
     * @var TableIdentifier the table Identifier
     */
    public $tableIdentifier;

    /**
     * @var string the model class
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
    public $namespace;
    /**
     * Namespace: Model
     *
     * @var string
     */
    public $namespace_model;
    /**
     * Namespace: Entity
     *
     * @var string
     */
    public $namespace_entity;

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

        return $this->_config['entitySingular']['enabled'];
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
     * @return \Cathedral\Builder\NameManager
     */
    public function setEntitySingularIgnores(array $tables): NameManager {
        $init = false;
        if (in_array($this->getTableName(), $tables)) $init = true;

        $this->_config['entitySingular']['ignore'] = array_unique(array_merge($this->_config['entitySingular']['ignore'], $tables));

        if ($init) $this->init();
        return $this;
    }

    /**
     * Check the word for entitySingular matches and returns a singular string
     * If EntitySingular disabled it simply returns the plural string
     *
     * @param string $word
     * @return string
     */
    private function processEntitySingular(string $word): string {
        if ($this->entitySingular() && !in_array($this->tableName, $this->getEntitySingularIgnores())) {
            $lowercase_word = strtolower($word);
            foreach ($this::$singularData['uncountable'] as $_uncountable) if (substr($lowercase_word, (-1 * strlen($_uncountable))) == $_uncountable) return $word;

            $arr = [];
            foreach ($this::$singularData['irregular'] as $_plural => $_singular) if (preg_match('/(' . $_singular . ')$/i', $word, $arr)) return preg_replace('/(' . $_singular . ')$/i', substr($arr[0], 0, 1) . substr($_plural, 1), $word);
            foreach ($this::$singularData['singular'] as $rule => $replacement) if (preg_match($rule, $word)) return preg_replace($rule, $replacement, $word);
        }
        return $word;
    }

    /**
     * Start processing table
     *
     * @return \Cathedral\Builder\NameManager
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
     * @return \Cathedral\Builder\NameManager
     */
    protected function processClassNames(): NameManager {
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
     * @since 0.3.0 Uses \Cathedral\Db\ValueType
     *
     * @return \Cathedral\Builder\NameManager
     */
    protected function processProperties(): NameManager {
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

                $sql = "SHOW COLUMNS FROM `{$this->tableName}` WHERE Extra = 'auto_increment' AND Field = '{$this->primary}'";
                $stmt = \Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
                $result = $stmt->execute();
                if ($result->count()) $this->primaryIsSequence = true;
            }
        }

        // PROPERTIES
        $this->properties = [];
        foreach ($columns as $column) {
            $info = [
                'vt' => ValueType::STRING,
                'dataType' => $column->getDataType(),
                'primary' => false,
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

            $default = $column->getColumnDefault();
            $info['default'] = $column->getColumnDefault();

            if ($info['default'] == "CURRENT_TIMESTAMP") $info['default'] = null;
            else if ($info['vt'] == ValueType::INT) $info['default'] = $info['default'] === null ? null : (int)$info['default'];
            else if ($info['vt'] == ValueType::FLOAT) $info['default'] = $info['default'] === null ? null : (float)$info['default'];
            elseif (strpos($dataType, ValueType::BIT->value) !== false) {
                $tmp = (string)$info['default'];
                $info['default'] = (bool)(int)$tmp[2];
            }

            $this->properties[$column->getName()] = $info;
        }

        return $this;
    }
}
