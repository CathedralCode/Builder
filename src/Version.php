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
 * @version $Id: 0.32.2-9-g96a14cc$
 * $Date: Tue Jul 26 22:45:10 2022 +0200$
 */
declare(strict_types=1);

namespace Cathedral\Builder;

/**
 * Version
 * Builders version information
 * @package Cathedral\Builder
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
    const BUILDER_VERSION = '33';

    /**
     * Cathedral Builder version identification for releases<br />
     * 0.18.10
     */
    const VERSION = '0.33.0';

    /**
     * Date of the release<br />
     * 2016 Apr 15
     */
    const VERSION_DATE = '2022 May 31';
}
