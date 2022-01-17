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
declare(strict_types=1);

namespace Cathedral\Builder;

use Laminas\Code\{
	Generator\DocBlockGenerator,
	DeclareStatement
};

/**
 * Builds the Entity
 *
 * @package Cathedral\Builder\Builders
 *
 * @version 1.0.0
 */
class EntityBuilder extends BuilderAbstract {

	protected $type = self::TYPE_ENTITY;

	/**
	 * Generate the php file code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupFile()
	 */
	protected function setupFile() {
		$this->_file->setNamespace($this->getNames()->namespace_entity);
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
		$this->_class->setName($this->getNames()->entityName);
		$this->_class->setExtendedClass($this->getNames()->entityAbstractName);

		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Entity for {$this->getNames()->tableName}");
		$this->_class->setDocBlock($docBlock);

		$this->_file->setClass($this->_class);
	}

	/**
	 * Generate the method code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupMethods()
	 */
	protected function setupMethods() {

	}
}
