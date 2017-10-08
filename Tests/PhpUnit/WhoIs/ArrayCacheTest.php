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

namespace Pentagonal\Tests\PhpUnit\WhoIs;

use Pentagonal\WhoIs\ArrayCache;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ArrayCacheTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class ArrayCacheTest extends TestCase
{
    /**
     * @var array
     */
    protected $defaultValues = [
        'array_key' => __CLASS__
    ];

    /**
     * Instance
     */
    public function testInstance()
    {
        $arrayCache = new ArrayCache();
        $this->assertInstanceOf(
            CacheInterface::class,
            $arrayCache
        );
        $this->assertInstanceOf(
            \ArrayObject::class,
            $arrayCache
        );
    }

    public function testCache()
    {
        $arrayCache = new ArrayCache();
        $arrayCache->put('key', $this->defaultValues, 2400);
        $this->assertArrayHasKey(
            'key',
            $arrayCache
        );
        $this->assertEquals(
            $this->defaultValues,
            $arrayCache->get('key')
        );
        $this->assertTrue(
            $arrayCache->exist('key')
        );
        $this->assertFalse(
            $arrayCache->exist('value')
        );

        $arrayCache->delete('key');
        $this->assertFalse(
            $arrayCache->exist('key')
        );
    }
}
