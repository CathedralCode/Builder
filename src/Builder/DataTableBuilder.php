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

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Builds the DataTable
 * 
 * @package Cathedral\Builder\Builders
 * @namespace \Cathedral\Builder
 */
class DataTableBuilder extends BuilderAbstract {

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
		$this->_file->setUse('Zend\Hydrator\ReflectionHydrator');

		$this->_file->setUse('Zend\EventManager\EventManagerInterface');
		$this->_file->setUse('Zend\EventManager\EventManager');
		$this->_file->setUse('Zend\EventManager\SharedEventManager');
		$this->_file->setUse('Zend\EventManager\EventManagerAwareInterface');

		$this->_file->setUse('Zend\Paginator\Adapter\DbSelect');
		$this->_file->setUse('Zend\Paginator\Paginator');
		
		$this->_file->setUse('Zend\Db\Sql\Select');

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

		// isSequence
		$property = new PropertyGenerator('isSequence');
		$property->setVisibility('private');
		$property->setDefaultValue($this->getNames()->primaryIsSequence);
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => 'boolean is primary key autonumbered']]]));
		$this->_class->addPropertyFromGenerator($property);
		
		// primaryKeyField
		$property = new PropertyGenerator('primaryKeyField');
		$property->setVisibility('private');
		$property->setDefaultValue($this->getNames()->primary);
		$property->setDocBlock(DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => 'string name of primary key']]]));
		$this->_class->addPropertyFromGenerator($property);

		// columnDefaults
		$columnDefault = [];
		foreach ($this->getNames()->properties as $key => $value) {
			$columnDefault[$key] = $value['default'];
		}
		$property = new PropertyGenerator('columnDefaults');
		$property->setVisibility('protected');
		$property->setDefaultValue($columnDefault);
		$property->setDocBlock(DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => 'Array default values']]]));
		$this->_class->addPropertyFromGenerator($property);

		// events
		$property = new PropertyGenerator('events');
		$property->setVisibility('protected');
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => '\Zend\EventManager\Event Event Manager']]]));
		$this->_class->addPropertyFromGenerator($property);

		$property = new PropertyGenerator('eventsEnabled');
		$property->setVisibility('protected');
		$property->setDefaultValue(true);
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => 'boolean Event Status']]]));
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
		
		$paramTagPrimary = new ParamTag();
		$paramTagPrimary->setTypes([$this->getNames()->properties[$this->getNames()->primary]['type']]);
		$paramTagPrimary->setVariableName($this->getNames()->primary);

		$parameterEntity = new ParameterGenerator();
		$parameterEntity->setName($this->getNames()->entityVariable);
		$parameterEntity->setType($this->getNames()->namespace_entity . '\\' . $this->getNames()->entityName);
		
		$parameterEvent = new ParameterGenerator();
		$parameterEvent->setName('events');
		$parameterEvent->setType('Zend\EventManager\EventManagerInterface');
		
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
		$method = $this->buildMethod('setEventManager');
		$method->setParameter($parameterEvent);
		$body = <<<MBODY
