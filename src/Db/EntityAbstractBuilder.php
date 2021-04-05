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
 * @copyright 2013-2021 Philip Michael Raab <peep@inane.co.za>
 */

namespace Cathedral\Builder\Db;

use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Db\Sql\TableIdentifier;

use function array_keys;
use function implode;
use function str_replace;
use function strpos;
use function ucwords;

/**
 * Builds the Abstract Entity
 *
 * @package Cathedral\Builder\Builders
 * @version 0.4.1
 */
class EntityAbstractBuilder extends BuilderAbstract {

    /**
     * string
     */
    protected $type = self::TYPE_ENTITYABSTRACT;

    /**
     * Generate the php file code
     *
     * @see \Cathedral\Builder\BuilderAbstract::setupFile()
     */
    protected function setupFile() {
        $this->_file->setNamespace($this->getNames()->namespace_entity);

        $this->_file->setUse('Laminas\Db\RowGateway\RowGatewayInterface')->setUse('Laminas\Db\RowGateway\AbstractRowGateway')->setUse('Laminas\Db\Sql\TableIdentifier')->setUse('Laminas\Json\Json')->setUse("{$this->getNames()->namespace_model}\\{$this->getNames()->modelName}")->setUse('Exception')->setUse('function in_array')->setUse('function array_keys');
    }

    /**
     * Convert a column name to a user friendly method name.
     * By default it returns a get method.
     *
     * @param string $property
     * @param string $prepend
     * @return string
     */
    private function parseMethodName(string $property, string $prepend = 'get'): string {
        return $prepend . str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
    }

    /**
     * Create getter & setter methods for properties
     *
     * @param string $property
     */
    protected function addGetterSetter(string $property) {
        $properyName = $this->parseMethodName($property, '');
        $getter = "get{$properyName}";
        $setter = "set{$properyName}";

        // Extract array to $type, $default, $primary
        [
            'type' => $type,
            'default' => $default,
            'primary' => $primary
        ] = $this->getNames()->properties[$property];

        // Type Cast
        // $cast = $type == 'int' ? '(int)' : '';
        // $cast2 = $cast == '(int)' ? '?:null' : '';

        // METHODS
        // METHOD:getPropperty
        $method = $this->buildMethod($getter);
        if ($default === null) $method->setReturnType('?' . $type);
        else $method->setReturnType($type);

        if ($type == 'array') {
            $body = <<<MBODY
\$json = \$this->data['{$property}'];
if (is_string(\$json)) \$json = Json::decode(\$json, Json::TYPE_ARRAY);
return \$json;
MBODY;
        } else {
            $body = <<<MBODY
return \$this->data['{$property}'];
MBODY;
        }

        $method->setBody($body);
        $method->setDocBlock(DocBlockGenerator::fromArray([
            'shortDescription' => "Get the {$property} property",
            'tags' => [
                new ReturnTag([
                    'datatype' => $type
                ])
            ]
        ]));
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:setPropperty
        $parameterSetter = new ParameterGenerator();
        $parameterSetter->setName($property);
        if ($default === null) $parameterSetter->setType('?' . $type);
        else {
            $parameterSetter->setType($type);
            $parameterSetter->setDefaultValue($default);
        }
        $method = $this->buildMethod($setter);
        $method->setParameter($parameterSetter);
        $method->setReturnType($this->getNames()->namespace_entity . '\\' . $this->getNames()->entityName);
        $body = <<<MBODY
\$this->data['{$property}'] = \${$property};
return \$this;
MBODY;

        if ($type == 'array') $body = <<<MBODY
if (!is_string(\${$property})) \${$property} = Json::encode(\${$property});
{$body}
MBODY;

        $method->setBody($body);
        $method->setDocBlock(DocBlockGenerator::fromArray([
            'shortDescription' => "Set the {$property} property",
            'tags' => [
                new ParamTag($property, [
                    'datatype' => $type
                ]),
                new ReturnTag([
                    'datatype' => $this->getNames()->entityName
                ])
            ]
        ]));
        $this->_class->addMethodFromGenerator($method);
    }

