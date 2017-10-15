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

namespace Pentagonal\Tests\PhpUnit\WhoIs\App;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\App\TLDCollector;
use Pentagonal\WhoIs\Util\Puny;
use PHPUnit\Framework\TestCase;

/**
 * Class TLDCollectorTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs\App
 */
class TLDCollectorTest extends TestCase
{
    public function testAvailableServersAndAvailableExtensions()
    {
        $tldCollector = new TLDCollector();

        $this->assertNotEmpty(
            $tldCollector->getAvailableServers()
        );
        $this->assertNotEmpty(
            $tldCollector->getAvailableExtensions()
        );
        $this->assertNotEquals(
            $tldCollector->getAvailableServers(),
            $tldCollector->getAvailableExtensions()
        );
        $this->assertArrayHasKey(
            'com',
            $tldCollector->getAvailableServers()
        );
        $this->assertArrayHasKey(
            'com',
            $tldCollector->getAvailableExtensions()
        );
    }
    public function testGetWhoisServerListFromExtension()
    {
        $tldCollector = new TLDCollector();
        $this->assertTrue(
            $tldCollector->isExtensionExists('com')
        );
        $googleWhoises = $tldCollector->getServersFromExtension('google');
        $this->assertInstanceOf(
            ArrayCollector::class,
            $googleWhoises
        );
        $this->assertNotEmpty(
            $googleWhoises
        );
        $this->assertArrayHasKey(
            0,
            $googleWhoises,
            true
        );
        $this->assertTrue(
            in_array('whois.nic.google', $googleWhoises->toArray())
        );
        $this->assertEquals(
            $tldCollector->getServerFromExtension('google'),
            reset($googleWhoises)
        );
        $this->assertNotEmpty(
            $tldCollector->getSubDomainFromExtension('id')
        );
        $this->assertNull(
            $tldCollector->getServerFromExtension('invalidextension')
        );
    }
    public function testGetFilesExtensionsAndServers()
    {
        $tldCollector = new TLDCollector();
        $this->assertNotEmpty(
            $tldCollector->getAvailableExtensionsFile()
        );
        $this->assertNotEmpty(
            $tldCollector->getAvailableServersFile()
        );
        $this->assertTrue(
            file_exists($tldCollector->getAvailableExtensionsFile())
        );
        $this->assertTrue(
            file_exists($tldCollector->getAvailableServersFile())
        );
    }

    public function testGetPunyCodeIsInstanceOfPuny()
    {
        $tldCollector = new TLDCollector();
        $this->assertInstanceOf(
            Puny::class,
            $tldCollector->getPunyCode()
        );
    }

    public function testEncodeDecodeString()
    {
        $tldCollector = new TLDCollector();
        $this->assertNotEquals(
            $tldCollector->decode('xn--11b4c3d'),
            'xn--11b4c3d'
        );

        $this->assertEquals(
            $tldCollector->decode('xn--11b4c3d'),
            'कॉम'
        );

        $this->assertEquals(
            $tldCollector->decode('कॉम'),
            'कॉम'
        );

        $this->assertNotEquals(
            $tldCollector->encode('कॉम'),
            'कॉम'
        );
        $this->assertEquals(
            $tldCollector->encode('xn--11b4c3d'),
            'xn--11b4c3d'
        );

        $this->assertEquals(
            $tldCollector->encode('कॉम'),
            'xn--11b4c3d'
        );
    }

    public function testThrowable()
    {
        try {
            new TLDCollector([
                0 => 'string is invalid',
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $e
            );
        }
        try {
            new TLDCollector([
                'invalid' => [
                    'valid.string.server',
                    ['invalid as array']
                ],
            ]);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \RuntimeException::class,
                $e
            );
        }
        try {
            $tldCollector = new TLDCollector();
            // white space
            $tldCollector->getSubDomainFromExtension('    ');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
        try {
            $tldCollector = new TLDCollector();
            // white space
            $tldCollector->getServersFromExtension('    ');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
    }
}
