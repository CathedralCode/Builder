<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * PHP version 7
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
 * Version
 * Builders version information
 * @package Cathedral\Builder\Classes
 * @version 0.1.0
 */
final class Version {
    /**
     * Development
     *
     * @var boolean
     */
    const DEVELOPMENT = false;

    /**
     * Version of the generated class files, this only increments when the generated files change in functionality<br />
     * 18
     *
     * @var string
     */
    const BUILDER_VERSION = '30';

    /**
     * Cathedral Builder version identification for releases<br />
     * 0.18.10
     */
    const VERSION = '0.30.1';

    /**
     * Date of the release<br />
     * 2016 Apr 15
     */
    const VERSION_DATE = '2021 Apr 05';
}
