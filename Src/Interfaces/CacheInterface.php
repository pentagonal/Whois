<?php
namespace Pentagonal\WhoIs\Interfaces;

/**
 * Class CacheInterface
 * @package Pentagonal\WhoIs\Interfaces
 */
interface CacheInterface
{
    /**
     * @param string|int $name
     *
     * @return boolean
     */
    public function exist($name);

    /**
     * @param string|int $identifier
     * @param mixed      $data
     * @param int        $timeout
     *
     * @return mixed|void
     */
    public function put($identifier, $data, $timeout = 3600);

    /**
     * @param string|int $identifier
     *
     * @return mixed|null
     */
    public function get($identifier);

    /**
     * @param string|int $identifier
     *
     * @return mixed
     */
    public function delete($identifier);
}
