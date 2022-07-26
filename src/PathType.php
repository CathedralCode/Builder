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
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @version $Id: 0.32.2-9-g96a14cc$
 * $Date: Tue Jul 26 22:45:10 2022 +0200$
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
