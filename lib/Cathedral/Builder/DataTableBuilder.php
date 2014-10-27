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
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Builds the DataTable
 * @package Cathedral\Builder\Builders
 */
class DataTableBuilder extends BuilderAbstract implements BuilderInterface {
	
	protected $type = self::TYPE_MODEL;
	
	/**
	 * Generate the php file code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupFile()
	 */
	protected function setupFile() {
		$this->_file->setNamespace($this->getNames()->namespace_model);
		
		$this->_file->setUse('Zend\Db\TableGateway\AbstractTableGateway');
		$this->_file->setUse('Zend\Db\TableGateway\Feature');
		$this->_file->setUse('Zend\Db\ResultSet\HydratingResultSet');
		$this->_file->setUse('Zend\Stdlib\Hydrator\Reflection');
		
		$this->_file->setUse('Zend\EventManager\EventManagerInterface');
		$this->_file->setUse('Zend\EventManager\EventManager');
		$this->_file->setUse('Zend\EventManager\EventManagerAwareInterface');
		
		$this->_file->setUse('Zend\Paginator\Adapter\DbSelect');
		$this->_file->setUse('Zend\Paginator\Paginator');
		
		$this->_file->setUse("{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}");
	}
	
	/**
	 * Generate the class code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupClass()
	 */
	protected function setupClass() {
		$this->_class->setName($this->getNames()->modelName);
		$this->_class->setExtendedClass('AbstractTableGateway');
		$this->_class->setImplementedInterfaces(['EventManagerAwareInterface']);
		
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("DataTable for {$this->getNames()->tableName}");
		
		$this->_class->setDocBlock($docBlock);
		
		$property = new PropertyGenerator('isSequence');
		$property->setVisibility('private');
		$property->setDefaultValue($this->getNames()->primaryIsSequence);
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => 'boolean']]]));
		$this->_class->addPropertyFromGenerator($property);
		
		$property = new PropertyGenerator('events');
		$property->setVisibility('protected');
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => '\Zend\EventManager\Event']]]));
		$this->_class->addPropertyFromGenerator($property);
		
		$property = new PropertyGenerator('eventsEnabled');
		$property->setVisibility('protected');
		$property->setDefaultValue(true);
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => 'boolean']]]));
		$this->_class->addPropertyFromGenerator($property);
		
		$this->_file->setClass($this->_class);
	}
	
	/**
	 * Generate the method code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupMethods()
	 */
	protected function setupMethods() {
		//PARAMETERS
		$parameterPrimary = new ParameterGenerator();
		$parameterPrimary->setName($this->getNames()->primary);
		
		$parameterEntity = new ParameterGenerator();
		$parameterEntity->setName($this->getNames()->entityVariable);
		$parameterEntity->setType($this->getNames()->entityName);
		
		$parameterEvent = new ParameterGenerator();
		$parameterEvent->setName('events');
		$parameterEvent->setType('EventManagerInterface');
		
		$parameterPaginator = new ParameterGenerator('paginated');
		$parameterPaginator->setDefaultValue(false);
		
		//===============================================
		
		//METHODS
		// METHOD:enableEvents
		$method = $this->buildMethod('enableEvents');
		$body = <<<MBODY
\$this->eventsEnabled = true;
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Enable Events');
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:disableEvents
		$method = $this->buildMethod('disableEvents');
		$body = <<<MBODY
\$this->eventsEnabled = false;
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Disable Events');
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:setEventManager
		$method = $this->buildMethod("setEventManager");
		$method->setParameter($parameterEvent);
		$body = <<<MBODY
\$events->setIdentifiers([
    __CLASS__,
    get_called_class(),
]);
\$this->events = \$events;
return \$this;
MBODY;
		$method->setBody($body);
		$paramTag = new ParamTag();
		$paramTag->setTypes('\Zend\EventManager\EventManagerInterface');
		$paramTag->setVariableName('events');
		$tag = new ReturnTag();
		$tag->setTypes("{$this->getNames()->modelName}");
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription('Set the event manager instance used by this context');
		$docBlock->setTag($paramTag);
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================

		// METHOD:getEventManager
		$method = $this->buildMethod("getEventManager");
		$body = <<<MBODY
if (null === \$this->events) {
    \$this->setEventManager(new EventManager());
}
return \$this->events;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes("\Zend\EventManager\EventManagerInterface");
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(<<<MBODY
Retrieve the event manager

Lazy-loads an EventManager instance if none registered.
MBODY
);
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:trigger
		$method = $this->buildMethod("trigger", MethodGenerator::FLAG_PRIVATE);
		$method->setParameter(new ParameterGenerator('task'));
		$method->setParameter(new ParameterGenerator('state'));
		$method->setParameter(new ParameterGenerator('argv', null, []));
		$body = <<<MBODY
if (\$this->eventsEnabled) {
    \$data['task'] = \$task;
    \$data['state'] = \$state;
    \$data['data'] = \$argv;
    
    if (\$state == 'post') {
        \$this->getEventManager()->trigger('commit', \$this, \$data);
    }
    \$this->getEventManager()->trigger(\$task.'.'.\$state, \$this, \$data);
}
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Trigger an event');
		$docBlock->setTag(new ParamTag('task', 'string'));
		$docBlock->setTag(new ParamTag('state', 'string'));
		$docBlock->setTag(new ParamTag('argv', 'array|object'));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
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
		$docBlock = new DocBlockGenerator('Create DataTable Object');
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:featchAll
		$method = $this->buildMethod('featchAll');
		$method->setParameter($parameterPaginator);
		$body = <<<MBODY
if (\$paginated) {
	// create a new Select object for the table
	\$select = \$this->sql->select();
	// create a new pagination adapter object
	\$paginatorAdapter = new DbSelect(
		// our configured select object
		\$select,
		// the adapter to run it against
		\$this->getAdapter(),
		// the result set to hydrate
		\$this->resultSetPrototype
	);
	\$paginator = new Paginator(\$paginatorAdapter);
	return \$paginator;
}
\$resultSet = \$this->select();
return \$resultSet;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes(["\\".$this->getNames()->namespace_entity."\\{$this->getNames()->entityName}[]","\\".$this->getNames()->namespace_entity."\\{$this->getNames()->entityName}"]);
		$docBlock = new DocBlockGenerator('Fetch all entities');
		$docBlock->setTag(new ParamTag('paginated', ['boolean'], 'True: use paginator'));
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:get
		$method = $this->buildMethod("get{$this->getNames()->entityName}");
		$method->setParameter($parameterPrimary);
		$body = <<<MBODY
\$rowset = \$this->select(['{$this->getNames()->primary}' => \${$this->getNames()->primary}]);
\$row = \$rowset->current();
return \$row;
MBODY;
		$method->setBody($body);
		
		$tag = new ReturnTag();
		$tag->setTypes("\\{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}");
		$docBlock = new DocBlockGenerator('Get by primaryId');
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
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
	\$this->trigger('insert', 'pre', \$data);
	\$this->insert(\$data);
	if (\$this->isSequence) {
		\${$this->getNames()->entityVariable}->{$this->getNames()->primary} = \$this->lastInsertValue;
	}
	\$this->trigger('insert', 'post', \$technique->getArrayCopy());
} else {
	\$row = \$this->get{$this->getNames()->entityName}(\${$this->getNames()->primary});
	if (\$row) {
		\$data = array_diff_assoc(\$data, \$row->getArrayCopy());
		if (count(\$data) > 0) {
		     \$this->trigger('update', 'pre', \$data);
			\$this->update(\$data, ['{$this->getNames()->primary}' => \${$this->getNames()->primary}]);
			\$this->trigger('update', 'post', \$data);
		}
	} else {
		throw new \Exception('{$this->getNames()->entityName} {$this->getNames()->primary} does not exist');
	}
}
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Save entity to database');
		$docBlock->setTag(new ParamTag($this->getNames()->entityVariable, $this->getNames()->entityName));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
		
		//===============================================
		
		// METHOD:delete
		$method = $this->buildMethod("delete{$this->getNames()->entityName}");
		$method->setParameter($parameterPrimary);
		$body = <<<MBODY
\$this->trigger('delete', 'pre', ['{$this->getNames()->primary}' => \${$this->getNames()->primary}]);
\$this->delete(['{$this->getNames()->primary}' => \${$this->getNames()->primary}]);
\$this->trigger('delete', 'post', ['{$this->getNames()->primary}' => \${$this->getNames()->primary}]);
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Delete entity');
		$docBlock->setTag(new ParamTag($this->getNames()->primary));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
	}

}