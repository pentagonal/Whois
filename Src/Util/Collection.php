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
     * Previous Data
     *
     * @return mixed
     */
    public function prev()
    {
        return prev($this);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return (array) $this;
    }
}
