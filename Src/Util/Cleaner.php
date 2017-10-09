<?php
/**
 * This package contains some code that reused by other repository(es) for private uses.
 * But on some certain conditions, it will also allowed to used as commercials project.
 * Some code & coding standard also used from other repositories as inspiration ideas.
 * And also uses 3rd-Party as to be used as result value without their permission but permit to be used.
 *
 * @license GPL-3.0  {@link https://www.gnu.org/licenses/gpl-3.0.en.html}
 * @copyright (c) 2017. Pentagonal Development
 * @author pentagonal <org@pentagonal.org>
 */

namespace Pentagonal\WhoIs\Util;

/**
 * Class Cleaner
 * @package Pentagonal\WhoIs\Util
 */
class Cleaner
{
    /**
     * @param string $data
     *
     * @return string
     */
    public static function cleanIniComment(string $data) : string
    {
        $data = trim($data);
        if ($data == '') {
            return $data;
        }

        return preg_replace(
            '/^(?:\;|\#)[^\n]+\n?/sm',
            '',
            $data
        );
    }

    /**
     * Clean Slashed Comment
     *
     * @param string $data
     *
     * @return string
     */
    public static function cleanSlashComment(string $data) : string
    {
        $data = trim($data);
        if ($data == '') {
            return $data;
        }

        return preg_replace(
            '/^(?:\/\/)[^\n]+\n?/sm',
            '',
            $data
        );
    }

    /**
     * Clean Multiple whitespace
     *
     * @param string $data
     *
     * @return string
     */
    public static function cleanMultipleWhiteSpaceTrim(string $data) : string
    {
        return trim(preg_replace('/(\s)+/sm', '$1', $data));
    }

    /**
     * Clean Whois result
     *
     * @param string $data
     *
     * @return string
     */
    public static function cleanWhoIsSocketResult(string $data) : string
    {
        $data = static::cleanMultipleWhiteSpaceTrim($data);
        $data = preg_replace(
            '/^\s?(\%|#)/sm',
            '',
            $data
        );
        $data = preg_replace(
            '~
            (?:
                \>\>\>?   # information
                |Terms\s+of\s+Use\s*:\s+Users?\s+accessing  # terms
                |URL\s+of\s+the\s+ICANN\s+WHOIS # informational from icann 
            ).*
            ~isx',
            '',
            $data
        );
        return preg_replace(
            '/([\n])+/s',
            '$1',
            $data
        );
    }
}
