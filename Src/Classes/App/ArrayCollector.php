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

namespace Pentagonal\WhoIs\App;

use Pentagonal\WhoIs\Interfaces\ArrayCollectorInterface;

/**
 * Class ArrayCollector
 * @package Pentagonal\WhoIs\App
 */
class ArrayCollector extends \ArrayObject implements ArrayCollectorInterface
{
    /**
     * Collection constructor.
     * {@inheritdoc}
     */
    public function __construct(array $input = [])
    {
        parent::__construct($input);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function get($keyName, $default = null)
    {
        if (array_key_exists($keyName, (array) $this)) {
            return $this[$keyName];
        }

        return $default;
    }

    /**
     * Set value
     *
     * @param mixed $keyName
     * @param mixed $value
     */
    public function set($keyName, $value)
    {
        $this[$keyName] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function exist($keyName) : bool
    {
        return array_key_exists($keyName, (array) $this);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function first()
    {
        return reset($this);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function last()
    {
        return end($this);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function next()
    {
        return next($this);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function current()
    {
        return current($this);
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function prev()
    {
        return prev($this);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($keyName)
    {
        unset($this[$keyName]);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(array $array)
    {
        $this->exchangeArray(array_merge((array) $this, $array));
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->exchangeArray([]);
    }

    /**
     * {@inheritdoc}
     */
    public function contain($value) : bool
    {
        return in_array($value, (array) $this);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return (array) $this;
    }

    /**
     * {@inheritdoc}
     */
    public function keys() : array
    {
        return array_keys((array) $this);
    }

    /**
     * {@inheritdoc}
     */
    public function values() : array
    {
        return array_values((array) $this);
    }

    /**
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /**
     * Magic Method to string
     *
     * @return string
     */
    public function __toString() : string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
