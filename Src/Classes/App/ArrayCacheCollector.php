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

use Pentagonal\WhoIs\Interfaces\CacheInterface;

/**
 * Cache class that stored into array collection cache
 *
 * Class ArrayCacheCollector
 * @package Pentagonal\WhoIs\App
 */
class ArrayCacheCollector implements CacheInterface
{
    /**
     * @var ArrayCollector
     */
    private $arrayCollector;

    const SELECTOR_TIME = 'time';
    const SELECTOR_DATA = 'data';

    /**
     * ArrayCache constructor.
     */
    public function __construct()
    {
        $this->arrayCollector = new ArrayCollector();
    }

    /**
     * Create new instance ArrayCacheCollector
     *
     * @return ArrayCacheCollector
     */
    public static function createInstance() : ArrayCacheCollector
    {
        return new static();
    }

    /**
     * {@inheritdoc}
     */
    public function exist(string $identifier): bool
    {
        return $this->arrayCollector->exist($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $identifier, $default = null)
    {
        return $this->exist($identifier)
            ? $this->arrayCollector[$identifier][static::SELECTOR_DATA]
            : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $identifier, $data, int $timeout = 3600)
    {
        return $this->arrayCollector[$identifier] = [
            static::SELECTOR_TIME => $timeout,
            static::SELECTOR_DATA => $data
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $identifier)
    {
        unset($this->arrayCollector[$identifier]);
    }

    /**
     * Clear stored cache when object class destruct
     */
    public function __destruct()
    {
        $this->arrayCollector->clear();
    }

    /**
     * Magic method that object class printing into echo as string
     *
     * @return string
     */
    public function __toString() : string
    {
        return sprintf(
            '%1$s::%2$s',
            __CLASS__,
            spl_object_hash($this)
        );
    }
}
