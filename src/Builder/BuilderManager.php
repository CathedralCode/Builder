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

/**
 * Builder manager handles all the heavy lifting
 * 
 * @package Cathedral\Builder\Managers
 * @namespace \Cathedral\Builder
 */
class BuilderManager {
	
	/**
	 * NameManager
	 */
	protected $names;
	
	/**
	 * DataTableBuilder
	 */
	protected $dataTable;
	
	/**
	 * EntityAbstractBuilder
	 */
	protected $entityAbstract;
	
	/**
	 * EntityBuilder
	 */
	protected $entity;

	/**
	 * Create BuilderManager instance
	 * 
	 * @param string|NameManager $namespace
	 * @param string $tableName
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct($namespace = 'Application', $tableName = null) {
		if (is_string($namespace)) {
			$this->names = new NameManager($namespace, $tableName);
		} elseif (is_object($namespace)) {
			if (get_class($namespace) == 'Cathedral\Builder\NameManager') {
				$this->names = $namespace;
			} else {
				throw new Exception\InvalidArgumentException('expects "namespace" to be a string or instance of NameManager');
			}
		} else {
			throw new Exception\InvalidArgumentException('expects "namespace" to be a string or instance of NameManager');
		}
		if ($tableName) {
			$this->names->setTableName($tableName);
		}
	}

	/**
	 * Set NameManager
	 * 
	 * @param NameManager $namemanager
	 */
	public function setNameManager(NameManager $namemanager) {
		$this->names = $namemanager;
		$this->dataTable = null;
		$this->entityAbstract = null;
		$this->entity = null;
	}
	
	// ===============================================
	
	/**
	 * Current table name
	 * 
	 * @return string
	 */
	public function getTableName() {
		return $this->names->getTableName();
	}

	/**
	 * NameManager
	 * 
	 * @return \Cathedral\Builder\NameManager
	 */
	public function getNames() {
		return $this->names;
	}

	/**
	 * Load next table
	 * 
	 * @return boolean
	 */
	public function nextTable() {
		$this->dataTable = null;
		$this->entityAbstract = null;
		$this->entity = null;
		
		return $this->getNames()->nextTable();
	
	}
	
	// ===============================================
	
	/**
	 * Check & Create Path
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	protected function verifyPath($path){
		if (file_exists($path)) return true;
			
		if (mkdir($path, 0777, true)) return true;
				
		return false;
	}
	
	/**
	 * Verify module and try create any missing items
	 *
	 * @return boolean
	 */
	public function verifyModuleStructure(){
		// Check Model Path
		$isValid = $this->verifyPath(dirname($this->getNames()->modelPath));
		
		// Check Entity Path
		$isValid = $isValid && $this->verifyPath(dirname($this->getNames()->entityPath));
		
		//$moduleFile = "{$this->getNames()->modulePath}/Module.php";
		
		return $isValid;
	}
	
	// ===============================================
	
	/**
	 * Create dataTable
	 * 
	 * @return \Cathedral\Builder\DataTableBuilder
	 */
	protected function getDataTable() {
		if (!$this->dataTable) {
			$this->dataTable = new DataTableBuilder($this);
		}
		return $this->dataTable;
	}

	/**
	 * DataTable Code
	 * 
	 * @return string
	 */
	public function getDataTableCode() {
		return $this->getDataTable()->getCode();
	}

	/**
	 * Status of file for dataTable
	 * @return string
	 */
	public function existsDataTable() {
		return $this->getDataTable()->existsFile();
	}

	/**
	 * Write dataTable file
	 * 
	 * @param string $overwrite
	 * @return boolean
	 */
	public function writeDataTable($overwrite = false) {
		return $this->getDataTable()->writeFile($overwrite);
	}
	
	// ===============================================
	
	/**
	 * Create EntityAbstract
	 * 
	 * @return \Cathedral\Builder\EntityAbstractBuilder
	 */
	protected function getEntityAbstract() {
		if (!$this->entityAbstract) {
			$this->entityAbstract = new EntityAbstractBuilder($this);
		}
		return $this->entityAbstract;
	}

	/**
	 * EntityAbstract Code
	 * 
	 * @return string
	 */
	public function getEntityAbstractCode() {
		return $this->getEntityAbstract()->getCode();
	}

	/**
	 * Status of file for EntityAbstract
	 * @return string
	 */
	public function existsEntityAbstract() {
		return $this->getEntityAbstract()->existsFile();
	}

	/**
	 * Write EntityAbstract file
	 * 
	 * @param string $overwrite
	 * @return boolean
	 */
	public function writeEntityAbstract($overwrite = false) {
		return $this->getEntityAbstract()->writeFile($overwrite);
	}
	
	// ===============================================
	
	/**
	 * Create Entity
	 * 
	 * @return \Cathedral\Builder\EntityBuilder
	 */
	protected function getEntity() {
		if (!$this->entity) {
			$this->entity = new EntityBuilder($this);
		}
		return $this->entity;
	}

	/**
	 * Entity Code
	 * 
	 * @return string
	 */
	public function getEntityCode() {
		return $this->getEntity()->getCode();
	}

	/**
	 * Status of file for Entity
	 * 
	 * @return string
	 */
	public function existsEntity() {
		return $this->getEntity()->existsFile();
	}

	/**
	 * Write Entity file
	 * @return boolean
	 */
	public function writeEntity() {
		return $this->getEntity()->writeFile();
	}
}