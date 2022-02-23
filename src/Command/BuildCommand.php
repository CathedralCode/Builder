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

namespace Cathedral\Builder\Command;

use Laminas\Cli\Command\AbstractParamAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function implode;
use function in_array;
use function stripos;
use const false;
use const null;
use const true;

use Cathedral\Builder\{
    Config\BuilderConfigAwareInterface,
    BuilderManager,
    NameManager
};
use Laminas\Cli\Input\{
    ParamAwareInputInterface,
    StringParam
};

/**
 * TablesCommand
 *
 * @version 1.0.0
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
     * @var null|\Cathedral\Builder\NameManager
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
     * @return \Cathedral\Builder\NameManager
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
        $this->addParam(
            (new StringParam('class'))
                ->setDescription('Create classes: ' . implode(array_keys(BuilderManager::$types)))
                ->setShortcut('c')
                ->setDefault('ALL')
        );
        $this->addParam(
            (new StringParam('filter'))
            ->setDescription('Filter tables containing')
            ->setShortcut('f')
                ->setDefault('')
        );
        $this->addParam(
            (new StringParam('write'))
                ->setDescription('Write tables to file?')
                ->setShortcut('w')
                ->setDefault('n')
        );
    }

    /**
     * @param ParamAwareInputInterface $input
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $class = $input->getParam('class');
        if (!in_array($class, array_keys(BuilderManager::$types))) $class = 'ALL';

        $filter = $input->getParam('filter');
        $write = $input->getParam('write') == 'y' ? true : false;

        $result = $this->buildAction($class, $filter, $write);
        $output->writeln($result);

        return 0;
    }

    /**
     * Returns developer mode string if dev mode true
     *
     * @return string
     */
    private function getDeveloperFooter(): string {
        return (\Cathedral\Builder\Version::DEVELOPMENT) ? "\nDevelopment Mode" : '';
    }

    /**
     * Generate the classes
     *
     * @return string the code
     */
    protected function buildAction(string $class, string $filter, bool $write) {
        $body = '';
        $classes = BuilderManager::$types[$class];
        $tables = $this->getNameManager()->getTableNames();

        foreach ($classes as $type) {
            echo "Generating $type\n";
            $extra =  $type == 'Entity' ? ' (Entity is NEVER Overridden)' : '';
            $body .= "Generating $type{$extra}\n";

            foreach ($tables as $t) $body .= $this->buildTable($t, $type, $filter, $write);

            $body .= $this->getDeveloperFooter();
        }

        return "$body\n";
    }

    /**
     * Generate the classes for a table
     *
     * @return string the code
     */
    protected function buildTable(string $table, string $type, string $filter, bool $write) {
        if ($filter == '' || stripos($table, $filter) !== false) {
            echo "For Table: $table\n";
            $bm = new BuilderManager($this->getNameManager(true), $table);

            if (!$write) return $bm->{"get{$type}Code"}();
            else return match ($bm->{"write{$type}"}(true)) {
                true => "\tWritten {$table} to file\n",
                false => "\tFAILED writing {$table} to file\n",
                null => "\tSKIPPED writing {$table} to file\n",
                default => "\tUNKNOWN response writing {$table} to file\n"
            };
        }
    }
}
