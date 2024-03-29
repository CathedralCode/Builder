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

use function basename;
use function chmod;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function mkdir;
use function strpos;
use const PHP_EOL;

use Laminas\Code\Generator\{
    ClassGenerator,
    DocBlockGenerator,
    FileGenerator,
    MethodGenerator
};

/**
 * Abstract for builders
 *
 * @package Cathedral\Builder
 *
 * @version 1.0.2
 */
abstract class BuilderAbstract implements BuilderInterface {

    /**
     * Generated code VERSION
     */
    const VERSION = VERSION::BUILDER_VERSION;

    /**
     * File not found
     */
    const FILE_MISSING    = -1;
    /**
     * Files VERSION older than builder VERSION
     */
    const FILE_OUTDATED    = 0;
    /**
     * File OK
     */
    const FILE_MATCH    = 1;

    /**
     * Type DataTable
     */
    const TYPE_MODEL = 'DataTable';
    /**
     * Type EntityAbstract
     */
    const TYPE_ENTITY_ABSTRACT = 'EntityAbstract';
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
        if (!isset($this->type)) {
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
     * Full file name and path
     *
     * @param \Cathedral\Builder\PathType $type which part of the path to return
     *
     * @return string
     */
    protected function getPath(PathType $type = PathType::Path): string {
        $path = match ($this->type) {
            self::TYPE_MODEL => $this->getNames()->modelPath,
            self::TYPE_ENTITY_ABSTRACT => $this->getNames()->entityAbstractPath,
            self::TYPE_ENTITY => $this->getNames()->entityPath,
        };

        return match ($type) {
            PathType::Path => $path,
            PathType::Filename => basename($path),
            PathType::Directory => dirname($path),
        };
    }

    /**
     * Kick off generation process
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
        $warn .= "\n\nPHP version 8";
        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $this->type,
            'longDescription' => "Generated {$this->type}{$warn}",
            'tags' => [
                [
                    'name' => 'package',
                    'description' => $this->getNames()->namespace_entity
                ],
                [
                    'name' => 'author',
                    'description' => 'Philip Michael Raab<peep@inane.co.za>'
                ],
                [
                    'name' => 'VERSION',
                    'description' => self::VERSION
                ]
            ]
        ]);
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

    /**
     * Build Method
     *
     * @param mixed $name
     * @param int $flag
     * @return MethodGenerator
     */
    protected function buildMethod($name, $flag = MethodGenerator::FLAG_PUBLIC): MethodGenerator {
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

        // NOTE: STRICT_TYPES: add strict_types using replace due to official method placing it bellow namespace declaration.
        // return $this->_file->generate();
        return \Inane\String\Str::str_replace("*/\n", "*/\ndeclare(strict_types=1);", $this->_file->generate(), 1);
    }

    /* (non-PHPdoc)
	 * @see \Cathedral\Builder\BuilderInterface::existsFile()
	 */
    /**
     * Check if file exists
     *
     * @return int check result
     */
    public function existsFile(): int {
        $file = $this->getPath();
        if (file_exists($file)) {
            if ($this->type == self::TYPE_ENTITY) return self::FILE_MATCH;

            $data = file_get_contents($file);
            if (strpos($data, "@VERSION " . VERSION::BUILDER_VERSION) !== FALSE) return self::FILE_MATCH;
            return self::FILE_OUTDATED;
        }
        $dir = $this->getPath(PathType::Directory);
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        return self::FILE_MISSING;
    }

    /**
     * Writes code to file.
     *  Overwrite Exception:
     *  Type Entity is never overwritten
     *
     * @param boolean $overwrite
     *
     * @return boolean|null write success returns true, failure returns false, if file exists and overwrite false returns null
     */
    public function writeFile(bool $overwrite = false): ?bool {
        $overwrite = ($this->type == self::TYPE_ENTITY) ? false : $overwrite;
        if (($this->existsFile() < self::FILE_MATCH) || $overwrite) {
            if (@file_put_contents($this->getPath(), $this->getCode(), LOCK_EX)) {
                @chmod($this->getPath(), 0755);
                return true;
            } else return false;
        }
        return null;
    }
}
