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

use Pentagonal\WhoIs\Util\DataGetter;
use Pentagonal\WhoIs\Verifier;
use Pentagonal\WhoIs\WhoIs;
use PHPUnit\Framework\TestCase;

/**
 * Class WhoIsTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class WhoIsTest extends TestCase
{
    /**
     * @var WhoIs
     */
    protected $whoIs;

    /**
     * WhoIsTest constructor.
     *
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->whoIs = new WhoIs(new DataGetter());
    }

    /**
     * Test WhoIs Object
     * @uses Verifier
     * @uses WhoIs
     */
    public function testVerifierFromWhoIsObject()
    {
        $this->assertInstanceOf(
            Verifier::class,
            $this->whoIs->getVerifier(),
            sprintf(
                'Result of %s::getVerifier() must be instance of %s',
                WhoIs::class,
                Verifier::class
            )
        );
    }

    /**
     * Test Thrown throwable
     */
    public function testThrown()
    {
        try {
            $this->whoIs->getWhoIsServer('test.invalid');
        } catch (\Throwable $e) {
            $this->assertInstanceOf(
                \DomainException::class,
                $e,
                sprintf(
                    'Invalid result from %s::getWhoIsServer()  with value %s must be instance of %s',
                    WhoIs::class,
                    'test.invalid',
                    \DomainException::class
                )
            );
        }
    }

    /**
     * Test WhoIs Result check
     */
    public function testWhoIs()
    {
        try {
            $whoIsArray = $this->whoIs->getWhoIsWithArrayDetail(
                'google.com'
            );
            $this->assertNotEmpty(
                $whoIsArray,
                sprintf(
                    '%s::getWhoIsWithArrayDetail() with value `google.com` success must be return array',
                    WhoIs::class
                )
            );
            $this->assertNotEmpty(
                reset($whoIsArray),
                sprintf(
                    '%s::getWhoIsWithArrayDetail() with value `google.com` success must be return array',
                    WhoIs::class
                )
            );
            $counted = count($whoIsArray);
            if ($counted > 1) {
                $this->assertEquals(
                    $counted,
                    2,
                    'Google result use whois.markmonitor.com as whois server'
                    .' so its maybe if success will be returned 2 data array'
                );
                $this->assertNotSameSize(
                    reset($whoIsArray),
                    next($whoIsArray),
                    'Google result use whois.markmonitor.com as whois server'
                    .' so its maybe if success will be returned 2 data array with different result count'
                );
                $this->assertNotSame(
                    reset($whoIsArray),
                    next($whoIsArray),
                    'Google result use whois.markmonitor.com as whois server'
                    .' so its maybe if success will be returned 2 data array with different result'
                );
            }
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \Exception::class,
                $e,
                'Error there was problem.'
            );
        }
    }
}
