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

use Cathedral\Builder\{
    Config\BuilderConfigAwareInterface,
    BuilderManager,
    NameManager
};

/**
 * BuilderCLIController
 *
 * CLI UI for Builder
 *
 * @package Cathedral\Builder\Controller\CLI
 *
 * @version 1.0.2
 */
class BuilderCLIController extends AbstractActionController implements BuilderConfigAwareInterface {

    private string $dataNamespace = 'Application';
    private bool $entitySingular = true;
    private array $singularIgnore = [];

    private ?NameManager $_nameManager = null;

    protected array $config;

    /**
     * {@inheritDoc}
     * @see \Cathedral\Config\ConfigAwareInterface::setConfig()
     */
    public function setBuilderConfig(array $config): void {
        $this->config = $config;
        if (in_array($this->config['namespace'], $this->config['modules']))
            $this->dataNamespace = $this->config['namespace'];

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
            $nm = new NameManager($this->dataNamespace);
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
            $body .= "Generating $type for $table\n";

            foreach ($tables as $t) {
                echo "For Table: $t\n";
                $bm = new BuilderManager($this->getNameManager(true), $t);
                $code = $bm->$getFunc();

                if (!$write) $body .= $code;
                else if ($bm->$writeFunc(true)) $body .= "\tWritten {$t} to file\n";
                else $body .= "\tFAILED writing {$t} to file\n";
            }

            $body .= $this->getDeveloperFooter();
        }

        return "$body\n";
    }
}
