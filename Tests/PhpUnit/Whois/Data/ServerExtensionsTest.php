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

namespace Pentagonal\Tests\PhpUnit\WhoIs\Data;

use Pentagonal\WhoIs\Util\DataGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Class ServerExtensionsTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs\Data
 */
class ServerExtensionsTest extends TestCase
{
    /**
     * Server List test for require files that returning array
     */
    public function testIncludeServerExtensionsFilesReturningArray()
    {
        $this->assertNotEmpty(
            require DataGenerator::PATH_EXTENSIONS_AVAILABLE
        );

        $this->assertNotEmpty(
            require DataGenerator::PATH_WHOIS_SERVERS
        );

        $this->assertArrayHasKey(
            'com',
            require DataGenerator::PATH_EXTENSIONS_AVAILABLE
        );

        $this->assertArrayHasKey(
            'com',
            require DataGenerator::PATH_WHOIS_SERVERS
        );
    }
}
