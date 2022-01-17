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

declare(strict_types=1);

namespace Cathedral\Builder\Cli;

use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_shift;
use function array_unshift;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_integer;
use function str_pad;

/**
 * Text Table
 *
 * Builds a passable console table from arrays
 *
 * options: [
 *      'row' => [
 *
 *      ],
 *      'col' => [
 *          'divider' => ' ',
 *      ],
 * ]
 *
 * @version 1.0.0
 *
 * @package Cathedral\Builder
 */
class TextTable {
    /**
     * Config defaults
     *
     * row->header:
     *  - null: no header
     *  - char: repeated as header divider string
     */
    protected array $defaults = [
        'row' => [
            'header' => '-',
            'definition' => [5, 20],
            'divider' => "\n",
        ],
        'col' => [
            'divider' => ' | ',
        ],
    ];

    protected array $config;

    // protected array $rowDefinition = [5, 20];
    protected array $rows = [];

    protected string $tableText = '';

    public function __construct(array $options = []) {
        $this->reset($options);
    }

    /**
     * Set row definition
     *
     * @param array $definition row details
     *
     * @return bool success
     */
    public function setRowDefinition(array $definition): bool {
        if (!array_is_list($definition)) return false;
        for ($i = 0; $i < count($definition); $i++) if (!is_integer($definition[$i])) return false;

        // $this->rowDefinition = $definition;
        $this->config['row']['definition'] = $definition;

        return true;
    }

    protected function getRowDefinition(): array {
        return $this->config['row']['definition'];
    }

    public function reset(array $options = []) {
        $this->config = static::applyDefaults($options, $this->defaults);

        $this->rows = [];
        $this->tableText = '';
    }

    protected function matchesDefinition(array $row): bool {
        return count($this->getRowDefinition()) == count($row);
    }

    public function addRow(array $row) {
        if ($this->matchesDefinition($row)) $this->rows[] = $row;
    }

    public function buildTextTable(): string {
        $rows = [];

        foreach ($this->rows as $r) {
            $cols = [];

            for ($i = 0; $i < count($this->config['row']['definition']); $i++) $cols[] = str_pad($r[$i], $this->config['row']['definition'][$i]);

            $rows[] = implode($this->config['col']['divider'], $cols);
        }

        if ($this->config['row']['header'] !== null) {
            $c = [];
            for ($i = 0; $i < count($this->config['row']['definition']); $i++) $c[] = str_pad('', $this->config['row']['definition'][$i], $this->config['row']['header']);
            $d = implode($this->config['col']['divider'], $c);

            $h = array_shift($rows);
            array_unshift($rows, $h, $d);
        }

        return implode($this->config['row']['divider'], $rows);
    }

    /**
     * Complete missing keys
     *
     * The middle ground between `array_merge` and `array_merge_recursive`
     *
     * Merges arrays **without** replacing existing values only **adding** keys.<br/>
     * > Priority decreases from first (highest) to last (lowest)
     *
     * @since 0.3.0
     *
     * @param array ...$arrays to merge with decreeing priority left to right
     *
     * @return array completed array
     */
    protected static function applyDefaults(array ...$arrays): array {
        $arrays = array_filter($arrays, fn ($a) => count($a) > 0) ?: [[]];
        $m = array_shift($arrays);

        while ($a = array_shift($arrays))
            foreach ($a as $k => $v)
                if (is_array($v) && isset($m[$k]) && is_array($m[$k])) $m[$k] = static::applyDefaults($m[$k], $v);
                else if (!array_key_exists($k, $m) || in_array($m[$k], [
                    '',
                    null,
                    false
                ])) $m[$k] = $v;
        return $m;
    }
}
