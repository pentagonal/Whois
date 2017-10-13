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

declare(strict_types=1);

namespace Pentagonal\WhoIs\Interfaces;

/**
 * Class CacheInterface
 * @package Pentagonal\WhoIs\Interfaces
 */
interface CacheInterface
{
    /**
     * @param string $identifier identifier or key of cache stored
     *
     * @return boolean
     */
    public function exist(string $identifier) : bool;

    /**
     * Set cache into cache storage
     *
     * @param string $identifier identifier or key of cache stored
     * @param mixed  $data       Data to stored into cache
     * @param int    $timeout    timeout / expired time
     *
     * @return mixed|void
     */
    public function put(string $identifier, $data, int $timeout = 3600);

    /**
     * Get Cache with given identifier
     *
     * @param string $identifier identifier or key of cache stored
     * @param mixed  $default    default return value if cache does not exists
     *
     * @return mixed|null
     */
    public function get(string $identifier, $default = null);

    /**
     * Delete cache with given identifier
     *
     * @param string $identifier identifier or key of cache stored
     *
     * @return mixed
     */
    public function delete(string $identifier);
}
