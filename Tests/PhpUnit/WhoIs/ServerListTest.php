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

use Pentagonal\WhoIs\ServerList;
use PHPUnit\Framework\TestCase;

class ServerListTest extends TestCase
{
    /**
     * Test Servers
     */
    public function testGetServers()
    {
        $serverList = ServerList::getServers();
        $this->assertNotEmpty($serverList);
        $this->assertEquals(
            $serverList,
            require __DIR__ .'/../../../Src/Data/MainServers.php'
        );
    }

    public function testGetAlternativeServers()
    {
        $alternative = ServerList::getAlternatives();
        $this->assertNotEmpty($alternative);
        $this->assertEquals(
            $alternative,
            require __DIR__ .'/../../../Src/Data/AlternativeServers.php'
        );
    }

    public function testGetAlternativeServer()
    {
        $listArray = ServerList::getAlternativeServers('academy');
        $server = ServerList::getAlternativeServer('academy');
        $this->assertCount(1, $listArray);
        $this->assertNotEmpty($server);
    }

    public function testExtensionValidation()
    {
        try {
            ServerList::getAlternativeServer(true);
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
        // get non ascii
        $this->assertNotEmpty(ServerList::getServer('vermögensberater'));
        try {
            ServerList::getServer(
                '       '
            );
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
    }
}