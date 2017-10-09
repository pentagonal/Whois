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

namespace Pentagonal\WhoIs\App;

/**
 * Class ArrayCollector
 * @package Pentagonal\WhoIs\App
 */
class ArrayCollector extends \ArrayObject implements \JsonSerializable
{
    /**
     * Collection constructor.
     *
     * @param array $input
     */
    public function __construct(array $input = [])
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
    public function merge(array $array)
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
    public function jsonSerialize() : array
    {
        return (array) $this;
    }
}
