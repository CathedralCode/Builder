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
 * @copyright 2013-2021 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Builder;

use Laminas\Code\Generator\DocBlock\Tag\{
	ParamTag,
	ReturnTag
};
use Laminas\Code\{
	Generator\DocBlockGenerator,
	Generator\ParameterGenerator,
	Generator\PropertyGenerator,
	DeclareStatement
};

/**
 * Builds the DataTable
 *
 * @package Cathedral\Builder\Builders
 * @version 0.11.4
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

		$this->_file->setUse('Laminas\Db\TableGateway\TableGateway')
			->setUse('Laminas\Db\Sql\TableIdentifier')
			->setUse('Laminas\Db\TableGateway\AbstractTableGateway')
			->setUse('Laminas\Db\TableGateway\Feature')
			->setUse('Laminas\Db\TableGateway\Feature\EventFeature\TableGatewayEvent')
			->setUse('Laminas\Db\TableGateway\Feature\EventFeatureEventsInterface')
			->setUse('Laminas\Db\ResultSet\HydratingResultSet')
			->setUse('Laminas\Hydrator\ArraySerializableHydrator')

			->setUse('Laminas\EventManager\EventManagerInterface')
			->setUse('Laminas\EventManager\EventManager')
			->setUse('Laminas\EventManager\SharedEventManager')
			->setUse('Laminas\EventManager\EventManagerAwareInterface')

			->setUse('Laminas\Paginator\Adapter\DbSelect')
			->setUse('Laminas\Paginator\Paginator')

			->setUse('Laminas\Db\Sql\Select')
			->setUse('Laminas\Db\Sql\Where')

			->setUse('Exception')
			->setUse('Closure')

			->setUse("{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}")

			->setUse('function array_diff_assoc')
			->setUse('function array_filter')
			->setUse('function array_pop')
			->setUse('function count')
			->setUse('function intval')

			->setUse('const false')
			->setUse('const null')
			->setUse('const true');

		// $this->_file->setDeclares([
		//     DeclareStatement::strictTypes(1),
		// ]);
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
				'description' => 'string|array|TableIdentifier'
			]]
		]));
		$this->_class->addPropertyFromGenerator($property);

		// isSequence
		$property = new PropertyGenerator('isSequence');
		$property->setVisibility('private');
		$property->setDefaultValue($this->getNames()->primaryIsSequence);
		$property->setDocBlock(DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => 'boolean is primary key autonumbered'
			]]
		]));
		$this->_class->addPropertyFromGenerator($property);

		// primaryKeyField
		$property = new PropertyGenerator('primaryKeyField');
		$property->setVisibility('private');
		$property->setDefaultValue($this->getNames()->primary);
		$property->setDocBlock(DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => 'string name of primary key'
			]]
		]));
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
				'description' => 'Array default values'
			]]
		]));
		$this->_class->addPropertyFromGenerator($property);

		// events
		$property = new PropertyGenerator('event');
		$property->setVisibility('protected');
		$property->setDefaultValue(null);
		$property->setDocBlock(DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => 'TableGatewayEvent Event'
			]]
		]));
		$this->_class->addPropertyFromGenerator($property);

		$property = new PropertyGenerator('eventManager');
		$property->setVisibility('protected');
		$property->setDefaultValue(null);
		$property->setDocBlock(DocBlockGenerator::fromArray([
			'tags' => [[
				'name' => 'var',
				'description' => 'EventManagerInterface EventManager'
			]]
		]));
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

		$returnTagEntity = new ReturnTag([
			'datatype' => $this->getNames()->entityName
		]);

		$returnEntity = $this->getNames()->namespace_entity . '\\' . $this->getNames()->entityName;

		//===============================================

		//METHODS
		// METHOD:setEventManager
		$method = $this->buildMethod('setEventManager');
		$method->setParameter($parameterEventManager);
		$body = <<<M_BODY
\$eventManager->addIdentifiers([
    self::class,
    @array_pop(explode('\\\', self::class)),
    TableGateway::class,
]);
\$this->event = \$this->event ?: new TableGatewayEvent();
\$this->eventManager = \$eventManager;
return \$this;
M_BODY;
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
		$body = <<<M_BODY
if (!\$this->eventManager instanceof EventManagerInterface) \$this->setEventManager(new EventManager(new SharedEventManager()));
return \$this->eventManager;
M_BODY;
		$method->setBody($body);
		$tag = new ReturnTag();
		$tag->setTypes('\Laminas\EventManager\EventManagerInterface');
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(
			<<<M_BODY
Retrieve the event manager

Lazy-loads an EventManager instance if none registered.
M_BODY
		);
		$docBlock->setTag($tag);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:getPrimaryKeyField
		$method = $this->buildMethod('getPrimaryKeyField');
		$body = <<<M_BODY
return \$this->primaryKeyField;
M_BODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(
			<<<M_BODY
Get PrimaryKey Field
M_BODY
		);
		$docBlock->setTag(new ReturnTag(['string']));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:__construct
		$method = $this->buildMethod('__construct');
		$body = <<<M_BODY
\$this->featureSet = new Feature\FeatureSet();
\$this->featureSet->addFeature(new Feature\GlobalAdapterFeature());
\$this->featureSet->addFeature(new Feature\MetadataFeature());
\$this->featureSet->addFeature(new Feature\EventFeature(\$this->getEventManager()));

\$this->initialize();
\$this->resultSetPrototype = new HydratingResultSet(new ArraySerializableHydrator(), new {$this->getNames()->entityName}(\$this));
M_BODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Create DataTable Object');
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:getEntity
		$method = $this->buildMethod('getEntity');
		$body = <<<M_BODY
return new \\{$this->getNames()->namespace_entity}\\{$this->getNames()->entityName}();
M_BODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(
			<<<M_BODY
Get Empty Entity
M_BODY
		);
		$docBlock->setTag(new ReturnTag(['\\' . $this->getNames()->namespace_entity . "\\{$this->getNames()->entityName}"]));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:getColumnDefaults
		$method = $this->buildMethod('getColumnDefaults');
		$body = <<<M_BODY
return \$this->columnDefaults;
M_BODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription(
			<<<M_BODY
Get Column Default
M_BODY
		);
		$docBlock->setTag(new ReturnTag(['Array']));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:fetchAll
		$method = $this->buildMethod('fetchAll');
		$method->setParameter($parameterPaginator);
		$body = <<<M_BODY
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
M_BODY;
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
		$body = <<<M_BODY
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
M_BODY;
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
		$body = <<<M_BODY
\$select = new Select(\$this->table);
if (\$order) \$select->order(\$order);
if (\$where) \$select->where(\$where);
if (\$limit) \$select->limit(\$limit);

return \$this->selectWith(\$select);
M_BODY;
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
		$method->setReturnType('?' . $returnEntity);
		$body = <<<M_BODY
\$rowset = \$this->select([\$this->getPrimaryKeyField() => \${$this->getNames()->primary}]);
\$row = \$rowset->current();
return \$row;
M_BODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Get by primaryId');
		$docBlock->setTag($paramTagPrimary);
		$docBlock->setTag($returnTagEntity);
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:save
		$method = $this->buildMethod("save{$this->getNames()->entityName}");
		$method->setParameter($parameterEntity);
		$method->setReturnType('void');
		$body = <<<M_BODY
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
		if (\$this->isSequence) \${$this->getNames()->entityVariable}->{$this->getNames()->primary} = intval(\$this->lastInsertValue);
	} else throw new Exception('{$this->getNames()->entityName} {$this->getNames()->primary} error with insert/update');
}
M_BODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Save entity to database');
		$docBlock->setTag(new ParamTag($this->getNames()->entityVariable, $this->getNames()->entityName));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);

		//===============================================

		// METHOD:delete
		$method = $this->buildMethod("delete{$this->getNames()->entityName}");
		$method->setParameter($parameterPrimary);
		$method->setReturnType('void');
		$body = <<<M_BODY
\$this->delete([\$this->getPrimaryKeyField() => \${$this->getNames()->primary}]);
M_BODY;
		$method->setBody($body);
		$docBlock = new DocBlockGenerator('Delete entity');
		$docBlock->setTag(new ParamTag($this->getNames()->primary));
		$method->setDocBlock($docBlock);
		$this->_class->addMethodFromGenerator($method);
	}
}