    /**
     * Create method to return related Parent entity
     * linked to foreign key stored in this coloumn
     *
     * @param string $columnName
     * @return void
     * @throws InvalidArgumentException
     */
    protected function addRelationParent(string $columnName): void {
        $table = substr($columnName, 3);
        $parent = new NameManager($this->getNames()->namespace, $table);
        // METHOD:getRelationParent
        $method = $this->buildMethod($parent->entityName);
        $method->setReturnType($parent->namespace_entity . '\\' . $parent->entityName);
        $body = <<<MBODY
\${$parent->tableName} = new \\{$parent->namespace_model}\\{$parent->modelName}();
return \${$parent->tableName}->get{$parent->entityName}(\$this->data['{$columnName}']);
MBODY;
        $method->setBody($body);
        $tag = new ReturnTag();
        $tag->setTypes("\\{$parent->namespace_entity}\\{$parent->entityName}");
        $docBlock = new DocBlockGenerator();
        $docBlock->setTag($tag);
        $docBlock->setShortDescription("Related {$parent->entityName}");
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);
    }

    /**
     * Create method to return related children entities
     * this primary key found in table
     *
     * @param string $tableName
     * @return void
     * @throws InvalidArgumentException
     */
    protected function addRelationChild(string $tableName): void {
        $parameter = new ParameterGenerator();
        $parameter->setName('whereArray');
        $parameter->setDefaultValue([]);
        $parameter->setType('array');

        $child = new NameManager($this->getNames()->namespace, $tableName);

        // METHOD:getRelationChild
        $functionName = ucwords($tableName);
        $method = $this->buildMethod($functionName);
        $method->setParameter($parameter);
        $body = <<<MBODY
\$where = array_merge(['fk_{$this->getNames()->tableName}' => \$this->data['{$this->getNames()->primary}']], \$whereArray);
\${$child->tableName} = new \\{$child->namespace_model}\\{$child->modelName}();
return \${$child->tableName}->select(\$where);
MBODY;
        $method->setBody($body);
        $tag = new ReturnTag();
        $tag->setTypes("\\Laminas\\Db\\ResultSet\\HydratingResultSet");
        $docBlock = new DocBlockGenerator();
        $docBlock->setTag(new ParamTag('whereArray', [
            'datatype' => []
        ]));
        $docBlock->setTag($tag);
        $docBlock->setShortDescription("Related {$child->entityName}");
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);
    }

    /**
     * Generate the class code
     *
     * @see \Cathedral\Builder\BuilderAbstract::setupClass()
     */
    protected function setupClass() {
        $this->_class->setName($this->getNames()->entityAbstractName);
        $this->_class->setExtendedClass('AbstractRowGateway');
        $this->_class->setImplementedInterfaces([
            'RowGatewayInterface'
        ]);
        $this->_class->setAbstract(true);

        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Entity for {$this->getNames()->tableName}");
        $tags = [];

        $tags[] = [
            'name' => 'namespace',
            'description' => $this->getNames()->namespace_entity
        ];

        // PROPERTIES DATA ARRAY & CLASS PROPERTY TAGS ARRAY
        $dataProperty = [];
        $tags = [];

        foreach ($this->getNames()->properties as $name => $def) {
            $tags[] = [
                'name' => 'property',
                'description' => "{$def['type']} \${$name}"
            ];

            $dataProperty[$name] = $def['default'];
        }

        // Add tags to class docblock
        $docBlock->setTags($tags);
        $this->_class->setDocBlock($docBlock);

        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => 'DataTable Link',
            'tags' => [
                [
                    'name' => 'var',
                    'description' => "\\{$this->getNames()->namespace_model}\\{$this->getNames()->modelName}"
                ]
            ]
        ]);

        $property = new PropertyGenerator('data');
        $property->setVisibility('protected');
        $property->setDefaultValue($dataProperty);
        $property->setDocBlock(DocBlockGenerator::fromArray([
            'shortDescription' => 'entry data',
            'tags' => [
                [
                    'name' => 'var',
                    'description' => 'array'
                ]
            ]
        ]));
        $this->_class->addPropertyFromGenerator($property);

        $property = new PropertyGenerator('primaryKeyColumn');
        $property->setVisibility('protected');
        $property->setDefaultValue([$this->getNames()->primary]);
        $property->setDocBlock(DocBlockGenerator::fromArray([
            'shortDescription' => 'primary key column',
            'tags' => [
                [
                    'name' => 'var',
                    'description' => 'string[]'
                ]
            ]
        ]));
        $this->_class->addPropertyFromGenerator($property);

        $property = new PropertyGenerator('table');
        $property->setVisibility('protected');
        $property->setDefaultValue($this->getNames()->tableName);
        $property->setDocBlock(DocBlockGenerator::fromArray([
            'shortDescription' => 'the table name',
            'tags' => [
                [
                    'name' => 'var',
                    'description' => 'string|TableIdentifier'
                ]
            ]
        ]));
        $this->_class->addPropertyFromGenerator($property);

        $property = new PropertyGenerator();
        $property->setName('dataTable');
        $property->setDocBlock($docBlock);
        $property->setVisibility('private');
        $this->_class->addPropertyFromGenerator($property);

        $this->_file->setClass($this->_class);
    }

    /**
     * Generate the method code
     *
     * @see \Cathedral\Builder\BuilderAbstract::setupMethods()
     */
    protected function setupMethods() {
        // PARAMETERS
        $parameterPrimary = new ParameterGenerator();
        $parameterPrimary->setName($this->getNames()->primary);
        $parameterPrimary->setType($this->getNames()->primaryType);

        $parameterPropertyPlain = new ParameterGenerator();
        $parameterPropertyPlain->setName('property');

        $parameterProperty = new ParameterGenerator();
        $parameterProperty->setName('property');
        $parameterProperty->setType('string');

        $parameterDataTable = new ParameterGenerator();
        $parameterDataTable->setName('dataTable');
        // $parameterDataTable->setType('?'.$this->getNames()->modelName);
        $parameterDataTable->setDefaultValue(null);
        // --
        $paramTagProperty = new ParamTag();
        $paramTagProperty->setTypes([
            'string'
        ]);
        $paramTagProperty->setVariableName('property');

        $parameterValue = new ParameterGenerator();
        $parameterValue->setName('value');

        $paramTagValue = new ParamTag();
        $paramTagValue->setTypes([
            'mixed'
        ]);
        $paramTagValue->setVariableName('value');

        $parameterPrepend = new ParameterGenerator();
        $parameterPrepend->setName('prepend');
        $parameterPrepend->setType('string');
        $parameterPrepend->setDefaultValue('get');

        $parameterUseDefaults = new ParameterGenerator();
        $parameterUseDefaults->setName('useDefaults');
        $parameterUseDefaults->setType('bool');
        $parameterUseDefaults->setDefaultValue(true);
        // --
        $paramTagPrepend = new ParamTag();
        $paramTagPrepend->setTypes([
            'string'
        ]);
        $paramTagPrepend->setVariableName('prepend');

        $parameterDataArray = new ParameterGenerator();
        $parameterDataArray->setName($this->getNames()->entityVariable);
        $parameterDataArray->setType('array');

        $returnTagString = new ReturnTag();
        $returnTagString->setTypes([
            'string'
        ]);

        $returnTagMixed = new ReturnTag();
        $returnTagMixed->setTypes([
            'mixed'
        ]);

        $returnTagArray = new ReturnTag();
        $returnTagArray->setTypes([
            'array'
        ]);

        $returnTagEntity = new ReturnTag([
            'datatype' => $this->getNames()->entityName
        ]);

        $returnEntity = $this->getNames()->namespace_entity . '\\' . $this->getNames()->entityName;
        $returnModel = $this->getNames()->namespace_model . '\\' . $this->getNames()->modelName;

        // ===============================================

        // METHODS
        // METHOD:parseMethodName
        $method = $this->buildMethod('parseMethodName');
        $method->setVisibility('private');
        $method->setParameter($parameterProperty);
        $method->setParameter($parameterPrepend);
        $method->setReturnType('string');
        $body = <<<MBODY
return \$prepend.str_replace(' ','',ucwords(str_replace('_',' ',\$property)));
MBODY;
        $docBlock = new DocBlockGenerator();
        $docBlock->setTag($paramTagProperty);
        $docBlock->setTag($paramTagPrepend);
        $docBlock->setTag($returnTagString);
        $docBlock->setShortDescription("Convert a column name to a user friendly method name.");
        $method->setDocBlock($docBlock);
        $method->setBody($body);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:__construct
        // {$this->getNames()->entityName}
        $method = $this->buildMethod('__construct');
        $method->setParameter($parameterDataTable);
        $body = <<<MBODY
if (\$dataTable) \$this->dataTable = \$dataTable;

// set table
\$this->sql = \$this->getDataTable()->getSql();

\$this->initialize();
MBODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Creates new {$this->getNames()->entityName} instance");
        $docBlock->setTag(new ParamTag('dataTable', [
            'datatype' => $this->getNames()->modelName
        ]));
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:__sleep
        $method = $this->buildMethod('__sleep');
        $method->setReturnType('array');
        $body = <<<MBODY
return ['data'];
MBODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription('magic method: __sleep');
        $docBlock->setTag($returnTagArray);
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:_wakeup
        $method = $this->buildMethod('_wakeup');
        // $method->setParameter(new ParameterGenerator('data', 'array'));
        $body = <<<MBODY
MBODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription('magic method: _wakeup');
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:__get
        $method = $this->buildMethod('__get');
        $method->setParameter($parameterPropertyPlain);
        // $method->setReturnType('mixed');
        $body = <<<MBODY
if (!in_array(\$property, array_keys(\$this->data))) throw new Exception("Invalid Property:\\n\\t{$this->getNames()->entityName} has no property: {\$property}");
\$method = \$this->parseMethodName(\$property);
return \$this->\$method();
MBODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription('magic method: __get');
        $docBlock->setTag($paramTagProperty);
        $docBlock->setTag($returnTagMixed);
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:__set
        $method = $this->buildMethod('__set');
        $method->setParameter($parameterPropertyPlain);
        $method->setParameter($parameterValue);
        // $method->setReturnType($returnEntity);
        $body = <<<MBODY
if (!in_array(\$property, array_keys(\$this->data))) throw new Exception("Invalid Property:\\n\\t{$this->getNames()->entityName} has no property: {\$property}");
\$method = \$this->parseMethodName(\$property, 'set');
\$this->\$method(\$value);
MBODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription('magic method: __set');
        $docBlock->setTag($paramTagProperty);
        $docBlock->setTag($paramTagValue);
        $docBlock->setTag($returnTagEntity);
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:getDataTable
        $method = $this->buildMethod('getDataTable');
        $method->setReturnType($returnModel);
        $body = <<<MBODY
if (!\$this->dataTable) \$this->dataTable = new {$this->getNames()->modelName}();

return \$this->dataTable;
MBODY;
        $method->setBody($body);
        $tag = new ReturnTag();
        $tag->setTypes([
            "\\{$this->getNames()->namespace_model}\\{$this->getNames()->modelName}"
        ]);
        $docBlock = new DocBlockGenerator();
        $docBlock->setTag($tag);
        $docBlock->setShortDescription("DataTable for {$this->getNames()->entityName}");
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:Getter/Setter
        $relationColumns = [];
        foreach (array_keys($this->getNames()->properties) as $name) {
            if (0 === strpos($name, 'fk_')) $relationColumns[] = $name;
            $this->addGetterSetter($name);
        }

        foreach ($relationColumns as $columnName) $this->addRelationParent($columnName);

        // ===============================================

        // METHOD:RelationChildren
        foreach ($this->getNames()->relationChildren as $tableName) {
            $this->addRelationChild($tableName);
        }

        // ===============================================

        // METHOD:get
        $method = $this->buildMethod('get');
        $method->setParameter($parameterPrimary);
        $method->setReturnType('?' . $returnEntity);

        $body = <<<MBODY
\$this->data['{$this->getNames()->primary}'] = \${$this->getNames()->primary};
\${$this->getNames()->entityVariable} = \$this->getDataTable()->get{$this->getNames()->entityName}(\${$this->getNames()->primary});

if(!\${$this->getNames()->entityVariable}) return null;

\$this->exchangeArray(\${$this->getNames()->entityVariable}->getArrayCopy());
return \$this;
MBODY;
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Get {$this->getNames()->entityName} by primary key value");
        $docBlock->setTag(new ParamTag($this->getNames()->primary, [
            'datatype' => $this->getNames()->properties[$this->getNames()->primary]['type']
        ]));
        $docBlock->setTag($returnTagEntity);
        $method->setDocBlock($docBlock);

        $method->setBody($body);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:save
        $method = $this->buildMethod('save');
        $method->setReturnType($returnEntity);
        $body = <<<MBODY
\$this->getDataTable()->save{$this->getNames()->entityName}(\$this);
return \$this;
MBODY;
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Save the entity to database");
        $docBlock->setTag($returnTagEntity);
        $method->setDocBlock($docBlock);

        $method->setBody($body);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:delete
        $method = $this->buildMethod('delete');
        $body = <<<MBODY
\$this->getDataTable()->delete{$this->getNames()->entityName}(\$this->data['{$this->getNames()->primary}']);
MBODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Deletes the entity from table");
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:getArrayCopy
        $method = $this->buildMethod('getArrayCopy');
        $objectParam = new ParameterGenerator('object', '?object');
        $objectParam->setDefaultValue(null);
        $method->setParameter($objectParam);
        $method->setParameter(new ParameterGenerator('ignorePrimaryColumn', 'bool', false));
        $method->setReturnType('array');
        $mjson = [];
        foreach($this->getNames()->properties as $name => $prop) if ($prop['type'] == 'array') $mjson[] = "\$data['{$name}'] = Json::encode(\$this->{$name});";
        $mjson = implode("\n", $mjson);

        $body = <<<MBODY
\$data = array_merge([], \$this->data);
{$mjson}
if (\$ignorePrimaryColumn) foreach (\$this->primaryKeyColumn as \$column) unset(\$data[\$column]);
return \$data;
MBODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setTag(new ParamTag('object', [
            'datatype' => '?object'
        ]));
        $docBlock->setTag(new ParamTag('ignorePrimaryColumn', [
            'datatype' => 'bool'
        ]));
        $docBlock->setTag(new ReturnTag([
            'datatype' => 'Array'
        ]));
        $docBlock->setShortDescription("Array copy of object");
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);
    }
}
