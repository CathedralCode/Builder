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

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\DocBlockGenerator;

/**
 * Abstract for builders
 *
 * @package Cathedral\Builder\Abstracts
 */
abstract class BuilderAbstract implements BuilderInterface {

	/**
	 * Generated code VERSION
	 */
	const VERSION = VERSION::BUILDER_VERSION;

	/**
	 * File not found
	 */
	const FILE_MISSING	= -1;
	/**
	 * Files VERSION older than builder VERSION
	 */
	const FILE_OUTDATED	= 0;
	/**
	 * File OK
	 */
	const FILE_MATCH	= 1;

	/**
	 * Type DataTable
	 */
	const TYPE_MODEL = 'DataTable';
	/**
	 * Type EntityAbstract
	 */
	const TYPE_ENTITYABSTRACT = 'EntityAbstract';
	/**
	 * Type Entity
	 */
	const TYPE_ENTITY = 'Entity';

	/**
	 * @var int gets set by inheriting classes
	 *  this needs to change,
	 *  in my previous code builder i had a better way...
	 *  but WTF was it???
	 */
	protected $type;

	/**
	 * @var BuilderManager
	 */
	protected $builderManager;

	/**
	 * @var \Laminas\Code\Generator\FileGenerator
	 */
	protected $_file;

	/**
	 * @var \Laminas\Code\Generator\ClassGenerator
	 */
	protected $_class;

	/**
	 * Builder instance
	 *
	 * @param BuilderManager $builderManager
	 * @throws Exception\ConfigurationException
	 */
	public function __construct(BuilderManager &$builderManager) {
		if (! isset($this->type)) {
			throw new Exception\ConfigurationException('A class based on BuilderAbstract has an unset type property');
		}

		$this->builderManager = $builderManager;
		//$this->init();
	}

	/**
	 * Name Manager
	 * @return NameManager
	 */
	protected function getNames(): NameManager {
		return $this->builderManager->getNames();
	}

	/**
	 * Path for file
	 *
	 * @return string
	 */
	protected function getPath(): string {
		switch ($this->type) {
			case self::TYPE_MODEL :
				$path = $this->getNames()->modelPath;
				break;

			case self::TYPE_ENTITYABSTRACT :
				$path = $this->getNames()->entityAbstractPath;
				break;

			case self::TYPE_ENTITY :
				$path = $this->getNames()->entityPath;
				break;

			default :
				break;
		}
		return $path;
	}

	/**
	 * Kick off generation proccess
	 */
	protected function init() {
		$this->_file = new FileGenerator();
		$this->_class = new ClassGenerator();

		$this->setupFile();
		$this->setupFileDocBlock();

		$this->setupClass();
		$this->setupMethods();
	}

	/**
	 * Create file Comments
	 */
	protected function setupFileDocBlock() {
	    $warn = PHP_EOL . "SAFE TO EDIT, BUILDER WILL NEVER OVERWRITE";
	    if (in_array($this->type, ['DataTable', 'EntityAbstract'])) {
	        $warn = PHP_EOL . "DO NOT MAKE CHANGES TO THIS FILE";
	    }
		$docBlock = DocBlockGenerator::fromArray([
			'shortDescription' => $this->type,
			'longDescription' => "Generated {$this->type}{$warn}",
			'tags' => [
				[
					'name' => 'package',
					'description' => $this->getNames()->namespace_entity],
				[
					'name' => 'author',
					'description' => 'Philip Michael Raab<philip@cathedral.co.za>'],
				[
					'name' => 'VERSION',
					'description' => self::VERSION]]]);
		$this->_file->setDocBlock($docBlock);
	}

	/**
	 * Generate the php file code
	 */
	abstract protected function setupFile();

	/**
	 * Generate the class code
	 */
	abstract protected function setupClass();

	/**
	 * Generate the method code
	 */
	abstract protected function setupMethods();

	protected function buildMethod($name, $flag = MethodGenerator::FLAG_PUBLIC) {
		$method = new MethodGenerator();
		$method->setName($name);
		$method->addFlag($flag);
		return $method;
	}

	/* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderInterface::getCode()
	 */
	public function getCode(): string {
		$this->init();
		return $this->_file->generate();
	}

	/* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderInterface::existsFile()
	 */
	public function existsFile() {
		$file = $this->getPath();
		if (file_exists($file)) {
			if ($this->type == self::TYPE_ENTITY) {
				return self::FILE_MATCH;
			}

			$data = file_get_contents($file);
			if (strpos($data, "@VERSION ".VERSION::BUILDER_VERSION) !== FALSE) {
				return self::FILE_MATCH;
			} else {
				return self::FILE_OUTDATED;
			}
		}
		return self::FILE_MISSING;
	}

	/**
	 * Writes code to file.
	 *  Overwrite Exception:
	 *  Type Entity is never overwitten
	 *
	 * @param boolean $overwrite
	 * @return boolean
	 */
	public function writeFile(bool $overwrite = false): bool {
		$overwrite = ($this->type == self::TYPE_ENTITY) ? false : $overwrite;
		if (($this->existsFile() < self::FILE_MATCH) || $overwrite) {
			//$checkPath = dirname($this->getPath());
			if (@file_put_contents($this->getPath(), $this->getCode(), LOCK_EX)) {
				@chmod($this->getPath(), 0666);
				return true;
			}/* else {
				throw new Exception\PermissionException('Write access to Entity OR Model dirs denied');
			}*/
		}
		return false;
	}
}