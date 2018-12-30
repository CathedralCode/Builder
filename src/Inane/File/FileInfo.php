<?php
/**
 * This file is part of the InaneClasses package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <philip@inane.co.za>
 * @package Inane\File
 *
 * @license MIT
 * @license http://www.inane.co.za/license/MIT
 *
 * @copyright 2015-2016 Philip Michael Raab <philip@inane.co.za>
 */

namespace Inane\File;

use Inane\String\Capitalisation;

/**
 * File metadata
 *
 * @package Inane\File
 * @version 0.4.0
 */
class FileInfo extends \SplFileInfo
{
    /**
     * Get the file extension
     *
     * @param Capitalisation    $case Optional: Capitalisation only UPPERCASE and lowercase have any effect
     * {@inheritDoc}
     * @see SplFileInfo::getExtension()
     */
    public function getExtension(Capitalisation $case = null)
    {
        $ext = parent::getExtension();

        switch ($case) {
            case Capitalisation::UPPERCASE:
                $ext = strtoupper($ext);
                break;

            case Capitalisation::lowercase:
                $ext = strtolower($ext);
                break;

            default:

                break;
        }

        return $ext;
    }

    /**
     * Return human readable size (Kb, Mb, ...)
     *
     * @return string|null
     */
    public function getHumanSize($decimals = 2)
    {
        return self::humanSize(parent::getSize(), $decimals);
    }

    /**
     * Return md5 hash
     * @return string|null
     */
    public function getMd5()
    {
        return md5_file(parent::getPathname());
    }

    /**
     * Return the mime type
     *
     * @return string|null
     */
    public function getMimetype()
    {
        return (new \finfo())->file(parent::getPathname(), FILEINFO_MIME_TYPE);
    }

    /**
     * True if file exists
     *
     * @return bool
     */
    public function isValid()
    {
        return file_exists(parent::getPathname());
    }

    /**
     * Convert bites to human readable size
     *
     * @param number $size
     * @param number $decimals
     * @return string
     */
    protected function humanSize($size, $decimals = 2)
    {
        $sizes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($size) - 1) / 3);
        $formatedSize = sprintf("%.{$decimals}f", $size / pow(1024, $factor));
        
        return rtrim($formatedSize, '0.').' '.@$sizes[$factor];
    }
}
