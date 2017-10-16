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
     * Get First Value
     *
     * @return mixed
     */
    public function first();

    /**
     * Get First Value
     *
     * @return mixed
     */
    public function last();

    /**
     * Get Next Offset
     *
     * @return mixed
     */
    public function next();

    /**
     * Current Offset
     *
     * @return mixed
     */
    public function current();

    /**
     * Previous Data
     *
     * @return mixed
     */
    public function prev();

    /**
     * Fill values
     *
     * @param array $array
     *
     * @return void
     */
    public function merge(array $array);

    /**
     * Clear data
     *
     * @return void
     */
    public function clear();

    /**
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
     * Remove offset
     *
     * @param string $keyName
     */
    public function remove($keyName);

    /**
     * Get as array
     *
     * @return array
     */
    public function toArray() : array;

    /**
     * @return array
     */
    public function jsonSerialize() : array;
}
