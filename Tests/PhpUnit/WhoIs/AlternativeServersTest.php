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
 * Class AlternativeServersTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class AlternativeServersTest extends TestCase
{
    /**
     * Test Alternative Server
     */
    public function testAlternativeServerReturn()
    {
        $dataArray = require __DIR__ .'/../../../Src/Data/AlternativeServers.php';
        $this->assertNotEmpty(
            $dataArray,
            'Alternative Must be not empty'
        );
        foreach ($dataArray as $key => $value) {
            $this->assertNotEmpty(
                $value,
                sprintf(
                    'Extension %s on alternative server must be not empty',
                    $key
                )
            );
        }
    }
}
