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

use Pentagonal\WhoIs\Util\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Class CollectionTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class CollectionTest extends TestCase
{
    /**
     * @var array
     */
    protected $defaultValues = [
        'test1' => 'true1',
        'test2' => 'true2',
    ];

    /**
     * Test Instance
     */
    public function testInstanceArrayObjectCollection()
    {
        $collection = new Collection();
        // collection must be instance ArrayObject
        $this->assertInstanceOf(
            \ArrayObject::class,
            $collection,
            sprintf(
                '%1$s must be instance of %2$s',
                Collection::class,
                \ArrayObject::class
            )
        );

        // collection must be instance JsonSerializable
        $this->assertInstanceOf(
            \JsonSerializable::class,
            $collection,
            sprintf(
                '%1$s must be instance of %2$s',
                Collection::class,
                \JsonSerializable::class
            )
        );

        // collection must be instance Countable
        $this->assertInstanceOf(
            \Countable::class,
            $collection,
            sprintf(
                '%1$s must be instance of %2$s',
                Collection::class,
                \Countable::class
            )
        );

        $this->assertEmpty(
            $collection,
            'Array instance is empty'
        );
    }

    /**
     * Test that collection is countable
     */
    public function testCountable()
    {
        $collection = new Collection();
        // countable
        $this->assertCount(
            0,
            $collection,
            sprintf(
                '%s must be countable',
                Collection::class
            )
        );
        $this->assertEmpty(
            $collection,
            'Array instance is empty when construct with empty data'
        );
    }

    /**
     * Test for set & clear
     */
    public function testSetValuesOfArrayAndClearIt()
    {
        $collection = new Collection();
        // empty collection
        $this->assertCount(
            0,
            $collection # countable
        );

        // set value
        $collection->replace($this->defaultValues);
        $this->assertCount(
            count($this->defaultValues),
            $collection, # countable
            sprintf(
                '%s after replace / set value with default value count is equal',
                Collection::class
            )
        );

        $this->assertEquals(
            $this->defaultValues,
            $collection->getArrayCopy(),
            'Array values has equal for default after set'
        );

        // clear data
        $collection->clear();
        $this->assertEmpty(
            $collection
        );
    }

    /**
     * Test for casting and instantiate
     */
    public function testInstantiationAndCasting()
    {
        $collection = new Collection($this->defaultValues);
        // test array copy
        $this->assertThat(
            $collection->getArrayCopy(),
            new \PHPUnit_Framework_Constraint_IsType('array')
        );

        $this->assertEquals(
            $collection->getArrayCopy(),
            (array) $collection,
            'Equalities of array casting with array copy'
        );

        $this->assertEquals(
            $collection->getArrayCopy(),
            $this->defaultValues,
            'Equalities of array values with array copy'
        );

        $this->assertEquals(
            $this->defaultValues,
            (array) $collection,
            'Equalities of array casting with array default value'
        );

        $this->assertNotEmpty(
            $collection,
            'Test that collection is not empty'
        );

        // test array casting that has key
        $keys = array_keys($this->defaultValues);
        $this->assertArrayHasKey(
            reset($keys),
            (array) $collection
        );
        $this->assertArrayHasKey(
            end($keys),
            (array) $collection
        );
    }

    public function testPrevNextCurrent()
    {
        $collection = new Collection($this->defaultValues);
        $this->assertNotEmpty(
            $collection
        );

        $this->assertEquals(
            count($collection),
            2
        );

        $this->assertEquals(
            $collection->first(),
            reset($this->defaultValues)
        );

        $this->assertEquals(
            reset($collection),
            reset($this->defaultValues)
        );

        $this->assertEquals(
            $collection->next(),
            next($this->defaultValues)
        );

        $this->assertEquals(
            $collection->last(),
            end($this->defaultValues)
        );

        $this->assertEquals(
            end($collection),
            end($this->defaultValues)
        );

        $this->assertEquals(
            $collection->prev(),
            prev($this->defaultValues)
        );

        $this->assertEquals(
            $collection->current(),
            current($this->defaultValues)
        );

        $this->assertEquals(
            current($collection),
            current($this->defaultValues)
        );
    }

    /**
     * Test Collection is JsonSerializable
     */
    public function testJsonSerializable()
    {
        $collection = new Collection($this->defaultValues);
        $this->assertJson(
            json_encode($collection),
            sprintf(
                '%s is json serializable',
                Collection::class
            )
        );

        $this->assertEquals(
            json_encode($collection),
            json_encode($collection->getArrayCopy())
        );
    }
}
