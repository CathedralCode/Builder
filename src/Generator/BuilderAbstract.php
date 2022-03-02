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
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Builder\Generator;

use function basename;
use function chmod;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use function strpos;
use const false;
use const PHP_EOL;
use const true;

use Cathedral\Builder\{
    Exception\ConfigurationException,
    Parser\NameManager,
    PathType,
    Version
};
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
 * @version 1.1.0
 */
abstract class BuilderAbstract implements BuilderInterface {

    /**
     * Generated code VERSION
     */
    const VERSION = VERSION::BUILDER_VERSION;

    /**
     * @var \Cathedral\Builder\Generator\GeneratorType gets set by inheriting classes
     *  this needs to change,
     *  in my previous code builder i had a better way...
     *  but WTF was it???
     */
    protected GeneratorType $type;

    /**
     * @var BuilderManager
     */
    protected BuilderManager $builderManager;

    /**
     * @var \Laminas\Code\Generator\FileGenerator
     */
    protected \Laminas\Code\Generator\FileGenerator $_file;

    /**
     * @var \Laminas\Code\Generator\ClassGenerator
     */
    protected \Laminas\Code\Generator\ClassGenerator $_class;

    /**
     * Builder instance
     *
     * @param BuilderManager $builderManager
     * @throws \Cathedral\Builder\Exception\ConfigurationException
     */
    public function __construct(BuilderManager &$builderManager) {
        if (!isset($this->type)) throw new ConfigurationException('A class based on BuilderAbstract has an unset type property');

        $this->builderManager = $builderManager;
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
            GeneratorType::Table => $this->getNames()->modelPath,
            GeneratorType::AbstractEntity => $this->getNames()->entityAbstractPath,
            GeneratorType::Entity => $this->getNames()->entityPath,
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
    protected function init(): void {
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
    protected function setupFileDocBlock(): void {
        $warn = "\n{$this->type->fileComment()}\n\nPHP version 8.1";

        $docBlock = DocBlockGenerator::fromArray([
            'shortDescription' => $this->type->value,
            'longDescription' => "Generated {$this->type->value}{$warn}",
            'tags' => [[
                'name' => 'package',
                'description' => $this->getNames()->namespace_entity
            ], [
                'name' => 'author',
                'description' => 'Philip Michael Raab<peep@inane.co.za>'
            ], [
                'name' => 'VERSION',
                'description' => self::VERSION
            ]]
        ]);
        $this->_file->setDocBlock($docBlock);
    }

    /**
     * Generate the php file code
     */
    abstract protected function setupFile(): void;

    /**
     * Generate the class code
     */
    abstract protected function setupClass(): void;

    /**
     * Generate the method code
     */
    abstract protected function setupMethods(): void;

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
     * @return \Cathedral\Builder\Generator\FileStatus check result
     */
    public function getFileStatus(): FileStatus {
        $file = $this->getPath();
        if (file_exists($file)) {
            if (!$this->type->replaceable()) return FileStatus::Ok;

            $data = file_get_contents($file);
            if (strpos($data, "@VERSION " . VERSION::BUILDER_VERSION) !== false) return FileStatus::Ok;
            return FileStatus::Outdated;
        }
        $dir = $this->getPath(PathType::Directory);
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        return FileStatus::Missing;
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
        if (($this->getFileStatus()->lessThan(FileStatus::Ok)) || ($overwrite && $this->type->replaceable())) {
            if (@file_put_contents($this->getPath(), $this->getCode(), LOCK_EX)) {
                @chmod($this->getPath(), 0755);
                return true;
            } else return false;
        }
        return null;
    }
}
