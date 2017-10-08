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

use Pentagonal\WhoIs\Util\ExtensionStorage;
use PHPUnit\Framework\TestCase;

/**
 * Class ExtensionStorageTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class ExtensionStorageTest extends TestCase
{
    public function testInstance()
    {
        $storage = new ExtensionStorage();
        $this->assertInstanceOf(
            \JsonSerializable::class,
            $storage
        );
        $this->assertInstanceOf(
            \Serializable::class,
            $storage
        );
        $this->assertInstanceOf(
            \Countable::class,
            $storage
        );
    }

    public function testAllMethod()
    {
        $storage = new ExtensionStorage(['whois.verisign-grs.com']);
        $this->assertCount(
            1,
            $storage->all()
        );
        $this->assertCount(
            1,
            $storage
        );
        // push
        $storage->push('whois.pir.org');
        $this->assertCount(
            2,
            $storage
        );
        // if same on push let just ignore
        $storage->push('whois.pir.org');
        $this->assertCount(
            2,
            $storage
        );
        // test invalid string so it will be still 2 count
        $storage->push(true);
        $this->assertCount(
            2,
            $storage
        );

        // pop
        $storage->pop();
        $this->assertCount(
            1,
            $storage
        );
        // if same on push let just ignore
        $storage->add('whois.pir.org');
        $this->assertCount(
            2,
            $storage
        );
        $storage->shift();
        $this->assertCount(
            1,
            $storage
        );
        // unshift 2
        $storage->unShift('whois.verisign-grs.com');
        $storage->unShift('whois.verisign-grs.com');
        $this->assertCount(
            2,
            $storage
        );

        // test invalid string so it will be still 2 count
        $storage->unShift(true);
        $this->assertCount(
            2,
            $storage
        );
        $all = $storage->all();
        $this->assertEquals(
            reset($all),
            $storage->reset()
        );
        $this->assertEquals(
            end($all),
            $storage->end()
        );
        $this->assertEquals(
            prev($all),
            $storage->prev()
        );
        $this->assertEquals(
            next($all),
            $storage->next()
        );
        $this->assertEquals(
            current($all),
            $storage->current()
        );

        // json test
        $this->assertEquals(
            $storage->jsonSerialize(),
            $storage->all()
        );
        $this->assertJson(
            json_encode($storage)
        );
        $this->assertEquals(
            json_encode($storage),
            json_encode($storage->all())
        );
        // test serialize
        $this->assertEquals(
            serialize($storage->all()),
            $storage->serialize()
        );
        $this->assertEquals(
            unserialize(serialize($storage->all())),
            unserialize($storage->serialize())
        );
        $oldStorage = clone $storage;
        $storage->unserialize(serialize($storage));
        $this->assertEquals(
            $oldStorage,
            $storage
        );
        $this->assertEquals(
            serialize($storage),
            // casting
            (string) $storage
        );

        $storage->clear();
        $this->assertEmpty(
            $storage->all()
        );
    }
}
