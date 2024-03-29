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

use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

use Cathedral\Builder\{
    Config\BuilderConfigAwareInterface,
    BuilderManager,
    NameManager
};

/**
 * BuilderWebController
 *
 * Web UI for Builder
 *
 * @package Cathedral\Builder\Controller\Web
 * 
 * @version 1.0.0
 */
class BuilderWebController extends AbstractActionController implements BuilderConfigAwareInterface {

    private $dataNamespace = 'Application';
    private $entitySingular = true;
    private array $singularIgnore;

    private $_nameManager = null;

    /**
     * Builders autoload config settings
     *
     * @var string
     */
    protected array $config;

    /**
     * {@inheritDoc}
     * @see \Cathedral\Config\ConfigAwareInterface::setConfig()
     */
    public function setBuilderConfig(array $config) {
        $this->config = $config;

        if (in_array($this->config['namespace'], $this->config['modules']))
            $this->dataNamespace = $this->config['namespace'];

        if ($this->config['entity_singular'])
            $this->entitySingular = $this->config['entity_singular'];

        if (!isset($this->entitySingular))
            $this->singularIgnore = $this->config['singular_ignore'];
    }

    /**
     * {@inheritDoc}
     * @see \Laminas\Mvc\Controller\AbstractController::setEventManager()
     */
    public function setEventManager(EventManagerInterface $events) {
        parent::setEventManager($events);
        $controller = $this;
        $events->attach('dispatch', function ($e) use ($controller) {
            $controller->layout('layout/cathedral/builder');
        }, 100);
    }

    /**
     * Creates and returns a NameManager
     *
     * @return \Cathedral\Builder\NameManager
     */
    private function getNameManager() {
        if (!$this->_nameManager) {
            $nm = new NameManager($this->dataNamespace);
            if (!$this->entitySingular) $nm->entitySingular(false);
            else if (isset($this->singularIgnore)) $nm->setEntitySingularIgnores($this->singularIgnore);
            $this->_nameManager = $nm;
        }
        return $this->_nameManager;
    }

    public function indexAction() {
        $bm = new BuilderManager($this->getNameManager());

        return new ViewModel([
            'title' => 'Overview',
            'builderManager' => $bm,
            'namespace' => $this->dataNamespace
        ]);
    }

    public function buildAction() {
        $types = [
            'DataTable',
            'EntityAbstract',
            'Entity'
        ];

        $table = $this->params()->fromRoute('table', null);
        $typeIndex = $this->params()->fromRoute('type', null);
        $write = (bool) $this->params()->fromRoute('write', false);

        $type = $types[$typeIndex];
        $getFunc = "get{$type}Code";
        $writeFunc = "write{$type}";

        $saved = '';

        if ($table == '0') {
            $code = '';
            $bm = new BuilderManager($this->getNameManager());
            $bm->verifyModuleStructure();

            while ($bm->nextTable()) {
                $code .= "{$bm->getTableName()}... ";
                if ($bm->$writeFunc(true)) $code .= "Saved\n";
                else $code .= "Failed\n";
            }
            $table = 'Tables';
        } else {
            $bm = new BuilderManager($this->getNameManager(), $table);
            $bm->verifyModuleStructure();
            $code = $bm->$getFunc();

            if ($write) {
                if ($bm->$writeFunc(true)) $saved = 'Saved';
                else $saved = "Error saving file";
            }
        }

        return new ViewModel([
            'title' => 'Code View',
            'table' => $table,
            'saved' => $saved,
            'type' => $type,
            'code' => $code,
            'namespace' => $this->dataNamespace
        ]);
    }
}
