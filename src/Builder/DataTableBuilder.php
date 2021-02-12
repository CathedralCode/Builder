<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * PHP version 7
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2021 Philip Michael Raab <peep@inane.co.za>
 */

namespace Cathedral\Builder;

use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\DocBlock\Tag\ParamTag;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\PropertyGenerator;

/**
 * Builds the DataTable
 *
 * @package Cathedral\Builder\Builders
 * @version 0.11.0
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

        $this->_file->setUse('Laminas\Db\TableGateway\TableGateway');
        $this->_file->setUse('Laminas\Db\Sql\TableIdentifier');
        $this->_file->setUse('Laminas\Db\TableGateway\AbstractTableGateway');
        $this->_file->setUse('Laminas\Db\TableGateway\Feature');
        $this->_file->setUse('Laminas\Db\TableGateway\Feature\EventFeature\TableGatewayEvent');
        $this->_file->setUse('Laminas\Db\TableGateway\Feature\EventFeatureEventsInterface');
		$this->_file->setUse('Laminas\Db\ResultSet\HydratingResultSet');
		$this->_file->setUse('Laminas\Hydrator\ArraySerializableHydrator');

		$this->_file->setUse('Laminas\EventManager\EventManagerInterface');
        $this->_file->setUse('Laminas\EventManager\EventManager');
        $this->_file->setUse('Laminas\EventManager\SharedEventManager');
		$this->_file->setUse('Laminas\EventManager\EventManagerAwareInterface');

		$this->_file->setUse('Laminas\Paginator\Adapter\DbSelect');
		$this->_file->setUse('Laminas\Paginator\Paginator');

		$this->_file->setUse('Laminas\Db\Sql\Select');
        $this->_file->setUse('Laminas\Db\Sql\Where');
        
        $this->_file->setUse('Exception');
        
		$this->_file->setUse("{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}");
        
		$this->_file->setUse('function array_diff_assoc');
		$this->_file->setUse('function array_filter');
		$this->_file->setUse('function array_pop');
	}

	/**
	 * Generate the class code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupClass()
	 */
	protected function setupClass() {
		$this->_class->setName($this->getNames()->modelName);
		$this->_class->setExtendedClass('AbstractTableGateway');
		$this->_class->setImplementedInterfaces(['EventManagerAwareInterface', 'EventFeatureEventsInterface']);

		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("DataTable for {$this->getNames()->tableName}");

        $this->_class->setDocBlock($docBlock);
        
        // table
        $property = new PropertyGenerator('table');
		$property->setVisibility('protected');
		$property->setDefaultValue($this->getNames()->tableName);
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => 'string|array|TableIdentifier']]]));
		$this->_class->addPropertyFromGenerator($property);

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
		foreach ($this->getNames()->properties as $key => $value) $columnDefault[$key] = $value['default'];
		$property = new PropertyGenerator('columnDefaults');
		$property->setVisibility('protected');
		$property->setDefaultValue($columnDefault);
		$property->setDocBlock(DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => 'Array default values']]]));
		$this->_class->addPropertyFromGenerator($property);

		// events
		$property = new PropertyGenerator('event');
        $property->setVisibility('protected');
        $property->setDefaultValue(null);
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => 'TableGatewayEvent Event']]]));
		$this->_class->addPropertyFromGenerator($property);

		$property = new PropertyGenerator('eventManager');
		$property->setVisibility('protected');
		$property->setDefaultValue(null);
		$property->setDocBlock(DocBlockGenerator::fromArray([
		    'tags' => [[
		        'name' => 'var',
		        'description' => 'EventManagerInterface EventManager']]]));
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

		$parameterEventManager = new ParameterGenerator();
		$parameterEventManager->setName('eventManager');
		$parameterEventManager->setType('\Laminas\EventManager\EventManagerInterface');

		$parameterPaginator = new ParameterGenerator('paginated');
		$parameterPaginator->setDefaultValue(false);

		//===============================================

		//METHODS
		// METHOD:setEventManager
		$method = $this->buildMethod('setEventManager');
		$method->setParameter($parameterEventManager);
        $body = <<<MBODY
\$eventManager->addIdentifiers([
    self::class,
    array_pop(explode('\\\', self::class)),
    TableGateway::class,
]);
\$this->event = \$this->event ?: new TableGatewayEvent();
\$this->eventManager = \$eventManager;
return \$this;
MBODY;
		$method->setBody($body);
		$paramTag = new ParamTag();
		$paramTag->setTypes('\Laminas\EventManager\EventManagerInterface');
		$paramTag->setVariableName('eventManager');
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
if (!\$this->eventManager instanceof EventManagerInterface) \$this->setEventManager(new EventManager(new SharedEventManager()));
return \$this->eventManager;
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes('\Laminas\EventManager\EventManagerInterface');
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

		// METHOD:__construct
		$method = $this->buildMethod('__construct');
		$body = <<<MBODY
\$this->featureSet = new Feature\FeatureSet();
\$this->featureSet->addFeature(new Feature\GlobalAdapterFeature());
\$this->featureSet->addFeature(new Feature\MetadataFeature());
\$this->featureSet->addFeature(new Feature\EventFeature(\$this->getEventManager()));

\$this->initialize();
\$this->resultSetPrototype = new HydratingResultSet(new ArraySerializableHydrator(), new {$this->getNames()->entityName}(\$this));
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
		$tag->setTypes("\\Laminas\\Db\\ResultSet\\HydratingResultSet|\\Laminas\\Paginator\\Paginator");
		$docBlock = new DocBlockGenerator('Fetch all entities');
		$docBlock->setTag(new ParamTag('paginated', ['boolean'], 'True: use paginator'));
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:selectPaginated
		$method = $this->buildMethod('selectPaginated');
		$method->setParameter(new ParameterGenerator('where', null, false));
		$body = <<<MBODY
\$select = \$this->sql->select();
if (\$where instanceof \Closure) \$where(\$select);
elseif (\$where !== null) \$select->where(\$where);

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
MBODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes("\\Laminas\\Paginator\\Paginator");
		$docBlock = new DocBlockGenerator('Select entities and return as paginated');
		$docBlock->setTag(new ParamTag('where', ['string', 'array', '\Closure', 'Where'], 'Where'));
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
		$tag->setTypes("\\Laminas\\Db\\ResultSet\\HydratingResultSet");
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
\$data = \${$this->getNames()->entityVariable}->getArrayCopy(null, true);
\${$this->getNames()->primary} = \${$this->getNames()->entityVariable}->{$this->getNames()->primary};
\$row = \$this->get{$this->getNames()->entityName}(\${$this->getNames()->primary});
if (\$row) {
	\$data = array_diff_assoc(\$data, \$row->getArrayCopy());
	if (count(\$data) > 0) \$this->update(\$data, [\$this->getPrimaryKeyField() => \${$this->getNames()->primary}]);
} else {
	if ((\$this->isSequence && !\${$this->getNames()->primary}) || (!\$this->isSequence && \${$this->getNames()->primary})) {
        \$data = array_filter(\$data);
		\$data['{$this->getNames()->primary}'] = \${$this->getNames()->primary};
		\$this->insert(\$data);
		if (\$this->isSequence) \${$this->getNames()->entityVariable}->{$this->getNames()->primary} = \$this->lastInsertValue;
	} else throw new Exception('{$this->getNames()->entityName} {$this->getNames()->primary} error with insert/update');
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
\$this->delete([\$this->getPrimaryKeyField() => \${$this->getNames()->primary}]);
MBODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Delete entity');
		$docBlock->setTag(new ParamTag($this->getNames()->primary));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
	}

}
