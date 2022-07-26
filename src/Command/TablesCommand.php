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
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Builder\Command;

use Cathedral\Builder\Config\BuilderConfigAwareInterface;
use Cathedral\Builder\Enum\FileState;
use Cathedral\Builder\Generator\BuilderManager;
use Cathedral\Builder\Parser\NameManager;
use Inane\Cli\TextTable;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function stripos;
use const false;
use const null;
use const true;

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
final class TablesCommand extends AbstractParamAwareCommand implements BuilderConfigAwareInterface {
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
    public static $defaultName = 'builder:tables';

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
        $this->addParam(
            (new StringParam('filter'))
                ->setDescription('Filter tables containing')
                ->setShortcut('f')
                ->setDefault('')
        );
    }

    /**
     * @param ParamAwareInputInterface $input
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $filter = $input->getParam('filter');
        $output->writeln("Filter: {$filter}");

        $result = $this->renderOutput($filter);
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
     * List tables and related file information
     *
     * @param string $filter show tables containing filter
     *
     * @return string
     */
    public function renderOutput(string $filter) {
        $bm = new BuilderManager($this->getNameManager());

        $st = new TextTable();
        $st->addHeader(['DataTable', 'EntityAbstract', 'Entity', 'Table']);

        while ($bm->nextTable()) if ($filter == '' || stripos($bm->getTableName(), $filter) !== false) $st->addRow([
            $bm->existsDataTable()->name,
            $bm->existsEntityAbstract()->name,
            $bm->existsEntity()->name,
            $bm->getTableName()
        ]);

        $body = $st->render();
        $footer = $this->getDeveloperFooter();

        return <<<TEXT_BODY
Cathedral\Builder: Listing tables
\tshowing the state of the generated file: [Ok, Outdated, Missing]\n
$body
\n$footer
TEXT_BODY;
    }
}
