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

use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;

/**
 * Builds the Abstract Entity
 *
 * @package Cathedral\Builder\Builders
 * @namespace \Cathedral\Builder
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
		
		$this->_file->setUse('Laminas\Db\RowGateway\RowGatewayInterface');
		$this->_file->setUse("{$this->getNames()->namespace_model}\\{$this->getNames()->modelName}");
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
		else
			$method->setReturnType($type);
		$body = <<<MBODY
return \$this->{$property};
MBODY;
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
\$this->{$property} = \${$property};
return \$this;
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
	 */
	protected function addRelationParent(string $columnName) {
		$table = substr($columnName, 3);
		$parent = new NameManager($this->getNames()->namespace, $table);
		// METHOD:getRelationParent
		$method = $this->buildMethod($parent->entityName);
		$method->setReturnType($parent->namespace_entity . '\\' . $parent->entityName);
		$body = <<<MBODY
\${$parent->tableName} = new \\{$parent->namespace_model}\\{$parent->modelName}();
return \${$parent->tableName}->get{$parent->entityName}(\$this->{$columnName});
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
	 */
	protected function addRelationChild(string $tableName) {
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
\$where = array_merge(['fk_{$this->getNames()->tableName}' => \$this->{$this->getNames()->primary}], \$whereArray);
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
		
		foreach ($this->getNames()->properties as $name => $values) {
			// Extract array to $type, $default, $primary
			[
				'type' => $type,
				'default' => $default,
				'primary' => $primary
			] = $values;
			
			$property = new PropertyGenerator($name);
			$property->setVisibility('protected');
			if ($default !== null) {
				$property->setDefaultValue($default);
			}
			
			$property->setDocBlock(DocBlockGenerator::fromArray([
				'shortDescription' => $name,
				'tags' => [
					[
						'name' => 'var',
						'description' => $type
					]
				]
			]));
			$this->_class->addPropertyFromGenerator($property);
			
			$tags[] = [
				'name' => 'property',
				'description' => "{$type} \${$name}"
			];
		}
		
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
		
		$parameterProperty = new ParameterGenerator();
		$parameterProperty->setName('property');
		$parameterProperty->setType('string');
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
		
		// METHOD:__sleep
		$method = $this->buildMethod('__sleep');
		$method->setReturnType('array');
		$body = <<<MBODY
return \$this->getDataTable()->getColumns();
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription('magic method: _sleep');
		$docBlock->setTag($returnTagArray);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		// ===============================================
		
		// METHOD:__wakeup
		$method = $this->buildMethod('__wakeup');
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
		$method->setParameter($parameterProperty);
		// $method->setReturnType('mixed');
		$body = <<<MBODY
if (!in_array(\$property, \$this->getDataTable()->getColumns())) {
	throw new \Exception("Invalid Property:\\n\\t{$this->getNames()->entityName} has no property: {\$property}");
}
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
		$method->setParameter($parameterProperty);
		$method->setParameter($parameterValue);
		$method->setReturnType($returnEntity);
		$body = <<<MBODY
if (!in_array(\$property, \$this->getDataTable()->getColumns())) {
	throw new \Exception("Invalid Property:\\n\\t{$this->getNames()->entityName} has no property: {\$property}");
}
\$method = \$this->parseMethodName(\$property, 'set');
\$this->\$method(\$value);
return \$this;
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
if (!\$this->dataTable) {
	\$this->dataTable = new {$this->getNames()->modelName}();
}
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
			if (0 === strpos($name, 'fk_')) {
				$relationColumns[] = $name;
			}
			$this->addGetterSetter($name);
		}
		foreach ($relationColumns as $columnName) {
			$this->addRelationParent($columnName);
		}
		
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
\$this->{$this->getNames()->primary} = \${$this->getNames()->primary};
\${$this->getNames()->entityVariable} = \$this->getDataTable()->get{$this->getNames()->entityName}(\${$this->getNames()->primary});
if(!\${$this->getNames()->entityVariable}) {
    return null;
}
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
\$this->getDataTable()->delete{$this->getNames()->entityName}(\$this->{$this->getNames()->primary});
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Deletes the entity from table");
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		// ===============================================
		
		// METHOD:exchangeArray
		$method = $this->buildMethod('exchangeArray');
		$method->setParameter($parameterDataArray);
		$method->setParameter($parameterUseDefaults);
		$method->setReturnType($returnEntity);
		$body = <<<MBODY
foreach ( \$this->getDataTable()->getColumns() as \$property ) {
	\$cols = \$this->getDataTable()->getColumnDefaults();
	
	if (is_array(\${$this->getNames()->entityVariable}))
		\${$this->getNames()->entityVariable} = (object)\${$this->getNames()->entityVariable};

	if (property_exists(\${$this->getNames()->entityVariable}, \$property)) {
		\$this->\$property = \${$this->getNames()->entityVariable}->\$property;
	} else if (\$useDefaults) {
		\$this->\$property = \$cols[\$property];
	}
}
return \$this;
MBODY;
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Load the properties from an array");
		$docBlock->setTag(new ParamTag($this->getNames()->entityVariable, [
			'datatype' => 'Array'
		]));
		$docBlock->setTag(new ParamTag('useDefaults', [
			'datatype' => 'bool'
		]));
		$docBlock->setTag($returnTagEntity);
		$method->setDocBlock($docBlock);
		
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		// ===============================================
		
		// METHOD:getArrayCopy
		$method = $this->buildMethod('getArrayCopy');
		$method->setParameter(new ParameterGenerator('ignorePrimaryColumn', 'bool', false));
		$method->setReturnType('array');
		$body = <<<MBODY
\$data = [];
\$columns = \$this->getDataTable()->getColumns();
foreach (\$columns as \$key)
	if (!\$ignorePrimaryColumn || \$key != \$this->getDataTable()->getPrimaryKeyField())
		\$data[\$key] = \$this->\$key;

return \$data;
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
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
