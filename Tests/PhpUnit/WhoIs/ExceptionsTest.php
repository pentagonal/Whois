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

use Pentagonal\WhoIs\Exceptions\ConnectionException;
use Pentagonal\WhoIs\Exceptions\ConnectionRefuseException;
use Pentagonal\WhoIs\Exceptions\HttpBadAddressException;
use Pentagonal\WhoIs\Exceptions\HttpException;
use Pentagonal\WhoIs\Exceptions\HttpExpiredException;
use Pentagonal\WhoIs\Exceptions\HttpPermissionException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use PHPUnit\Framework\TestCase;

/**
 * Class ExceptionsTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class ExceptionsTest extends TestCase
{
    public function testExceptions()
    {
        $this->assertInstanceOf(
            HttpException::class,
            new ConnectionException()
        );
        $this->assertInstanceOf(
            ConnectionException::class,
            new ConnectionRefuseException()
        );
        $this->assertInstanceOf(
            ConnectionException::class,
            new TimeOutException()
        );

        $this->assertInstanceOf(
            \Exception::class,
            new HttpException()
        );

        $this->assertInstanceOf(
            HttpException::class,
            new HttpBadAddressException()
        );
        $this->assertInstanceOf(
            HttpException::class,
            new HttpExpiredException()
        );
        $this->assertInstanceOf(
            HttpException::class,
            new HttpPermissionException()
        );
    }
}
