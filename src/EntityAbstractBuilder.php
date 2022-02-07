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

declare(strict_types=1);

namespace Cathedral\Builder;

use Cathedral\Db\ValueType;

use function str_replace;
use function ucwords;

use Laminas\Code\Generator\DocBlock\Tag\{
    ParamTag,
    ReturnTag
};
use Laminas\Code\Generator\{
    Exception\InvalidArgumentException,
    DocBlockGenerator,
    ParameterGenerator,
    PropertyGenerator
};

/**
 * Builds the Abstract Entity
 *
 * @package Cathedral\Builder\Builders
 * @version 0.6.1
 */
class EntityAbstractBuilder extends BuilderAbstract {

    /**
     * string
     */
    protected $type = self::TYPE_ENTITY_ABSTRACT;

    /**
     * Generate the php file code
     *
     * @see \Cathedral\Builder\BuilderAbstract::setupFile()
     */
    protected function setupFile() {
        $this->_file->setNamespace($this->getNames()->namespace_entity);

        $this->_file
            ->setUse('Laminas\Db\RowGateway\RowGatewayInterface')
            ->setUse('Cathedral\Db\Entity\AbstractEntity')
            ->setUse('Laminas\Db\Sql\TableIdentifier')
            ->setUse('Laminas\Json\Json')
            ->setUse("{$this->getNames()->namespace_model}\\{$this->getNames()->modelName}")
            ->setUse('function call_user_func')
            ->setUse('function floatval')
            ->setUse('function intval')
            ->setUse('function is_string')
            ->setUse('function method_exists')
            ->setUse('const null')
            ;

        // NOTE: STRICT_TYPES: see BuilderAbstract->getCode(): add strict_types using replace due to official method placing it bellow namespace declaration.
        // $this->_file->setDeclares([
        //     DeclareStatement::strictTypes(1),
        // ]);
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
        $propertyName = $this->parseMethodName($property, '');
        $getter = "get{$propertyName}";
        $setter = "set{$propertyName}";

        /**
         * @var \Cathedral\Db\ValueType $vt
         */
        // Extract array to $type, $default, $primary
        [
            'type' => $type,
            'default' => $default,
            'primary' => $primary,
            'vt' => $vt,
            'nullable' => $nullable,
        ] = $this->getNames()->properties[$property];

        // METHODS
        // ===============================================
        // METHOD:getProperty
        // ===============================================
        $method = $this->buildMethod($getter);
        if ($nullable) $method->setReturnType('?' . $type);
        else $method->setReturnType($type);

        $bodyNullable = $nullable ? "\$this->data['{$property}'] === null ? null :" : '';

        if ($type == 'array') {
            $body = <<<M_BODY
\$json = \$this->data['{$property}'];
if (is_string(\$json)) \$json = Json::decode(\$json, Json::TYPE_ARRAY);
return \$json;
M_BODY;
        } else if ($type == 'int') {
            $body = <<<M_BODY
return {$bodyNullable} intval(\$this->data['{$property}']);
M_BODY;
        } else if ($type == 'float') {
            $body = <<<M_BODY
return {$bodyNullable} floatval(\$this->data['{$property}']);
M_BODY;
        } else {
            $body = <<<M_BODY
return \$this->data['{$property}'];
M_BODY;
        }

        $method->setBody($body);
        $method->setDocBlock(DocBlockGenerator::fromArray([
            'shortDescription' => "Get the {$property} property",
            'tags' => [
                new ReturnTag([
                    'datatype' => $type . ($nullable ? '|null' : '')
                ])
            ]
        ]));
        $this->_class->addMethodFromGenerator($method);

        // ===============================================
        // METHOD:setProperty
        // ===============================================
        $parameterSetter = new ParameterGenerator();
        $parameterSetter->setName($property);
        if ($vt === ValueType::JSON) $parameterSetter->setType('null|string|' . $type);
        else if ($nullable) $parameterSetter->setType('?' . $type);
        else if (!is_null($default)) {
            $parameterSetter->setType($type);
            $parameterSetter->setDefaultValue($default);
        } else $parameterSetter->setType($type);

        $method = $this->buildMethod($setter);
        $method->setParameter($parameterSetter);
        $method->setReturnType($this->getNames()->namespace_entity . '\\' . $this->getNames()->entityName);
        $body = <<<M_BODY
\$this->data['{$property}'] = \${$property};
return \$this;
M_BODY;

        if ($type == 'array') $body = <<<M_BODY
if (!is_string(\${$property})) \${$property} = Json::encode(\${$property});
{$body}
M_BODY;

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
     * linked to foreign key stored in this column
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function addRelatedParent(): void {
        $sql = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = SCHEMA() AND REFERENCED_TABLE_NAME IS NOT NULL and TABLE_NAME = '{$this->getNames()->tableName}'";
        $stmt = \Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
        $result = $stmt->execute();
        while ($result->next()) {
            $table = $result->current()['REFERENCED_TABLE_NAME'];
            $columnName = $result->current()['COLUMN_NAME'];

            $parent = new NameManager($this->getNames()->namespace, $table);
            // METHOD:getRelationParent
            $method = $this->buildMethod($parent->entityName);
            $method->setReturnType($parent->namespace_entity . '\\' . $parent->entityName);
            $body = <<<M_BODY
\${$parent->tableName} = new \\{$parent->namespace_model}\\{$parent->modelName}();
return \${$parent->tableName}->get{$parent->entityName}(\$this->data['{$columnName}']);
M_BODY;
            $method->setBody($body);
            $tag = new ReturnTag();
            $tag->setTypes("\\{$parent->namespace_entity}\\{$parent->entityName}");
            $docBlock = new DocBlockGenerator();
            $docBlock->setTag($tag);
            $docBlock->setShortDescription("Related {$parent->entityName}");
            $method->setDocBlock($docBlock);
            $this->_class->addMethodFromGenerator($method);
        }
    }

    /**
     * Create method to return related children entities
     * this primary key found in table
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function addRelatedChildren(): void {
        $sql = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = SCHEMA() AND REFERENCED_TABLE_NAME IS NOT NULL and REFERENCED_TABLE_NAME = '{$this->getNames()->tableName}'";
        $stmt = \Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter()->query($sql);
        $result = $stmt->execute();
        while ($result->next()) {
            $tableName = $result->current()['TABLE_NAME'];
            $columnName = $result->current()['COLUMN_NAME'];

            $parameter = new ParameterGenerator();
            $parameter->setName('whereArray');
            $parameter->setDefaultValue([]);
            $parameter->setType('array');

            $child = new NameManager($this->getNames()->namespace, $tableName);

            // METHOD:getRelationChild
            $functionName = ucwords($tableName);
            $method = $this->buildMethod($functionName);
            $method->setParameter($parameter);
            $body = <<<M_BODY
\$where = array_merge(['{$columnName}' => \$this->data['{$this->getNames()->primary}']], \$whereArray);
\${$child->tableName} = new \\{$child->namespace_model}\\{$child->modelName}();
return \${$child->tableName}->select(\$where);
M_BODY;
            $method->setBody($body);
            $tag = new ReturnTag();
            $tag->setTypes("\\Laminas\\Db\\ResultSet\\HydratingResultSet");
            $docBlock = new DocBlockGenerator();
            $docBlock->setTag(new ParamTag('whereArray', [
                'array'
            ]));
            $docBlock->setTag($tag);
            $docBlock->setShortDescription("Related {$child->entityName}");
            $method->setDocBlock($docBlock);
            $this->_class->addMethodFromGenerator($method);
        }
    }

    /**
     * Generate the class code
     *
     * @see \Cathedral\Builder\BuilderAbstract::setupClass()
     */
    protected function setupClass() {
        $this->_class->setName($this->getNames()->entityAbstractName);
        $this->_class->setExtendedClass('AbstractEntity');
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

        $returnTagString = new ReturnTag([
            'datatype' => 'string'
        ]);

        $returnTagMixed = new ReturnTag([
            'datatype' => 'mixed'
        ]);

        $returnTagArray = new ReturnTag([
            'datatype' => 'array'
        ]);

        $returnTagEntity = new ReturnTag([
            'datatype' => $this->getNames()->entityName
        ]);

        $returnTagVoid = new ReturnTag([
            'datatype' => 'void'
        ]);

        $returnEntity = $this->getNames()->namespace_entity . '\\' . $this->getNames()->entityName;
        $returnModel = $this->getNames()->namespace_model . '\\' . $this->getNames()->modelName;

        // ===============================================

        // METHOD:__construct
        // {$this->getNames()->entityName}
        $method = $this->buildMethod('__construct');
        $method->setParameter($parameterDataTable);
        $body = <<<M_BODY
if (\$dataTable) \$this->dataTable = \$dataTable;

// set table
\$this->sql = \$this->getDataTable()->getSql();

\$this->initialize();

// Call method if implemented
if (method_exists(\$this, 'customInitialise')) call_user_func([\$this, 'customInitialise']);
M_BODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Creates new {$this->getNames()->entityName} instance");
        $docBlock->setTag(new ParamTag('dataTable', [
            'datatype' => $this->getNames()->modelName
        ]));
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:getDataTable
        $method = $this->buildMethod('getDataTable');
        $method->setReturnType($returnModel);
        $body = <<<M_BODY
if (!\$this->dataTable) \$this->dataTable = new {$this->getNames()->modelName}();

return \$this->dataTable;
M_BODY;
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
        foreach ($this->getNames()->properties as $name => $value) $this->addGetterSetter($name);

        // METHODS: To get related records
        // ===============================================
        $this->addRelatedParent();
        $this->addRelatedChildren();

        // ===============================================

        // METHOD:get
        $method = $this->buildMethod('get');
        $method->setParameter($parameterPrimary);
        $method->setReturnType('?' . $returnEntity);

        $body = <<<M_BODY
\$this->data['{$this->getNames()->primary}'] = \${$this->getNames()->primary};
\${$this->getNames()->entityVariable} = \$this->getDataTable()->get{$this->getNames()->entityName}(\${$this->getNames()->primary});

if(!\${$this->getNames()->entityVariable}) return null;

\$this->data = \${$this->getNames()->entityVariable}->getArrayCopy();
return \$this;
M_BODY;
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
        $body = <<<M_BODY
\$this->getDataTable()->save{$this->getNames()->entityName}(\$this);
return \$this;
M_BODY;
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Save the entity to database");
        $docBlock->setTag($returnTagEntity);
        $method->setDocBlock($docBlock);

        $method->setBody($body);
        $this->_class->addMethodFromGenerator($method);

        // ===============================================

        // METHOD:delete
        $method = $this->buildMethod('delete');
        $method->setReturnType('void');
        $body = <<<M_BODY
\$this->getDataTable()->delete{$this->getNames()->entityName}(\$this->data['{$this->getNames()->primary}']);
M_BODY;
        $method->setBody($body);
        $docBlock = new DocBlockGenerator();
        $docBlock->setShortDescription("Deletes the entity from table");
        $docBlock->setTag($returnTagVoid);
        $method->setDocBlock($docBlock);
        $this->_class->addMethodFromGenerator($method);
    }
}
