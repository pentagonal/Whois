<?php
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
