<?php
namespace Pentagonal\WhoIs\Util;

/**
 * Class ExtensionStorage
 * @package Pentagonal\WhoIs\Util
 */
final class ExtensionStorage implements \JsonSerializable, \Serializable, \Countable
{
    /**
     * @var array|string[]
     */
    private $stored = [];

    /**
     * ExtensionStorage constructor.
     *
     * @param array $extensions
     */
    public function __construct(array $extensions = [])
    {
        $this->merge($extensions);
    }

    /**
     * @param string $server
     */
    public function add($server)
    {
        $this->push($server);
    }

    /**
     * @param array $extensions
     */
    public function merge(array $extensions)
    {
        foreach ($extensions as $value) {
            $this->add($value);
        }
    }

    /**
     * Remove first server
     */
    public function shift()
    {
        array_shift($this->stored);
    }

    /**
     * Remove first server
     */
    public function pop()
    {
        array_pop($this->stored);
    }

    public function unShift($server)
    {
        if (!is_string($server)) {
            return;
        }
        $server = strtolower($server);
        $key = array_search($server, $this->stored);
        if ($key !== false) {
            unset($this->stored[$server]);
            $this->stored = array_values($this->stored);
        }

        array_unshift($this->stored, $server);
    }

    public function push($server)
    {
        if (!is_string($server)) {
            return;
        }
        $server = strtolower($server);
        $key = array_search($server, $this->stored);
        if ($key !== false) {
            unset($this->stored[$server]);
            $this->stored = array_values($this->stored);
        }

        $this->stored[] = $server;
    }

    /**
     * @return array|string[]
     */
    public function all()
    {
        return $this->stored;
    }

    /**
     * @return mixed
     */
    public function reset()
    {
        return reset($this->stored);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->stored);
    }

    /**
     * @return mixed
     */
    public function prev()
    {
        return prev($this->stored);
    }

    /**
     * @return mixed
     */
    public function end()
    {
        return end($this->stored);
    }

    /**
     * @return array|string[]
     */
    public function jsonSerialize()
    {
        return $this->stored;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->stored);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = @unserialize($serialized);
        if (is_array($data)) {
            $this->merge($data);
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->stored);
    }

    /**
     * clear data
     */
    public function clear()
    {
        $this->stored = [];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return serialize($this);
    }
}
