<?php

/**
 * Cathedral
 *
 * Builder
 *
 * PHP version 8.1
 *
 * @package Cathedral\Builder
 * @author Philip Michael Raab<peep@inane.co.za>
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

namespace Cathedral\Builder;

use Cathedral\Builder\Exception\RuntimeException;
use Stringable;

/**
 * Builder
 *
 * Cathedral Builder: Application
 *
 * @package Cathedral\Builder
 *
 * @version 1.0.0
 */
class Builder implements Stringable {
    /**
     * Builder constructor
     *
     * @param string $title
     * @param string $body
     */
    public function __construct(
        /**
         * Example Title
         *
         * @var string
         */
        protected string $title,
        /**
         * Example body
         *
         * @var string
         */
        protected string $body = '',
    ) {
        throw new RuntimeException('Stub');
    }

    /**
     * Builder: EOL
     */
    public function __destruct() {
    }

    /**
     * Builder String
     *
     * @return string Builder
     */
    public function __toString(): string {
        return "{$this->title}\n\n{$this->body}";
    }
}
