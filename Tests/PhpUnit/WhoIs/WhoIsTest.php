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

use Pentagonal\WhoIs\ArrayCache;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\Util\Collection;
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
        $this->whoIs = new WhoIs(new DataGetter(), new ArrayCache());
    }

    /**
     * Test Thrown throwable
     */
    public function testThrown()
    {
        try {
            $this->whoIs->getWhoIsServer('test.invalid');
        } catch (\DomainException $e) {
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
                    '%s::getWhoIsWithArrayDetail() with value `google.com` success must be return iterable',
                    WhoIs::class
                )
            );
            $this->assertNotEmpty(
                reset($whoIsArray),
                sprintf(
                    '%s::getWhoIsWithArrayDetail() with value `google.com` success must be return iterable',
                    WhoIs::class
                )
            );
            $this->assertInstanceOf(
                \ArrayObject::class,
                $whoIsArray,
                sprintf(
                    '%1$s::getWhoIsWithArrayDetail() with value `google.com` success must be return %2$s',
                    WhoIs::class,
                    \ArrayObject::class
                )
            );
            $whoIsArraySecond = $this->whoIs->getWhoIsWithArrayDetail(
                'google.com',
                true
            );
            $this->assertNotEquals(
                $whoIsArray->last(),
                $whoIsArraySecond->last()
            );

            $counted = count($whoIsArraySecond);
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
                $this->assertTrue(
                    $this->whoIs->isDomainRegistered('google.com'),
                    'Use method isDomainRegistered() Google.com with result is true'
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

    public function testOtherContext()
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

        $this->assertInstanceOf(
            CacheInterface::class,
            $this->whoIs->getCache()
        );

        $result = $this->whoIs->getWhoIs('google.com');
        $this->assertInstanceOf(
            Collection::class,
            $result,
            sprintf(
                'Get whois mustbe instanceof %s',
                Collection::class
            )
        );
        $resultSecond = $this->whoIs->getWhoIs('google.com', true);
        $this->assertNotEquals(
            $result->first(),
            $resultSecond->first(),
            'Clean result and result is not same'
        );

        $resultThird = $this->whoIs->getWhoIs('google.com', true, true);
        if (count($resultThird) > 1) {
            $this->assertNotEquals(
                $resultThird->first(),
                $resultThird->last(),
                'Clean result and result is not same'
            );

            $this->assertNotEquals(
                $resultThird->first(),
                $resultThird->next(),
                'Clean result and result is not same'
            );

            $this->assertNotEquals(
                $resultThird->current(),
                $resultThird->prev(),
                'Clean result and result is not same'
            );
        }

        $this->assertJson(
            json_encode($result),
            'Result of who is is json serialize-able'
        );
        $this->assertEquals(
            json_encode($result),
            json_encode($result->getArrayCopy()),
            'Result of who is is json serialize-able same with array copy'
        );
        $this->assertEquals(
            json_encode($result),
            json_encode((array) $result),
            'Result of who is is json serialize-able same with array copy'
        );
        $whoIsServer = $this->whoIs->parseWhoIsServerFromData($result->first());
        $this->assertContains(
            'whois.',
            $whoIsServer,
            'Google use whois.markmonitor.com for followed whois.'
        );

        $parsedServer = $this->whoIs->parseWhoIsServer($whoIsServer);
        $this->assertContains(
            ':'.WhoIs::SERVER_PORT,
            $parsedServer,
            'Parsed Whois server for '
        );
        $this->assertArrayHasKey(
            'com',
            $this->whoIs->getTemporaryCachedWhoIsServers()
        );
        try {
            WhoIs::cleanResultData(null);
        } catch (\InvalidArgumentException $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
        // clean
        $data = WhoIs::cleanResultData('URL of the ICANN Whois Inaccuracy Complaint Form: ');
        $this->assertEquals(
            '',
            $data
        );
        // clean
        $data = WhoIs::cleanResultData('# Text Must Be deleted');
        $this->assertEquals(
            '',
            $data
        );
        // clean
        $data = WhoIs::cleanResultData('% Text Must Be deleted');
        $this->assertEquals(
            '',
            $data
        );
        // clean
        $data = WhoIs::cleanResultData('>>> Text Must Be deleted');
        $this->assertEquals(
            '',
            $data
        );

        $this->assertNotEmpty(
            $this->whoIs->getASNData('1234')
        );

        $this->assertInstanceOf(
            Collection::class,
            $this->whoIs->getASNWithArrayDetail('1234')
        );

        $this->assertInstanceOf(
            Collection::class,
            $this->whoIs->getASNWithArrayDetail('1234', true)
        );

        try {
            $this->whoIs->getASNData('BBB1234');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }

        try {
            $this->whoIs->getIpData('domain.invalid');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }

        try {
            $this->whoIs->getIpData('123456');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \UnexpectedValueException::class,
                $e
            );
        }

        $this->assertInstanceOf(
            Collection::class,
            $this->whoIs->getIpData('8.8.8.8')
        );

        $this->assertInstanceOf(
            Collection::class,
            $this->whoIs->getIpData('8.8.8.8', true)
        );

        $this->assertInstanceOf(
            Collection::class,
            $this->whoIs->getIPWithArrayDetail('8.8.8.8')
        );
        $this->assertTrue(
            $this->whoIs->isDomainRegistered('google.com')
        );
        try {
            $this->whoIs->isDomainRegistered(true);
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
        try {
            $this->whoIs->isDomainRegistered('domain');
        } catch (\Exception $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }

        $this->whoIs->__destruct();
    }
}
