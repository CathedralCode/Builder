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

namespace Cathedral\Builder\Controller;

use Laminas\Console\Request;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\Controller\AbstractActionController;

use function in_array;
use function str_contains;
use function strtolower;

use Cathedral\Builder\{
    Cli\TextTable,
    Config\BuilderConfigAwareInterface,
    Enum\FileState,
    BuilderManager,
    NameManager
};

/**
 * BuilderCLIController
 *
 * CLI UI for Builder
 *
 * @package Cathedral\Builder
 *
 * @version 1.0.4
 */
class BuilderCLIController extends AbstractActionController implements BuilderConfigAwareInterface {

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

    /**
     * {@inheritDoc}
     * @see \Cathedral\Config\ConfigAwareInterface::setConfig()
     */
    public function setBuilderConfig(array $config): void {
        $this->config = $config;
        if (in_array($this->config['namespace'], $this->config['modules']))
            $this->_namespace = $this->config['namespace'];

        if ($this->config['entity_singular'])
            $this->entitySingular = $this->config['entity_singular'];

        if (!isset($this->entitySingular))
            $this->singularIgnore = $this->config['singular_ignore'];
    }

    public function setEventManager(EventManagerInterface $events) {
        parent::setEventManager($events);
    }

    /**
     * Creates and returns a NameManager
     *
     * @return \Cathedral\Builder\NameManager
     */
    private function getNameManager($reset = false): NameManager {
        if ($reset)
            $this->_nameManager = null;

        if (!$this->_nameManager) {
            $nm = new NameManager($this->_namespace);
            if (!$this->entitySingular)
                $nm->entitySingular(false);
            else
                $nm->setEntitySingularIgnores($this->singularIgnore);
            $this->_nameManager = $nm;
        }
        return $this->_nameManager;
    }

    private function getConsoleRequest() {
        $request = $this->getRequest();
        if (!$request instanceof Request) {
            throw new \RuntimeException('You can only use this action from a console!');
        }
        return $request;
    }

    /**
     * Returns developer mode string if dev mode true
     *
     * @return string
     */
    private function getDeveloperFooter() {
        return (\Cathedral\Builder\Version::DEVELOPMENT) ? "\nDevelopment Mode" : '';
    }

    /**
     * List tables and status of class files
     *
     * @return string
     */
    public function tableListAction() {
        $this->getConsoleRequest();

        $status = [
            -1 => 'None',
            0 => 'Outdated',
            1 => 'Ok'
        ];
        $bm = new BuilderManager($this->getNameManager());

        $body = '';
        while ($bm->nextTable()) {
            $body .= $bm->getTableName() . "\n";
            $body .= "\tDataTable     :" . $status[$bm->existsDataTable()] . "\n";
            $body .= "\tEntityAbstract:" . $status[$bm->existsEntityAbstract()] . "\n";
            $body .= "\tEntity        :" . $status[$bm->existsEntity()] . "\n";
        }
        $footer = $this->getDeveloperFooter();
        $response = <<<MBODY
Listing of tables
$body
$footer
MBODY;
        return "$response\n";
    }

    /**
     * List tables and related file information
     *
     * @return string
     */
    public function tablesAction() {
        $request = $this->getConsoleRequest();

        $filter = strtolower($request->getParam('filter'));

        $bm = new BuilderManager($this->getNameManager());

        $st = new TextTable();
        $st->setRowDefinition([10, 15, 10, 30]);
        $st->addRow(['DataTable', 'EntityAbstract', 'Entity', 'Table']);

        while ($bm->nextTable()) if ($filter == '' || str_contains(strtolower($bm->getTableName()), $filter)) $st->addRow([
            FileState::from($bm->existsDataTable())->name,
            FileState::from($bm->existsEntityAbstract())->name,
            FileState::from($bm->existsEntity())->name,
            $bm->getTableName()
        ]);

        $body = $st->buildTextTable();

        $footer = $this->getDeveloperFooter();
        $response = <<<TEXT_BODY
Cathedral\Builder: Listing tables
\tshowing the state of the generated file: [Ok, Outdated, Missing]\n
$body
\n$footer
TEXT_BODY;
        return "$response\n";
    }

    /**
     * Generate the classes
     *
     * @return string the code or status if -w
     */
    public function buildAction() {
        $request = $this->getConsoleRequest();

        $types = [
            'datatable' => [
                'DataTable'
            ],
            'abstract' => [
                'EntityAbstract'
            ],
            'entity' => [
                'Entity'
            ],
            'ALL' => [
                'DataTable',
                'EntityAbstract',
                'Entity'
            ]
        ];

        $class = $request->getParam('class', 'ALL') === false ? 'ALL' : $request->getParam('class', 'ALL');
        $table = $request->getParam('table', 'ALL') === false ? 'ALL' : $request->getParam('table', 'ALL');
        $write = $request->getParam('write') || $request->getParam('w');

        $body = '';
        $classes = $types[$class];

        $tables = $this->getNameManager()->getTableNames();
        if (in_array($table, $tables)) $tables = [$table];
        else if ($table !== 'ALL') return $body . "\nInvalid Table: $table";

        foreach ($classes as $type) {
            $getFunc = "get{$type}Code";
            $writeFunc = "write{$type}";

            echo "Generating $type\n";
            $extra =  $type == 'Entity' ? ' (Entity is NEVER Overridden)' : '';
            $body .= "Generating $type for {$table}{$extra}\n";

            foreach ($tables as $t) {
                echo "For Table: $t\n";
                $bm = new BuilderManager($this->getNameManager(true), $t);
                $code = $bm->$getFunc();

                if (!$write) $body .= $code;
                else $body .= match ($bm->$writeFunc(true)) {
                    true => "\tWritten {$t} to file\n",
                    false => "\tFAILED writing {$t} to file\n",
                    null => "\tSKIPPED writing {$t} to file\n",
                    default => "\tUNKNOWN response writing {$t} to file\n"
                };
            }

            $body .= $this->getDeveloperFooter();
        }

        return "$body\n";
    }
}
