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

namespace Pentagonal\Tests\PhpUnit\WhoIs\Util;

use Pentagonal\WhoIs\Util\Sanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Class SanitizerTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs\Util
 */
class SanitizerTest extends TestCase
{
    /**
     * @var array
     */
    protected $defaultDataArray = [
        'This' => [
            'is' => 'default'
        ],
        'data' => __CLASS__
    ];

    /**
     * @var string
     */
    protected $stringToBeSanitized = 'This : कॉम is not valid ascii and this < maybe need html entity';

    public function testEntityString()
    {
        if (extension_loaded('iconv')) {
            $this->assertNotEquals(
                $this->stringToBeSanitized,
                Sanitizer::entityMultiByteString($this->stringToBeSanitized)
            );
        }
        $this->assertNotEquals(
            Sanitizer::entityMultiByteString($this->stringToBeSanitized),
            Sanitizer::entityMultiByteString($this->stringToBeSanitized, true)
        );
    }

    public function testSerializeAnsUnSerialize()
    {
        $this->assertNotEquals(
            $this->defaultDataArray,
            Sanitizer::maybeSerialize($this->defaultDataArray)
        );

        $this->assertEquals(
            serialize($this->defaultDataArray),
            Sanitizer::maybeSerialize($this->defaultDataArray)
        );

        $this->assertNotEquals(
            serialize($this->defaultDataArray),
            Sanitizer::maybeSerialize(serialize($this->defaultDataArray))
        );
        $this->assertEquals(
            null,
            Sanitizer::maybeSerialize(null)
        );
        $this->assertEquals(
            serialize(serialize(null)),
            Sanitizer::maybeSerialize(serialize(null))
        );
        $this->assertEquals(
            serialize(serialize($this->defaultDataArray)),
            Sanitizer::maybeSerialize(serialize($this->defaultDataArray))
        );

        $this->assertEquals(
            $this->defaultDataArray,
            Sanitizer::maybeUnSerialize(serialize($this->defaultDataArray))
        );

        $this->assertEquals(
            serialize($this->defaultDataArray),
            Sanitizer::maybeUnSerialize(
                serialize(serialize($this->defaultDataArray))
            )
        );

        $this->assertEquals(
            serialize(new \stdClass),
            Sanitizer::maybeSerialize(
                new \stdClass
            )
        );
        $this->assertEquals(
            new \stdClass,
            Sanitizer::maybeUnSerialize(
                new \stdClass
            )
        );

        $this->assertEquals(
            ' ',
            Sanitizer::maybeSerialize(' ')
        );

        $this->assertEquals(
            ' ',
            Sanitizer::maybeUnSerialize(' ')
        );

        // check is serialized
        $this->assertFalse(
            Sanitizer::isSerialized(true)
        );
        $this->assertFalse(
            Sanitizer::isSerialized(new \stdClass())
        );
        $this->assertFalse(
            Sanitizer::isSerialized('{}')
        );
        $this->assertFalse(
            Sanitizer::isSerialized('   ')
        );
        $this->assertTrue(
            Sanitizer::isSerialized(serialize(null))
        );
        $this->assertTrue(
            Sanitizer::isSerialized(serialize(true))
        );
        $this->assertTrue(
            Sanitizer::isSerialized(serialize(1))
        );
    }
}
