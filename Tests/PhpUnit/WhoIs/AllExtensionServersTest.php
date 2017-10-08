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

use PHPUnit\Framework\TestCase;

/**
 * Class AllExtensionServersTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class AllExtensionServersTest extends TestCase
{
    /**
     * Test Extension
     */
    public function testAllExtensionReturn()
    {
        $extensions = require __DIR__ .'/../../../Src/Data/AllExtensions.php';
        $this->assertNotEmpty(
            $extensions,
            'All extensions must be not empty'
        );
        foreach ($extensions as $ext => $value) {
            $this->assertCount(
                count($value),
                $value
            );
        }
    }
}
