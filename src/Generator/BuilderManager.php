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
 *
 * @version $Id: 0.32.2-9-g96a14cc$
 * $Date: Tue Jul 26 22:45:10 2022 +0200$
 */

declare(strict_types=1);

namespace Cathedral\Builder\Generator;

use Cathedral\Builder\Exception\InvalidArgumentException;
use Cathedral\Builder\Parser\NameManager;

use function dirname;
use function file_exists;
use function is_null;
use function is_string;
use function mkdir;
use const null;

/**
 * Builder manager handles all the heavy lifting
 *
 * @package Cathedral\Builder
 *
 * @version 1.1.0
 */
class BuilderManager {
    /**
     * Builder class types
     *
     * @var array
     */
    public static array $types = [
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

    /**
     * NameManager
     */
    protected NameManager $names;

    /**
     * DataTableBuilder
     */
    protected ?DataTableBuilder $dataTable;

    /**
     * EntityAbstractBuilder
     */
    protected ?EntityAbstractBuilder $entityAbstract;

    /**
     * EntityBuilder
     */
    protected ?EntityBuilder $entity;

    /**
     * Create BuilderManager instance
     *
     * @param string|NameManager $namespace
     * @param null|string $tableName
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string|NameManager $namespace = 'Application', ?string $tableName = null) {
        if (is_string($namespace)) $namespace = new NameManager($namespace, $tableName);
        $this->names = $namespace;

        if (!is_null($tableName)) $this->names->setTableName($tableName);
    }


    /**
     * Set NameManager
     *
     * @param NameManager $nameManager
     *
     * @return void
     */
    public function setNameManager(NameManager $nameManager): void {
        $this->names = $nameManager;
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
    public function getTableName(): string {
        return $this->names->getTableName();
    }

    /**
     * NameManager
     *
     * @return NameManager
     */
    public function getNames(): NameManager {
        return $this->names;
    }

    /**
     * Load next table
     *
     * @return boolean
     */
    public function nextTable(): bool {
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
    protected function verifyPath(string $path): bool {
        if (file_exists($path)) return true;

        if (mkdir($path, 0777, true)) return true;

        return false;
    }

    /**
     * Verify module and try create any missing items
     *
     * @return boolean
     */
    public function verifyModuleStructure(): bool {
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
     * @return DataTableBuilder
     */
    protected function getDataTable(): DataTableBuilder {
        if (!isset($this->dataTable)) $this->dataTable = new DataTableBuilder($this);
        return $this->dataTable;
    }

    /**
     * DataTable Code
     *
     * @return string
     */
    public function getDataTableCode(): string {
        return $this->getDataTable()->getCode();
    }

    /**
     * Status of file for dataTable
     *
     * @return FileStatus
     */
    public function existsDataTable(): FileStatus {
        return $this->getDataTable()->getFileStatus();
    }

    /**
     * Write dataTable file
     *
     * @param bool $overwrite
     *
     * @return boolean
     */
    public function writeDataTable(bool $overwrite = false): bool {
        return $this->getDataTable()->writeFile($overwrite);
    }

    // ===============================================

    /**
     * Create EntityAbstract
     *
     * @return EntityAbstractBuilder
     */
    protected function getEntityAbstract(): EntityAbstractBuilder {
        if (!isset($this->entityAbstract)) $this->entityAbstract = new EntityAbstractBuilder($this);
        return $this->entityAbstract;
    }

    /**
     * EntityAbstract Code
     *
     * @return string
     */
    public function getEntityAbstractCode(): string {
        return $this->getEntityAbstract()->getCode();
    }

    /**
     * Status of file for EntityAbstract
     *
     * @return FileStatus
     */
    public function existsEntityAbstract(): FileStatus {
        return $this->getEntityAbstract()->getFileStatus();
    }

    /**
     * Write EntityAbstract file
     *
     * @param bool $overwrite
     *
     * @return boolean
     */
    public function writeEntityAbstract(bool $overwrite = false): bool {
        return $this->getEntityAbstract()->writeFile($overwrite);
    }

    // ===============================================

    /**
     * Create Entity
     *
     * @return EntityBuilder
     */
    protected function getEntity(): EntityBuilder {
        if (!isset($this->entity)) $this->entity = new EntityBuilder($this);
        return $this->entity;
    }

    /**
     * Entity Code
     *
     * @return string
     */
    public function getEntityCode(): string {
        return $this->getEntity()->getCode();
    }

    /**
     * Status of file for Entity
     *
     * @return FileStatus
     */
    public function existsEntity(): FileStatus {
        return $this->getEntity()->getFileStatus();
    }

    /**
     * Write Entity file
     *
     * @return boolean|null write success returns true, failure returns false, if file exists and overwrite false returns null
     */
    public function writeEntity(): ?bool {
        return $this->getEntity()->writeFile();
    }
}
