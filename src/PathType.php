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

namespace Cathedral\Builder;

/**
 * PathType
 *
 * @package Cathedral\Builder
 *
 * @version 1.0.0
 */
enum PathType: int {
    /**
     * Filename without directory
     */
    case Filename = -1;
    /**
     * Directory without filename
     */
    case Directory = 0;
    /**
     * Filename with directory
     */
    case Path = 1;
}
