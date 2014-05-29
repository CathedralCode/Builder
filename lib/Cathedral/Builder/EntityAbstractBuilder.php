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

use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\PropertyValueGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Filter\Null;

/**
 *
 * @author Philip Michael Raab<peep@cathedral.co.za>
 */
class EntityAbstractBuilder extends BuilderAbstract implements BuilderInterface {
	
	protected $type = self::TYPE_ENTITYABSTRACT;
	
	protected function setupFile() {
		$this->_file->setNamespace($this->getNames()->namespace_entity);
	
		$this->_file->setUse('Zend\Db\RowGateway\RowGatewayInterface');
		$this->_file->setUse($this->getNames()->namespace_model."\\{$this->getNames()->modelName}");
	}
	
	
	protected function setupClass() {
		$this->_class->setName($this->getNames()->entityAbstractName);
		$this->_class->setImplementedInterfaces(['RowGatewayInterface']);
		$this->_class->setAbstract(true);
	
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Entity for {$this->getNames()->tableName}");
		$this->_class->setDocBlock($docBlock);
		
		foreach ($this->getNames()->properties as $key => $value) {
			$property = new PropertyGenerator($key);
			if ($value['default'] == 'CURRENT_TIMESTAMP') {
				//$property->setDefaultValue("{$this->getNames()->modelName}::BLANK_DEFAULT", PropertyValueGenerator::TYPE_CONSTANT, PropertyValueGenerator::OUTPUT_SINGLE_LINE);
			} else {
				$property->setDefaultValue($value['default']);
			}
			
			$this->_class->addPropertyFromGenerator($property);
		}
		
		$docBlock = DocBlockGenerator::fromArray(array(
				'tags' => array(
						array(
								'name'        => 'var',
								'description' => "\\".$this->getNames()->namespace_model."\\{$this->getNames()->modelName}",
						),
				),
		));
		
		$property = new PropertyGenerator();
		$property->setName('dataTable');
		$property->setDocBlock($docBlock);
		$this->_class->addPropertyFromGenerator($property);
		
		$this->_file->setClass($this->_class);
	}
	
	protected function setupMethods() {
		//PARAMETERS
		$parameterPrimary = new ParameterGenerator();
		$parameterPrimary->setName($this->getNames()->primary);
			
		$parameterDateArray = new ParameterGenerator();
		$parameterDateArray->setName($this->getNames()->entityVariable);
		
		//===============================================
		
		//METHODS
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
		$tag->setDatatype("\\".$this->getNames()->namespace_model."\\{$this->getNames()->modelName}");
		$docBlock = new DocBlockGenerator();
		$docBlock->setTag($tag);
		$docBlock->setShortDescription("DataTable for entity");
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
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
		$tag->setDatatype("Array");
		$docBlock = new DocBlockGenerator();
		$docBlock->setTag($tag);
		$docBlock->setShortDescription("Array copy of object");
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
	}
}
