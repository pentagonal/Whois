<?php
namespace Pentagonal\Tests\PhpUnit\WhoIs;

use Pentagonal\WhoIs\Util\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Class CollectionTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class CollectionTest extends TestCase
{
    public function testInstance()
    {
        $collection = new Collection();
        $this->assertEmpty(
            $collection,
            'Array instance is empty'
        );
        $this->assertThat(
            $collection->getArrayCopy(),
            new \PHPUnit_Framework_Constraint_IsType('array')
        );
    }

    public function testValue()
    {
        $collection = new Collection([
            'test1' => 'true1',
            'test2' => 'true2',
        ]);
        $this->assertArrayHasKey(
            'test2',
            (array) $collection
        );
        $this->assertArrayHasKey(
            'test2',
            (array) $collection
        );
        $this->assertNotEmpty(
            $collection
        );
        $this->assertEquals(
            count($collection),
            2
        );
        $this->assertEquals(
            $collection->first(),
            'true1'
        );
        $this->assertEquals(
            $collection->next(),
            'true2'
        );
        $this->assertEquals(
            $collection->last(),
            'true2'
        );
        $this->assertEquals(
            $collection->prev(),
            'true1'
        );
        $this->assertEquals(
            $collection->current(),
            'true1'
        );
        $this->assertJson(
            json_encode($collection)
        );
        $this->assertEquals(
            json_encode($collection),
            json_encode($collection->getArrayCopy())
        );
    }
}
