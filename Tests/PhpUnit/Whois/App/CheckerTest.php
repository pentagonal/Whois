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

use Pentagonal\WhoIs\App\ArrayCacheCollector;
use Pentagonal\WhoIs\App\Checker;
use Pentagonal\WhoIs\App\Validator;
use Pentagonal\WhoIs\App\WhoIsRequest;
use PHPUnit\Framework\TestCase;

/**
 * Class CheckerTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs\App
 */
class CheckerTest extends TestCase
{
    public function testGetRequestWithServer()
    {
        $whois = new Checker(new Validator(), new ArrayCacheCollector());
        $request = $whois->getRequest('google.com', 'whois.verisign-grs.com');
        $this->assertInstanceOf(
            WhoIsRequest::class,
            $request
        );
        $this->assertInstanceOf(
            Validator::class,
            $whois->getValidator()
        );
    }
}
