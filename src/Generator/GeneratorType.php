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
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Builder\Generator;

use const false;
use const true;

/**
 * Enum: Generator File Type
 *
 * @version 1.0.0
 */
enum GeneratorType: string {
    /**
     * Type: Table for DataTable
     */
    case Table = 'DataTable';

    /**
     * Type: AbstractEntity for EntityAbstract
     */
    case AbstractEntity = 'EntityAbstract';

    /**
     * Type: Entity for Entity
     */
    case Entity = 'Entity';

    /**
     * If this file type can be replaced by the generator
     *
     * @return bool
     */
    public function replaceable(): bool {
        return match($this) {
            static::Entity => false,
            default => true,
        };
    }

    /**
     * Warning comment for generated file
     *
     * @return string
     */
    public function fileComment(): string {
        return match ($this->replaceable()) {
            true => 'DO NOT MAKE CHANGES TO THIS FILE',
            false => 'SAFE TO EDIT, BUILDER WILL NEVER OVERWRITE',
        };
    }

    /**
     * Command Shortcut
     *
     * @return string
     */
    public function shortcut(): string {
        return match ($this) {
            static::AbstractEntity => 'a',
            static::Entity => 'e',
            static::Table => 'd',
        };
    }
}

// var_dump(array_column(GeneratorType::cases(), 'value'));
