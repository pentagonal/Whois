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

namespace Pentagonal\WhoIs\Util;

/**
 * Class ArrayLoopAbleCallback
 * @package Pentagonal\WhoIs\Util
 */
class ArrayLoopAbleCallback implements \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $contents = [];

    /**
     * @var bool
     */
    protected $stopped = false;

    /**
     * ArrayLoopAbleCallback constructor.
     *
     * @param array $contents
     */
    public function __construct(array $contents)
    {
        $this->contents = $contents;
    }

    /**
     * Get Contents
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->contents;
    }

    /**
     * @return mixed
     */
    public function first()
    {
        return reset($this->contents);
    }

    /**
     * @return mixed
     */
    public function last()
    {
        return end($this->contents);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->contents);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->contents);
    }

    /**
     * @param int|string $offset
     *
     * @return bool
     */
    public function exist($offset) : bool
    {
        return array_key_exists($offset, $this->contents);
    }

    /**
     * Get offset
     *
     * @param int|string $offset
     *
     * @return mixed
     */
    public function offset($offset)
    {
        if (!$this->exist($offset)) {
            throw new \InvalidArgumentException(
                sprintf('Offset %d is not exists', $offset)
            );
        }

        return $this->contents[$offset];
    }

    /**
     * Stop process
     */
    public function stop()
    {
        $this->stopped = true;
    }

    /**
     * Resume process
     */
    public function resume()
    {
        $this->stopped = false;
    }

    /**
     * @param \Closure $callback
     *
     * @return array
     */
    public function each(\Closure $callback) : array
    {
        $result = [];
        $this->resume();
        foreach ($this->contents as $key => $value) {
            if ($this->stopped) {
                break;
            }

            $result[$key] = $callback($value, $this);
        }

        // do resume
        $this->resume();
        return $result;
    }

    /**
     * Countable
     *
     * @return int
     */
    public function count() : int
    {
        return count($this->contents);
    }

    /**
     * Clear cached data
     */
    public function clear()
    {
        $this->contents = [];
    }

    /**
     * @return \Iterator
     */
    public function getIterator() : \Iterator
    {
        return new \ArrayIterator($this->toArray());
    }
}
