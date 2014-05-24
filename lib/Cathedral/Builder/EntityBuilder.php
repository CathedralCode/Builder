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

use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\PropertyGenerator;

/**
 *
 * @author Philip Michael Raab<peep@cathedral.co.za>
 */
class EntityBuilder extends BuilderAbstract implements BuilderInterface {
	
	protected $type = self::TYPE_ENTITY;
	
	protected function setupFile() {
		$this->_file->setNamespace($this->getNames()->namespace_entity);
	}
	
	protected function setupClass() {
		$this->_class->setName($this->getNames()->entityName);
		$this->_class->setExtendedClass($this->getNames()->entityAbstractName);
	
		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Entity for {$this->getNames()->tableName}");
		$this->_class->setDocBlock($docBlock);
		
		$this->_file->setClass($this->_class);
	}
	
	protected function setupMethods() {
		
	}
}
