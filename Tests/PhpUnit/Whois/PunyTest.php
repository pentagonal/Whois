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

use Pentagonal\WhoIs\Interfaces\TransCodeInterface;
use Pentagonal\WhoIs\Util\Puny;
use PHPUnit\Framework\TestCase;

/**
 * Class PunyTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class PunyTest extends TestCase
{
    public function testDecodePunyCode()
    {
        $puny = new Puny();
        $this->assertInstanceOf(
            TransCodeInterface::class,
            $puny
        );
        $this->assertNotEquals(
            $puny->decode('xn--11b4c3d'),
            'xn--11b4c3d'
        );
        $this->assertEquals(
            $puny->decode('xn--11b4c3d'),
            'कॉम'
        );
    }

    public function testEncodePunyCode()
    {
        $puny = new Puny();
        $this->assertNotEquals(
            $puny->encode('कॉम'),
            'कॉम'
        );
        $this->assertEquals(
            $puny->encode('कॉम'),
            'xn--11b4c3d'
        );
    }
}
