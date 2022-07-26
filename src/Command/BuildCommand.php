<?php

/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP version 8.1
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Builder\Command;

use Cathedral\Builder\Config\BuilderConfigAwareInterface;
use Cathedral\Builder\Parser\NameManager;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function count;
use function in_array;
use function stripos;
use function strtolower;
use const false;
use const null;
use const true;

use Cathedral\Builder\Generator\{
    BuilderManager,
    GeneratorType
};
use Laminas\Cli\Input\{
    ParamAwareInputInterface,
    StringParam
};

/**
 * TablesCommand
 *
 * @version 1.1.0
 *
 * @package Cathedral\Builder
 */
final class BuildCommand extends AbstractParamAwareCommand implements BuilderConfigAwareInterface {
    /**
     * Base Namespace for created data layer
     * @var string
     */
    private string $_namespace = 'Application';

    /**
     * Attempt to use singular form of table name for entity
     * @var bool
     */
    private bool $entitySingular = true;

    /**
     * List of tables not to singularise table name for entity
     * @var array
     */
    private array $singularIgnore = [];

    /**
     * Name Manager
     *
     * @var null|\Cathedral\Builder\Parser\NameManager
     */
    private ?NameManager $_nameManager = null;

    /**
     * Configuration options
     *
     * @var array
     */
    protected array $config;

    /** @var string */
    public static $defaultName = 'builder:build';

    /**
     * {@inheritDoc}
     * @see \Cathedral\Config\ConfigAwareInterface::setConfig()
     */
    public function setBuilderConfig(array $config): void {
        $this->config = $config;
        $this->_namespace = $this->config['namespace'];

        if ($this->config['entity_singular'])
            $this->entitySingular = $this->config['entity_singular'];

        if (!isset($this->entitySingular))
            $this->singularIgnore = $this->config['singular_ignore'];
    }

    /**
     * Creates and returns a NameManager
     *
     * @param bool $reset create a new NameManager
     *
     * @return \Cathedral\Builder\Parser\NameManager
     */
    private function getNameManager(bool $reset = false): NameManager {
        if ($reset) $this->_nameManager = null;

        if (!$this->_nameManager) {
            $this->_nameManager = new NameManager($this->_namespace);
            if (!$this->entitySingular) $this->_nameManager->entitySingular(false);
            else $this->_nameManager->setEntitySingularIgnores($this->singularIgnore);
        }

        return $this->_nameManager;
    }

    /**
     * Configure Command
     *
     * @return void
     */
    protected function configure(): void {
        $this->setName(self::$defaultName);

        $types = GeneratorType::cases();
        for ($i=0; $i < count($types); $i++) {
            $this->addParam(
                (new StringParam($types[$i]->name))
                ->setDescription("Generate code for {$types[$i]->value}: Y/n?")
                ->setShortcut($types[$i]->shortcut())
                ->setDefault('Y')
            );
        }
        $this->addParam(
            (new StringParam('filter'))
                ->setDescription('Filter tables containing')
                ->setShortcut('f')
                ->setDefault('')
        );
        $this->addParam(
            (new StringParam('write'))
                ->setDescription('Write tables to file: Y/n?')
                ->setShortcut('w')
                ->setDefault('Y')
        );
    }

    /**
     * @param ParamAwareInputInterface $input
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $classes = array_filter(GeneratorType::cases(), fn ($t) => in_array(strtolower($input->getParam($t->name)), ['y','yes', 'true', '1']));
        $filter = $input->getParam('filter');
        $write = in_array(strtolower($input->getParam('write')), ['y','yes', 'true', '1']);

        $result = $this->buildAction($classes, $filter, $write, $output);
        $output->writeln($result);

        return 0;
    }

    /**
     * Generate the classes
     *
     * @param GeneratorType[] $classes
     *
     * @return string the code
     */
    protected function buildAction(array $classes, string $filter, bool$write, OutputInterface $output) {
        $tables = $this->getNameManager()->getTableNames();

        foreach ($classes as $type) {
            $body = "Generating {$type->value}";
            if (!$type->replaceable()) $body .= ' (Entity is NEVER Overridden)';
            $output->writeln($body);

            foreach ($tables as $t) $output->writeln($this->buildTable($t, $type->value, $filter, $write));
        }

        return "\n...Done!";
    }

    /**
     * Generate the classes for a table
     *
     * @return string the code
     */
    protected function buildTable(string $table, string $type, string $filter, bool$write) {
        if ($filter == '' || stripos($table, $filter) !== false) {
            $bm = new BuilderManager($this->getNameManager(true), $table);

            if (!$write) return $bm->{"get{$type}Code"}();
            else return match ($bm->{"write{$type}"}(true)) {
                true => "\tWritten => {$table}",
                false => "\tFAILED => {$table}",
                null => "\tSKIPPED => {$table}",
                default => "\tUNKNOWN => {$table}",
            };
        }
    }
}
