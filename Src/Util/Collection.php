<?php
namespace Pentagonal\WhoIs\Util;

/**
 * Class Collection
 * @package Pentagonal\WhoIs\Util
 */
class Collection extends \ArrayObject implements \JsonSerializable
{
    /**
     * Collection constructor.
     *
     * @param array $input
     */
    public function __construct($input = [])
    {
        parent::__construct($input);
    }

    /**
     * Get First Value
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this);
    }

    /**
     * Get First Value
     *
     * @return mixed
     */
    public function last()
    {
        return end($this);
    }

    /**
     * Get Next Offset
     *
     * @return mixed
     */
    public function next()
    {
        return next($this);
    }

    /**
     * Current Offset
     *
     * @return mixed
     */
    public function current()
    {
        return current($this);
    }

    /**
     * Previous Data
     *
     * @return mixed
     */
    public function prev()
    {
        return prev($this);
    }

    /**
     * Fill values
     *
     * @param array $array
     */
    public function replace(array $array)
    {
        foreach ($array as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Clear data
     */
    public function clear()
    {
        foreach ((array) $this as $key => $value) {
            unset($this[$key]);
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return (array) $this;
    }
}
