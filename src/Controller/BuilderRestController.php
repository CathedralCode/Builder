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

use Cathedral\Builder\Config\BuilderConfigAwareInterface;
use Cathedral\Builder\Parser\NameManager;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;

/**
 * BuilderRestController
 *
 * Restful access to tables
 *
 * @package Cathedral\Builder\Controller\Rest
 *
 * @version 1.0.0
 */
class BuilderRestController extends AbstractRestfulController implements BuilderConfigAwareInterface {

    protected $_dataTable = null;
    protected $_entity = null;

    private $dataNamespace = 'Application';
    private $entitySingular = true;
    private array $singularIgnore;

    private $_nameManager = null;

    protected array $config;

    /**
     * {@inheritDoc}
     * @see \Cathedral\Config\ConfigAwareInterface::setConfig()
     */
    public function setBuilderConfig(array $config) {
        $this->config = $config;
    }

    /**
     * Create JSON response
     *
     * @param string|array $data
     * @param number $code
     * @param string $message
     * @return \Laminas\View\Model\JsonModel
     */
    private function createResponse($data = null, $code = 0, $message = "") {
        return new JsonModel([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Creates and returns a NameManager
     *
     * @return \Cathedral\Builder\Parser\NameManager
     */
    private function getNameManager(): NameManager {
        if (!$this->_nameManager) {
            if (in_array($this->config['namespace'], $this->config['modules']))
                $this->dataNamespace = $this->config['namespace'];

            if ($this->config['entity_singular'])
                $this->entitySingular = $this->config['entity_singular'];

            if (!isset($this->entitySingular))
                $this->singularIgnore = $this->config['singular_ignore'];

            $nm = new NameManager($this->dataNamespace, $this->params('table'));
            if (!$this->entitySingular) $nm->entitySingular(false);
            else $nm->setEntitySingularIgnores($this->singularIgnore);

            $this->_nameManager = $nm;
        }
        return $this->_nameManager;
    }

    /**
     * Creates and returns a DataTable or null if invalid
     *
     * @return mixed
     */
    protected function getDataTable() {
        if (!$this->_dataTable) if (in_array($this->params('table'), $this->getNameManager()->getTableNames())) {
            $DataTable = "\\{$this->getNameManager()->namespace_model}\\{$this->getNameManager()->modelName}";
            $this->_dataTable = new $DataTable();
        }
        return $this->_dataTable;
    }

    /**
     * Creates and returns an Entity or null if invalid
     *
     * @return mixed
     */
    protected function getEntity() {
        if (!$this->_entity) if ($this->getDataTable()) $this->_entity = $this->getDataTable()->getEntity();

        return $this->_entity;
    }

    /**
     * Return list of resources
     *
     * @return mixed
     */
    public function getList() {
        $dt = $this->getDataTable();

        if (!$dt)
            return $this->createResponse($this->getNameManager()->getTableNames(), 401, "Tables");

        $es = $dt->fetchAll();
        $data = [];
        foreach ($es as $e) $data[] = [
            $this->getNameManager()->primary => $e->{$this->getNameManager()->primary}
        ];

        return $this->createResponse($data, 0, "{$this->getNameManager()->modelName} List");
    }

    /**
     * Return single resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function get($id) {
        $e = $this->getEntity();

        if (!$e)
            return $this->createResponse($this->getNameManager()->getTableNames(), 401, "Tables");

        if (!$e->get($id))
            return $this->createResponse($id, 401, "{$this->getNameManager()->entityName} not found");

        return $this->createResponse($e->getArrayCopy(), 0, $this->getNameManager()->entityName);
    }

    /**
     * Create a new resource
     *
     * @param  mixed $data
     * @return mixed
     */
    public function create($data) {
        $dt = $this->getDataTable();

        if (!$dt)
            return $this->createResponse($this->getNameManager()->getTableNames(), 401, "Tables");

        return $this->createResponse([], 200, "{$this->getNameManager()->entityName}: No Create");
    }

    /**
     * Delete an existing resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function delete($id) {
        $dt = $this->getDataTable();

        if (!$dt)
            return $this->createResponse($this->getNameManager()->getTableNames(), 401, "Tables");

        return $this->createResponse([], 200, "{$this->getNameManager()->entityName}: No Delete");
    }

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    public function update($id, $data) {
        $dt = $this->getDataTable();

        if (!$dt)
            return $this->createResponse($this->getNameManager()->getTableNames(), 401, "Tables");

        return $this->createResponse([], 200, "{$this->getNameManager()->entityName}: No Update");
    }
}
