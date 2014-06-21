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

use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\PropertyValueGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Filter\Null;

/**
 * Builders the Abstract Entity
 * @package Cathedral\Builder\Builders
 */
class EntityAbstractBuilder extends BuilderAbstract implements BuilderInterface {
	
	/**
	 * @var string
	 */
	protected $type = self::TYPE_ENTITYABSTRACT;
	
	/* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderAbstract::setupFile()
	 */
	protected function setupFile() {
		$this->_file->setNamespace($this->getNames()->namespace_entity);
	
		$this->_file->setUse('Zend\Db\RowGateway\RowGatewayInterface');
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
	private function parseMethodName($property, $prepend = 'get') {
		return $prepend.str_replace(' ','',ucwords(str_replace('_',' ',$property)));
	}
	
	/**
	 * Create getter & setter methods for properties
	 * 
	 * @param string $property
	 */
	protected function addGetterSetter($property) {
		$properyName = $this->parseMethodName($property, '');
		$getter = "get{$properyName}";
		$setter = "set{$properyName}";
		
		//METHODS
		// METHOD:getPropperty
		$method = $this->buildMethod($getter);
		$body = <<<MBODY
return \$this->{$property};
MBODY;
		$method->setBody($body);
		$method->setDocBlock(DocBlockGenerator::fromArray([
			'shortDescription' => "Get the {$property} property",
			'tags' => [
			new ReturnTag(['datatype' => $this->getNames()->properties[$property]['type']])
			]]));
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:setPropperty
		$parameterSetter = new ParameterGenerator();
		$parameterSetter->setName($property);
		$method = $this->buildMethod($setter);
		$method->setParameter($parameterSetter);
		$body = <<<MBODY
\$this->{$property} = \${$property};
MBODY;
		$method->setBody($body);
		$method->setDocBlock(DocBlockGenerator::fromArray([
			'shortDescription' => "Set the {$property} property",
			'tags' => [
			new ParamTag($property, ['datatype' => $this->getNames()->properties[$property]['type']])
			]]));
		$this->_class->addMethodFromGenerator($method);
	}
	
	/**
	 * Create method to return related Parent entity
	 *  linked to foreign key stored in this coloumn
	 * 
	 * @param string $columnName
	 */
	protected function addRelationParent($columnName) {
		$table = substr($columnName, 3);
		$parent = new NameManager($this->getNames()->namespace, $table);
		// METHOD:getRelationParent
		$method = $this->buildMethod("get{$parent->entityName}");
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
	 *  this primary key found in table
	 *  
	 * @param string $tableName
	 */
	protected function addRelationChild($tableName) {
		$child = new NameManager($this->getNames()->namespace, $tableName);
		
		// METHOD:getRelationChild
		$method = $this->buildMethod("get{$child->entityName}s");
		$body = <<<MBODY
\${$child->tableName} = new \\{$child->namespace_model}\\{$child->modelName}();
return \${$child->tableName}->select(['fk_{$this->getNames()->tableName}', \$this->{$this->getNames()->primary}]);
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes("\\{$child->namespace_entity}\\{$child->entityName}[]");
		$docBlock = new DocBlockGenerator();
		$docBlock->setTag($tag);
		$docBlock->setShortDescription("Related {$child->entityName}");
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
	}
	
	/* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderAbstract::setupClass()
	 */
	protected function setupClass() {
		$this->_class->setName($this->getNames()->entityAbstractName);
		$this->_class->setImplementedInterfaces(['RowGatewayInterface']);
		$this->_class->setAbstract(true);
	
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Entity for {$this->getNames()->tableName}");
		$this->_class->setDocBlock($docBlock);
		
		foreach ($this->getNames()->properties as $name => $value) {
			$property = new PropertyGenerator($name);
			$property->setVisibility('protected');
			if ($value['default'] != null) {
				$property->setDefaultValue($value['default']);
			}
			
			$property->setDocBlock(DocBlockGenerator::fromArray([
				'tags' => [[
					'name' => 'var',
					'description' => $value['type']]]]));
			$this->_class->addPropertyFromGenerator($property);
		}
		
		$docBlock = DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => "\\{$this->getNames()->namespace_model}\\{$this->getNames()->modelName}"]]]);
		
		$property = new PropertyGenerator();
		$property->setName('dataTable');
		$property->setDocBlock($docBlock);
		$property->setVisibility('protected');
		$this->_class->addPropertyFromGenerator($property);
		
		$this->_file->setClass($this->_class);
	}
	
	/* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderAbstract::setupMethods()
	 */
	protected function setupMethods() {
		//PARAMETERS
		$parameterPrimary = new ParameterGenerator();
		$parameterPrimary->setName($this->getNames()->primary);
		
		$parameterProperty = new ParameterGenerator();
		$parameterProperty->setName('property');
		//--
		$paramTagProperty = new ParamTag();
		$paramTagProperty->setTypes(['string']);
		$paramTagProperty->setVariableName('property');
		
		$parameterValue = new ParameterGenerator();
		$parameterValue->setName('value');
		
		$parameterPrepend = new ParameterGenerator();
		$parameterPrepend->setName('prepend');
		$parameterPrepend->setDefaultValue('get');
		//--
		$paramTagPrepend = new ParamTag();
		$paramTagPrepend->setTypes(['string']);
		$paramTagPrepend->setVariableName('prepend');
			
		$parameterDateArray = new ParameterGenerator();
		$parameterDateArray->setName($this->getNames()->entityVariable);
		
		$returnTagString = new ReturnTag();
		$returnTagString->setTypes(['string']);
		
		$propertyArrayString = '["'.implode('","', array_keys($this->getNames()->properties)).'"]';
		
		//===============================================
		
		//METHODS
		// METHOD:parseMethodName
		$method = $this->buildMethod('parseMethodName');
		$method->setVisibility('private');
		$method->setParameter($parameterProperty);
		$method->setParameter($parameterPrepend);
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
		
		//===============================================
		
		// METHOD:__get
		$method = $this->buildMethod('__get');
		$method->setParameter($parameterProperty);
		$body = <<<MBODY
if (!in_array(\$property, {$propertyArrayString})) {
	throw new \Exception("Invalid Property:\\n\\t{$this->getNames()->entityName} has no property: {\$property}");
}
\$method = \$this->parseMethodName(\$property);
return \$this->\$method();
MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:__set
		$method = $this->buildMethod('__set');
		$method->setParameter($parameterProperty);
		$method->setParameter($parameterValue);
		$body = <<<MBODY
if (!in_array(\$property, {$propertyArrayString})) {
	throw new \Exception("Invalid Property:\\n\\t{$this->getNames()->entityName} has no property: {\$property}");
}
\$method = \$this->parseMethodName(\$property, 'set');
\$this->\$method(\$value);
MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:getDataTable
		$method = $this->buildMethod('getDataTable');
		$body = <<<MBODY
if (!\$this->dataTable) {
	\$this->dataTable = new {$this->getNames()->modelName}();
}
return \$this->dataTable;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes(["\\{$this->getNames()->namespace_model}\\{$this->getNames()->modelName}"]);
		$docBlock = new DocBlockGenerator();
		$docBlock->setTag($tag);
		$docBlock->setShortDescription("DataTable for {$this->getNames()->entityName}");
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:Getter/Setter
		$relationColumns = [];
		foreach ($this->getNames()->properties as $name => $value) {
			if (0 === strpos($name, 'fk_')) {
				$relationColumns[] = $name;
			}
			$this->addGetterSetter($name);
		}
		foreach ($relationColumns as $columnName) {
			$this->addRelationParent($columnName);
		}
		
		//===============================================
		
		// METHOD:RelationChildren
		foreach ($this->getNames()->relationChildren as $tableName) {
			$this->addRelationChild($tableName);
		}
		
		//===============================================
		
		// METHOD:get
		$method = $this->buildMethod('get');
		$method->setParameter($parameterPrimary);
		
		$body = <<<MBODY
\${$this->getNames()->entityVariable} = \$this->getDataTable()->get{$this->getNames()->entityName}(\${$this->getNames()->primary});
return \${$this->getNames()->entityVariable};
MBODY;
		$paramTag = new ParamTag();
		$paramTag->setTypes('mixed');
		$paramTag->setVariableName($this->getNames()->primary);
		$returnTag = new ReturnTag();
		$returnTag->setTypes($this->getNames()->entityName);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Get {$this->getNames()->entityName} by primary key value");
		$docBlock->setTag($paramTag);
		$docBlock->setTag($returnTag);
		$method->setDocBlock($docBlock);
		
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:save
		$method = $this->buildMethod('save');
		$body = <<<MBODY
\$this->getDataTable()->save{$this->getNames()->entityName}(\$this);
MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:delete
		$method = $this->buildMethod('delete');
		$body = <<<MBODY
\$this->getDataTable()->delete{$this->getNames()->entityName}(\$this->{$this->getNames()->primary});
MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:exchangeArray
		$method = $this->buildMethod('exchangeArray');
		$method->setParameter($parameterDateArray);
		
		$body = '';
		foreach ($this->getNames()->properties as $field => $info) {
			$body .= "\$this->{$field}     = (!empty(\${$this->getNames()->entityVariable}['{$field}'])) ? \${$this->getNames()->entityVariable}['{$field}'] : null;\n";
		}
		
		$body .= <<<MBODY

MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:getArrayCopy
		$method = $this->buildMethod('getArrayCopy');
		$body = <<<MBODY
\$array = get_object_vars(\$this);
unset(\$array['dataTable']);
return \$array;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes("Array");
		$docBlock = new DocBlockGenerator();
		$docBlock->setTag($tag);
		$docBlock->setShortDescription("Array copy of object");
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
	}
}
