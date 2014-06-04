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

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Builders the Model
 * @package Cathedral\Builder\Builders
 */
class DataTableBuilder extends BuilderAbstract implements BuilderInterface {
	
	protected $type = self::TYPE_MODEL;
	
	protected function setupFile() {
		$this->_file->setNamespace($this->getNames()->namespace_model);
		
		$this->_file->setUse('Zend\Db\TableGateway\AbstractTableGateway');
		$this->_file->setUse('Zend\Db\TableGateway\Feature');
		$this->_file->setUse('Zend\Db\ResultSet\HydratingResultSet');
		$this->_file->setUse('Zend\Stdlib\Hydrator\Reflection');
		$this->_file->setUse("{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}");
	}
	
	protected function setupClass() {
		$this->_class->setName($this->getNames()->modelName);
		$this->_class->setExtendedClass('AbstractTableGateway');
		
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("DataTable for {$this->getNames()->tableName}");
		
		$this->_class->setDocBlock($docBlock);
		
		$property = new PropertyGenerator();
		$property->setName('isSequence');
		$property->setDefaultValue($this->getNames()->primaryIsSequence);
		$property->setVisibility('private');
		//$property->setDocBlock($docBlock);
		$this->_class->addPropertyFromGenerator($property);
		
		$this->_file->setClass($this->_class);
	}
	
	protected function setupMethods() {
		//PARAMETERS
		$parameterPrimary = new ParameterGenerator();
		$parameterPrimary->setName($this->getNames()->primary);
		
		$parameterEntity = new ParameterGenerator();
		$parameterEntity->setName($this->getNames()->entityVariable);
		$parameterEntity->setType($this->getNames()->entityName);
		
		//METHODS
		// METHOD:__construct
		$method = $this->buildMethod('__construct');
		$body = <<<MBODY
\$this->table = '{$this->getNames()->tableName}';
\$this->featureSet = new Feature\FeatureSet();
\$this->featureSet->addFeature(new Feature\GlobalAdapterFeature());

\$this->resultSetPrototype = new HydratingResultSet(new Reflection(), new {$this->getNames()->entityName}());

\$this->initialize();
MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		// METHOD:featchAll
		$method = $this->buildMethod('featchAll');
		$body = <<<MBODY
\$resultSet = \$this->select();
return \$resultSet;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setDatatype("\\".$this->getNames()->namespace_entity."\\{$this->getNames()->entityName}[]|\\".$this->getNames()->namespace_entity."\\{$this->getNames()->entityName}");
		$docBlock = new DocBlockGenerator();
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		// METHOD:get
		$method = $this->buildMethod("get{$this->getNames()->entityName}");
		$method->setParameter($parameterPrimary);
		$body = <<<MBODY
\$rowset = \$this->select(array('{$this->getNames()->primary}' => \${$this->getNames()->primary}));
\$row = \$rowset->current();
if (!\$row) {
	throw new \Exception("Could not find {$this->getNames()->entityName} \${$this->getNames()->primary}");
}
return \$row;
MBODY;
		$method->setBody($body);
		
		$tag = new ReturnTag();
		$tag->setDatatype("\\{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}");
		$docBlock = new DocBlockGenerator();
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		
		// METHOD:save
		$method = $this->buildMethod("save{$this->getNames()->entityName}");
		$method->setParameter($parameterEntity);
		
		$body = "\$data = array(\n";
		foreach ($this->getNames()->properties as $field => $info) {
			if (!$info['primary']) {
				$body .= "	'{$field}' => \${$this->getNames()->entityVariable}->{$field},\n";
			}
		}
		$body .= ");\n";
		
		$body .= <<<MBODY
\${$this->getNames()->primary} = \${$this->getNames()->entityVariable}->{$this->getNames()->primary};
if (\${$this->getNames()->primary} == null) {
	\$data = array_filter(\$data, 'strlen');
	\$this->insert(\$data);
	if (\$this->isSequence) {
		\${$this->getNames()->entityVariable}->{$this->getNames()->primary} = \$this->lastInsertValue;
	}
} else {
	if (\$this->get{$this->getNames()->entityName}(\${$this->getNames()->primary})) {
		\$this->update(\$data, array('{$this->getNames()->primary}' => \${$this->getNames()->primary}));
	} else {
		throw new \Exception('{$this->getNames()->entityName} {$this->getNames()->primary} does not exist');
	}
}
MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
		
		// METHOD:delete
		$method = $this->buildMethod("delete{$this->getNames()->entityName}");
		$method->setParameter($parameterPrimary);
		$body = <<<MBODY
\$this->delete(array('{$this->getNames()->primary}' => \${$this->getNames()->primary}));
MBODY;
		$method->setBody($body);
		$this->_class->addMethodFromGenerator($method);
	}

}