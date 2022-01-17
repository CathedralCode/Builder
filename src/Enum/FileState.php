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
 * @copyright 2013-2019 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Builder\Enum;

/**
 * FileState
 *
 * @package Cathedral\Builder
 *
 * @version 1.0.0
 */
enum FileState: int {
    /**
     * No generated file
     */
    case Missing = -1;
    /**
     * Generated file needs to be updated
     */
    case Outdated = 0;
    /**
     * File is correct
     */
    case Ok = 1;
}
