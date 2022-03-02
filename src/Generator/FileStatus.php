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
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Builder\Generator;

/**
 * Enum: Generated File Status
 *
 * @version 1.0.0
 */
enum FileStatus: int {
    case Ok = 1;
    case Outdated = 0;
    case Missing = -1;

    /**
     * Checks if current status is lower than $status
     *
     * @param self $status for comparison
     *
     * @return bool is lower than
     */
    public function lessThan(self $status): bool {
        return $this->value < $status->value;
    }
}
