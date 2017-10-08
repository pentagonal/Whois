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

namespace Pentagonal\WhoIs;

/**
 * Class ServerList
 * @package Pentagonal\WhoIs
 */
final class ServerList
{
    /**
     * @var string[]
     */
    protected static $servers;

    /**
     * @var array[]
     */
    protected static $alternatives;

    /**
     * @return array|string[]
     */
    public static function getServers()
    {
        if (!isset(static::$servers)) {
            static::$servers = require __DIR__ . '/Data/MainServers.php';
        }

        return static::$servers;
    }

    /**
     * @return array|array[]
     */
    public static function getAlternatives()
    {
        if (empty(static::$alternatives)) {
            static::$alternatives =  require __DIR__ . '/Data/AlternativeServers.php';
        }

        return static::$alternatives;
    }

    /**
     * Validate
     *
     * @param string $extension
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private static function validateExtension($extension)
    {
        if (!is_string($extension)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Extension must be as a string, %s given',
                    gettype($extension)
                ),
                E_WARNING
            );
        }

        $extension = strtolower(trim($extension));
        if (preg_match('/[^a-z]/i', $extension)) {
            $extension = idn_to_utf8(idn_to_ascii($extension));
        }

        if ($extension == '') {
            throw new \InvalidArgumentException(
                'Extension could not be empty',
                E_WARNING
            );
        }

        return $extension;
    }

    /**
     * Get Alternative Servers
     *
     * @param string $extension
     * @param mixed $default
     *
     * @return mixed|array
     */
    public static function getAlternativeServers($extension, $default = null)
    {
        $extension = static::validateExtension($extension);
        return isset(static::getAlternatives()[$extension])
            ? static::getAlternatives()[$extension]
            : $default;
    }

    /**
     * Get Alternative Server
     *
     * @param string $extension
     * @param mixed $default
     *
     * @return mixed|null
     */
    public static function getAlternativeServer($extension, $default = null)
    {
        $servers = static::getAlternativeServers($extension);
        return is_array($servers) ? reset($servers) : $default;
    }

    /**
     * Get Extension
     *
     * @param string $extension
     * @param mixed $default
     *
     * @return mixed|string
     */
    public static function getServer($extension, $default = null)
    {
        $extension = static::validateExtension($extension);
        return isset(static::getServers()[$extension])
            ? static::getServers()[$extension]
            : $default;
    }
}
