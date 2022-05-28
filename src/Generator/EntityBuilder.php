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

namespace Cathedral\Builder\Generator;

use Laminas\Code\Generator\DocBlockGenerator;

/**
 * Builds the Entity
 *
 * @package Cathedral\Builder\Builders
 *
 * @version 1.1.0
 */
class EntityBuilder extends BuilderAbstract {

	/**
	 * Generator Type
	 *
     * @var \Cathedral\Builder\Generator\GeneratorType GeneratorType
     */
	protected GeneratorType $type = GeneratorType::Entity;

	/**
	 * Generate the php file code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupFile()
	 */
	protected function setupFile(): void {
		// NOTE: STRICT_TYPES: see BuilderAbstract->getCode(): add strict_types using replace due to official method placing it bellow namespace declaration.
		// $this->fileGenerator()->setDeclares([
		//     DeclareStatement::strictTypes(1),
		// ]);

		$this->fileGenerator()->setNamespace($this->getNames()->namespace_entity);
	}

	/**
	 * Generate the class code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupClass()
	 */
	protected function setupClass(): void {
		$this->ClassGenerator()->setName($this->getNames()->entityName);
		$this->ClassGenerator()->setExtendedClass($this->getNames()->entityAbstractName);

		$docBlock = new DocBlockGenerator();
		$docBlock->setShortDescription("Entity for {$this->getNames()->tableName}");
		$this->ClassGenerator()->setDocBlock($docBlock);

		$this->fileGenerator()->setClass($this->ClassGenerator());
	}

	/**
	 * Generate the method code
	 *
	 * @see \Cathedral\Builder\BuilderAbstract::setupMethods()
	 */
	protected function setupMethods(): void {
	}
}
