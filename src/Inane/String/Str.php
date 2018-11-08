<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\String
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2018 Philip Michael Raab <philip@inane.co.za>
 */

namespace Inane\String;

use \Inane\String\Capitalisation;

/**
 *
 * @package Inane\String\Str
 * @version 0.0.9
 * @property-read public length
 * @property public string
 */
class Str
{
    /**
     * @var Capitalisation
     */
    protected $_case = Capitalisation::Ignore;

    /**
     * @var string
     */
    protected $_str = '';

    /**
     * Creates instance of Str object
     *
     * @param string $string
     */
    public function __construct(string $string = '')
    {
        if ($string) {
            $this->_str = $string;
        }
    }

    /**
     * magic method: _get
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        if (!in_array($property, ['length', 'string'])) {
            throw new \Exception("Invalid Property:\n\tStr has no property: {$property}");
        }

        $methods = [
            'length' => 'length',
            'string' => 'getString'
        ];

        return $this->{$methods[$property]}();
    }

    /**
     * magic method: _set
     *
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function __set($property, $value)
    {
        if (!in_array($property, ['string'])) {
            throw new \Exception("Invalid Property:\n\tStr has no property: {$property}");
        }

        $methods = [
            'length' => 'length',
            'string' => 'setString'
        ];

        $this->{$methods[$property]}($value);

        return $this;

        $method = $this->parseMethodName($property, 'set');
        $this->$method($value);
    }

    /**
     * Echoing the Str object print out the string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->_str;
    }

    /**
     * Append str to Str
     *
     * @param string $str
     * @return Str
     */
    public function append(string $str): Str
    {
        $this->_str .= $str;

        return $this;
    }

    /**
     * Check if Str contains needle
     *
     * @param string $needle
     * @return bool
     */
    public function contains(string $needle): bool
    {
        return self::str_contains($needle, $this->_str);
    }

    /**
     * @return the string
     */
    public function getString(): string
    {
        return $this->_str;
    }

    /**
     * length of str
     *
     * @return int
     */
    public function length(): int
    {
        return \strlen($this->_str);
    }

    /**
     * Prepend str to Str
     *
     * @param string $str
     * @return Str
     */
    public function prepend(string $str): Str
    {
        $this->_str = "{$str}{$this->_str}";

        return $this;
    }

    /**
     * Replaces last match of search with replace
     *
     * @param string $search
     * @param string $replace
     * @return Str
     */
    public function replaceLast(string $search, string $replace): Str
    {
        $this->_str = self::str_replace_last($search, $replace, $this->_str);

        return $this;
    }

    /**
     * Replaces last match of search with replace
     *
     * @param string $search
     * @param string $replace
     * @return Str
     */
    public function replace(string $search, string $replace): Str
    {
        $this->_str = \str_replace($search, $replace, $this->_str);

        return $this;
    }

    /**
     * @param string $string
     *
     * @return Str
     */
    public function setString(string $string): Str
    {
        $this->_str = $string;

        return $this;
    }

    /**
     * Check if haystack contains needle
     *
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    public static function str_contains(string $needle, string $haystack): bool
    {
        return strstr($haystack, $needle);
    }

    /**
     * Replaces last match of search with replace in str
     *
     * @param string $search
     * @param string $replace
     * @param string $str
     * @return string
     */
    public static function str_replace_last(string $search, string $replace, string $str): string
    {
        if (($pos = strrpos($str, $search)) !== false) {
            $search_length = strlen($search);
            $str = substr_replace($str, $replace, $pos, $search_length);
        }

        return $str;
    }

    /**
     * Changes the case of $string to $case and optionally removes spaces
     *
     * @param String $string
     * @param Capitalisation $case
     * @param bool $removeSpaces
     *
     * @return string
     */
    public static function str_to_case(String $string, Capitalisation $case, bool $removeSpaces = false): string
    {
        switch ($case) {
            case Capitalisation::UPPERCASE:
                $string = strtoupper($string);
                break;

            case Capitalisation::lowercase:
                $string = strtolower($string);
                break;

            case Capitalisation::camelCase:
                $string = lcfirst(ucwords(strtolower($string)));
                break;

            case Capitalisation::StudlyCaps:
                $string = ucwords(strtolower($string));
                break;

            case Capitalisation::RaNDom:
                for ($i = 0, $c = strlen($string); $i < $c; $i++) {
                    $string[$i] = (rand(0, 100) > 50
                        ? strtoupper($string[$i])
                        : strtolower($string[$i]));
                }

                break;

            default:

                break;
        }

        if ($removeSpaces) {
            $string = str_replace(' ', '', $string);
        }

        return $string;
    }

    /**
     * Create Str with $length random characters
     *
     * @param int $length
     * @return Str
     */
    public static function stringWithRandomCharacters(int $length = 6): Str
    {
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;

        $str = new self();
        while ($str->length < $length) {
            $rand = mt_rand(0, $max);
            $str->append($characters[$rand]);
        }

        return $str;
    }

    /**
     * Changes the case of Str to $case and optionally removes spaces
     *
     * @param Capitalisation $case
     * @param bool $removeSpaces
     * @return Str
     */
    public function toCase(Capitalisation $case, bool $removeSpaces = false): Str
    {
        $this->_str = self::str_to_case($this->_str, $case, $removeSpaces);
        $this->_case = $case;

        return $this;
    }

    /**
     * Trim chars from beginning and end of string default chars ' ,:-./\\`";'
     *
     * @param string $chars to trim
     * @return Str
     */
    public function trim(string $chars = ' ,:-./\\`";'): Str
    {
        $this->_str = \trim($this->_str, $chars);

        return $this;
    }
}
