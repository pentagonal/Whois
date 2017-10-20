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
 * Interface ArrayCollectorInterface
 * @package Pentagonal\WhoIs\Interfaces
 */
interface ArrayCollectorInterface extends \IteratorAggregate, \ArrayAccess, \Serializable, \Countable, \JsonSerializable
{
    /**
     * Get value from first offset
     *
     * @return mixed
     */
    public function first();

    /**
     * Get value from last offset
     *
     * @return mixed
     */
    public function last();

    /**
     * Get value from next data offset
     *
     * @return mixed
     */
    public function next();

    /**
     * Get value from current offset
     *
     * @return mixed
     */
    public function current();

    /**
     * Get value from prev data offset
     *
     * @return mixed
     */
    public function prev();

    /**
     * Merge the values
     *
     * @param array $array
     *
     * @return void
     */
    public function merge(array $array);

    /**
     * Clear all array data
     *
     * @return void
     */
    public function clear();

    /**
     * Check if value exists by offset
     *
     * @param mixed $keyName
     *
     * @return bool
     */
    public function exist($keyName) : bool;

    /**
     * check if contains expected value
     *
     * @param string $value
     *
     * @return bool
     */
    public function contain($value) : bool;

    /**
     * Remove values by offset
     *
     * @param string $keyName
     */
    public function remove($keyName);

    /**
     * Get value by Offset
     *
     * @param mixed $offset
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($offset, $default = null);

    /**
     * Get array keys
     *
     * @return array
     */
    public function keys() : array;

    /**
     * Get array values
     *
     * @return array
     */
    public function values() : array;

    /**
     * Get as array -> all array
     *
     * @return array
     */
    public function toArray() : array;

    /**
     * Returning array data that to be json serialize
     *
     * @return array
     */
    public function jsonSerialize() : array;
}
