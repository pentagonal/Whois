<?php
namespace Pentagonal\WhoIs;

use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\Util\Collection;

/**
 * Class ArrayCache
 * @package Pentagonal\WhoIs
 */
class ArrayCache extends Collection implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function put($identifier, $data, $timeout = 3600)
    {
        $this[$identifier] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function get($identifier)
    {
        return isset($this[$identifier]) ? $this[$identifier] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function exist($name)
    {
        return array_key_exists($name, (array) $this);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($identifier)
    {
        unset($this[$identifier]);
    }
}
