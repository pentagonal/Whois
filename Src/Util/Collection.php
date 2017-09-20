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
     * @return array
     */
    public function jsonSerialize()
    {
        return (array) $this;
    }
}
