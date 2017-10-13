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
 * Class Sanitizer
 * @package Pentagonal\WhoIs\Util
 *
 * Sanitation helper
 */
class Sanitizer
{
    /**
     * @var bool
     */
    private static $iconvEnabled;

    /**
     * @var int
     */
    private static $regexLimit;

    /**
     * Entities the Multi bytes deep string
     *
     * @param string $string  the string to detect multi bytes
     * @param bool  $entity true if want to entity the output
     *
     * @return mixed
     */
    public static function entityMultiByteString(string $string, $entity = false)
    {
        if (!isset(self::$iconvEnabled)) {
            // safe resource check
            self::$iconvEnabled = function_exists('iconv');
        }

        if (! self::$iconvEnabled && ! $entity) {
            return $string;
        }

        if (!isset(self::$regexLimit)) {
            self::$regexLimit = @ini_get('pcre.backtrack_limit');
            self::$regexLimit = ! is_numeric(self::$regexLimit) ? 4096 : abs(self::$regexLimit);
            // minimum regex is 512 byte
            self::$regexLimit = self::$regexLimit < 512 ? 512 : self::$regexLimit;
            // limit into 40 KB
            self::$regexLimit = self::$regexLimit > 40960 ? 40960 : self::$regexLimit;
        }

        /**
         * Work Safe with Parse @uses @var $limit Bit
         * | 4KB data split for regex callback & safe memory usage
         * that maybe fail on very long string
         */
        if (strlen($string) > self::$regexLimit) {
            $data = '';
            foreach (str_split($string, self::$regexLimit) as $stringSplit) {
                $data .= self::entityMultiByteString($stringSplit, $entity);
            }

            return $data;
        }

        if ($entity) {
            $string = htmlentities(html_entity_decode($string));
        }

        return self::$iconvEnabled
            ? (
                preg_replace_callback(
                    '/[\x{80}-\x{10FFFF}]/u',
                    function ($match) {
                        if (!($char = current($match))) {
                            return "&#x0;";
                        }
                        $utf = iconv('UTF-8', 'UCS-4//IGNORE', $char);
                        $utf = $utf ? bin2hex($utf) : null;
                        if (!is_string($utf) || ($utf == trim($utf)) == '') {
                            return "&#x0;";
                        }
                        $utf = strtolower($utf);
                        return "&#x{$utf};";
                    },
                    $string
                ) ?: $string
            ) : $string;
    }

    /* --------------------------------------------------------------------------------*
     |                              Serialize Helper                                   |
     |                                                                                 |
     | Custom From WordPress Core wp-includes/functions.php                            |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Check value to find if it was serialized.
     * If $data is not an string, then returned value will always be false.
     * Serialized data is always a string.
     *
     * @param  mixed $data   Value to check to see if was serialized.
     * @param  bool  $strict Optional. Whether to be strict about the end of the string. Defaults true.
     * @return bool  false if not serialized and true if it was.
     */
    public static function isSerialized($data, $strict = true)
    {
        /* if it isn't a string, it isn't serialized
         ------------------------------------------- */
        if (! is_string($data) || trim($data) == '') {
            return false;
        }

        $data = trim($data);
        // null && boolean
        if ('N;' == $data || $data == 'b:0;' || 'b:1;' == $data) {
            return true;
        }

        if (strlen($data) < 4 || ':' !== $data[1]) {
            return false;
        }

        if ($strict) {
            $last_char = substr($data, -1);
            if (';' !== $last_char && '}' !== $last_char) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace     = strpos($data, '}');

            // Either ; or } must exist.
            if (false === $semicolon && false === $brace
                || false !== $semicolon && $semicolon < 3
                || false !== $brace && $brace < 4
            ) {
                return false;
            }
        }

        $token = $data[0];
        switch ($token) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
            // or else fall through
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }

        return false;
    }

    /**
     * Un-serialize value only if it was serialized.
     *
     * @param  string $original Maybe un-serialized original, if is needed.
     * @return mixed  Un-serialized data can be any type.
     */
    public static function maybeUnSerialize($original)
    {
        if (! is_string($original) || trim($original) == '') {
            return $original;
        }

        /**
         * Check if serialized
         * check with trim
         */
        if (self::isSerialized($original)) {
            /**
             * use trim if possible
             * Serialized value could not start & end with white space
             */
            return @unserialize(trim($original));
        }

        return $original;
    }

    /**
     * Serialize data, if needed. @uses for ( un-compress serialize values )
     * This method to use safe as save data on database. Value that has been
     * Serialized will be double serialize to make sure data is stored as original
     *
     *
     * @param  mixed $data Data that might be serialized.
     * @return mixed A scalar data
     */
    public static function maybeSerialize($data)
    {
        if (is_array($data) || is_object($data)) {
            return @serialize($data);
        }

        // Double serialization is required for backward compatibility.
        if (self::isSerialized($data, false)) {
            return serialize($data);
        }

        return $data;
    }
}