\$events->setIdentifiers([
    __CLASS__,
    array_pop(explode('\\\\', __CLASS__))
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
		$method = $this->buildMethod('getEventManager');
		$body = <<<MBODY
if (null === \$this->events) {
    \$this->setEventManager(new EventManager(new SharedEventManager()));
}
return \$this->events;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes('\Zend\EventManager\EventManagerInterface');
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
		
		// METHOD:getPrimaryKeyField
		$method = $this->buildMethod('getPrimaryKeyField');
		$body = <<<MBODY
return \$this->primaryKeyField;
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(<<<MBODY
Get PrimaryKey Field
MBODY
				);
		$docBlock->setTag(new ReturnTag(['string']));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:trigger
		$method = $this->buildMethod('trigger', MethodGenerator::FLAG_PRIVATE);
		$method->setParameter(new ParameterGenerator('task'));
		$method->setParameter(new ParameterGenerator('state'));
		$method->setParameter(new ParameterGenerator('data', null, []));
		$body = <<<MBODY
if (\$this->eventsEnabled) {
	\$table = \$this->table;
    \$info = compact('table', 'task', 'state', 'data');

    if (\$state == 'post') {
        \$this->getEventManager()->trigger('commit', \$this, \$info);
    }
    \$this->getEventManager()->trigger(\$task.'.'.\$state, \$this, \$info);
}
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Trigger an event');
		$docBlock->setTag(new ParamTag('task', 'string'));
		$docBlock->setTag(new ParamTag('state', 'string'));
		$docBlock->setTag(new ParamTag('data', 'array|object'));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:__construct
		$method = $this->buildMethod('__construct');
		$body = <<<MBODY
\$this->table = '{$this->getNames()->tableName}';
\$this->featureSet = new Feature\FeatureSet();
\$this->featureSet->addFeature(new Feature\GlobalAdapterFeature());
\$this->featureSet->addFeature(new Feature\MetadataFeature());

\$this->resultSetPrototype = new HydratingResultSet(new ReflectionHydrator(), new {$this->getNames()->entityName}());

\$this->initialize();
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Create DataTable Object');
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:getEntity
		$method = $this->buildMethod('getEntity');
		$body = <<<MBODY
return new \\{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}();
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(<<<MBODY
Get Empty Entity
MBODY
		);
		$docBlock->setTag(new ReturnTag(['\\'.$this->getNames()->namespace_entity."\\{$this->getNames()->entityName}"]));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:getColumnDefaults
		$method = $this->buildMethod('getColumnDefaults');
		$body = <<<MBODY
return \$this->columnDefaults;
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(<<<MBODY
Get Column Default
MBODY
		);
		$docBlock->setTag(new ReturnTag(['Array']));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:fetchAll
		$method = $this->buildMethod('fetchAll');
		$method->setParameter($parameterPaginator);
		$body = <<<MBODY
if (\$paginated) {
	// create a new pagination adapter object
	\$paginatorAdapter = new DbSelect(
		// our configured select object
		\$this->sql->select(),
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
		$tag->setTypes("\\Zend\\Db\\ResultSet\\HydratingResultSet");
		$docBlock = new DocBlockGenerator('Fetch all entities');
		$docBlock->setTag(new ParamTag('paginated', ['boolean'], 'True: use paginator'));
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:selectUsing
		$method = $this->buildMethod('selectUsing');
		$method->setParameter(new ParameterGenerator('order', null, false));
		$method->setParameter(new ParameterGenerator('where', null, false));
		$method->setParameter(new ParameterGenerator('limit', null, false));
		$body = <<<MBODY
\$select = new Select(\$this->table);
if (\$order) \$select->order(\$order);
if (\$where) \$select->where(\$where);
if (\$limit) \$select->limit(\$limit);

return \$this->selectWith(\$select);
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes("\\Zend\\Db\\ResultSet\\HydratingResultSet");
		$docBlock = new DocBlockGenerator('Select Using Where/Order');
		$docBlock->setTag(new ParamTag('order', ['string', 'array'], 'Sort Order'));
		$docBlock->setTag(new ParamTag('where', ['string', 'array', '\Closure', 'Where'], 'Where'));
		$docBlock->setTag(new ParamTag('limit', ['int'], 'Limit'));
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:get
		$method = $this->buildMethod("get{$this->getNames()->entityName}");
		$method->setParameter($parameterPrimary);
		$body = <<<MBODY
\$rowset = \$this->select([\$this->getPrimaryKeyField() => \${$this->getNames()->primary}]);
\$row = \$rowset->current();
return \$row;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes("\\{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}");
		$docBlock = new DocBlockGenerator('Get by primaryId');
		$docBlock->setTag($paramTagPrimary);
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:save
		$method = $this->buildMethod("save{$this->getNames()->entityName}");
		$method->setParameter($parameterEntity);

		$body = <<<MBODY
\$data = \${$this->getNames()->entityVariable}->getArrayCopy(true);
\${$this->getNames()->primary} = \${$this->getNames()->entityVariable}->{$this->getNames()->primary};
\$row = \$this->get{$this->getNames()->entityName}(\${$this->getNames()->primary});
if (\$row) {
	\$data = array_diff_assoc(\$data, \$row->getArrayCopy());
	if (count(\$data) > 0) {
		\$this->trigger('update', 'pre', \${$this->getNames()->primary});
		\$this->update(\$data, [\$this->getPrimaryKeyField() => \${$this->getNames()->primary}]);
		\$this->trigger('update', 'post', \${$this->getNames()->primary});
	}
} else {
	if ((\$this->isSequence && !\${$this->getNames()->primary}) || (!\$this->isSequence && \${$this->getNames()->primary})) {
		\$data['{$this->getNames()->primary}'] = \${$this->getNames()->primary};
		\$this->trigger('insert', 'pre', \$data);
		\$this->insert(array_filter(\$data));
		if (\$this->isSequence) {
			\${$this->getNames()->entityVariable}->{$this->getNames()->primary} = \$this->lastInsertValue;
		}
		\$this->trigger('insert', 'post', \${$this->getNames()->entityVariable}->{$this->getNames()->primary});
	} else {
		throw new \Exception('{$this->getNames()->entityName} {$this->getNames()->primary} error with insert/update');
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
\$this->trigger('delete', 'pre', \${$this->getNames()->primary});
\$this->delete([\$this->getPrimaryKeyField() => \${$this->getNames()->primary}]);
\$this->trigger('delete', 'post', \${$this->getNames()->primary});
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Delete entity');
		$docBlock->setTag(new ParamTag($this->getNames()->primary));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
	}

}
