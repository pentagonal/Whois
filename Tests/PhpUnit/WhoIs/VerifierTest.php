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
use PHPUnit\Framework\TestCase;

/**
 * Class VerifierTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class VerifierTest extends TestCase
{
    /**
     * Test DataGetter instance
     */
    public function testInstance()
    {
        $verifier = new Verifier(new DataGetter());
        $this->assertInstanceOf(
            DataGetter::class,
            $verifier->getDataGetter(),
            sprintf(
                '%s::getDataGetter() must be instance of %s',
                Verifier::class,
                DataGetter::class
            )
        );
    }

    /**
     * Test Extension lists
     */
    public function testExtensionListArray()
    {
        $verifier = new Verifier(new DataGetter());
        $this->assertNotEmpty(
            $verifier->getDataGetter()->getTLDList(),
            sprintf(
                '%s::getDataGetter()->getTLDList() is array not empty',
                Verifier::class
            )
        );
        $this->assertNotEmpty(
            $verifier->getExtensionList(),
            sprintf(
                '%s::getExtensionList() is array not empty',
                Verifier::class
            )
        );
        $this->assertEquals(
            $verifier->getExtensionList(),
            $verifier->getDataGetter()->getTLDList(),
            sprintf(
                '%1$s::getExtensionList() is equals with %1$s::getDataGetter()->getTLDList()',
                Verifier::class
            )
        );
    }

    /**
     * Test for validation domain & email address
     */
    public function testValidationDomainAndEmail()
    {
        $verifier = new Verifier(new DataGetter());
        $this->assertTrue(
            $verifier->isDomain('example.id'),
            'example.id must be valid domain'
        );

        $this->assertTrue(
            $verifier->isTopDomain('example.id'),
            'example.id must be valid top level domain'
        );

        $this->assertTrue(
            $verifier->isDomain('example.co.id'),
            'example.co.id must be valid domain'
        );

        $this->assertTrue(
            $verifier->isTopDomain('example.co.id'),
            'example.co.id must be valid top level domain'
        );

        $this->assertTrue(
            $verifier->isEmail('admin@example.id'),
            'admin@example.id is must be valid email'
        );

        $this->assertTrue(
            $verifier->isEmail('admin@example.co.id'),
            'admin@example.co.id is must be valid email'
        );

        $this->assertFalse(
            $verifier->isEmail('admin----@example.co.id'),
            'admin----@example.co.id is must be not a valid email'
        );

        $this->assertFalse(
            $verifier->isEmail('admin@example'),
            'admin@example is must be not email'
        );

        $this->assertFalse(
            $verifier->isDomain('example.invalid'),
            'example.invalid must be not valid domain'
        );
    }

    /**
     * Test for sanitation & validation domain & email address
     */
    public function testDomainAndEmailSanity()
    {
        $verifier = new Verifier(new DataGetter());
        $domain = $verifier->validateDomain('examPle.ID');
        $this->assertNotEmpty(
            $domain,
            sprintf(
                '%s::ValidateDomain() with value `examPle.ID` must be valid and not empty',
                Verifier::class
            )
        );
        $this->assertEquals(
            $verifier->validateTopDomain('exAmple.iD'),
            $domain,
            sprintf(
                '%s::ValidateDomain() with value `exAmPle.iD` must be equals with %s',
                Verifier::class,
                sprintf(
                    '%s::ValidateDomain() with value `examPle.ID`',
                    Verifier::class
                )
            )
        );

        $this->assertFalse(
            $verifier->validateDomain('examPle.ID.ids'),
            sprintf(
                '%s::ValidateDomain() with value `examPle.ID.ids` must be not valid and return false',
                Verifier::class
            )
        );
        $this->assertFalse(
            $verifier->validateDomain('_examPledomain.cz'),
            sprintf(
                '%s::ValidateDomain() with value `_examPledomain.cz` must be not valid and return false',
                Verifier::class
            )
        );

        $this->assertEquals(
            $domain[Verifier::SELECTOR_DOMAIN_NAME],
            'example',
            sprintf(
                'Result %s::ValidateDomain() with value %s selector domain base must be return as lower case',
                Verifier::class,
                '`examPle.ID`'
            )
        );

        $this->assertNull(
            $domain[Verifier::SELECTOR_SUB_DOMAIN_NAME],
            sprintf(
                'Result %s::ValidateDomain() with value %s must be empty null type',
                Verifier::class,
                '`examPle.ID.ids`'
            )
        );

        $email = $verifier->validateEmail('admin@exampLE.com');
        $this->assertNotEmpty(
            $email,
            sprintf(
                '%s::validateEmail with value %s must be as array not empty',
                Verifier::class,
                'admin@exampLE.com'
            )
        );

        $this->assertArrayHasKey(
            Verifier::SELECTOR_EMAIL_NAME,
            $email
        );

        $this->assertEquals(
            $email[Verifier::SELECTOR_EMAIL_NAME],
            'admin',
            sprintf(
                '%s::validateEmail with value %s and email base address must be same with `admin`',
                Verifier::class,
                'admin@exampLE.com'
            )
        );
        $this->assertEquals(
            ($email[Verifier::SELECTOR_EMAIL_NAME]
                .'@'
                .$email[Verifier::SELECTOR_FULL_NAME]
                .'.'
                .$email[Verifier::SELECTOR_EXTENSION_NAME]
            ),
            'admin@example.com',
            sprintf(
                '%s::validateEmail with value %s and email base address must be same with `admin@example.com`',
                Verifier::class,
                'admin@exampLE.com'
            )
        );
    }

    /**
     * Test IPv4
     */
    public function testIPV4()
    {
        $verifier = new Verifier(new DataGetter());
        $this->assertTrue(
            $verifier->isIPv4('192.168.100.1'),
            sprintf(
                '%s::isIPV4() with value `192.168.100.1` must be valid IPV 4',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->isIPv4('::1'),
            '::1 is not an IPV 4'
        );

        $this->assertTrue(
            $verifier->isLocalIPv4('192.168.100.1'),
            sprintf(
                '%s::isLocalIPv4() with value `192.168.100.1` must be valid local Ipv4',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->isLocalIPv4('8.8.8.8'),
            '8.8.8.8 is not a local IPV4'
        );
    }

    /**
     * Test Constant
     */
    public function testConstant()
    {
        $this->assertEquals(
            Verifier::SELECTOR_EMAIL_NAME,
            'email_name'
        );
        $this->assertEquals(
            Verifier::SELECTOR_FULL_NAME,
            'domain_name'
        );
        $this->assertEquals(
            Verifier::SELECTOR_DOMAIN_NAME,
            'domain_name_base'
        );
        $this->assertEquals(
            Verifier::SELECTOR_EXTENSION_NAME,
            'domain_extension'
        );
        $this->assertEquals(
            Verifier::SELECTOR_SUB_DOMAIN_NAME,
            'sub_domain'
        );
        $this->assertStringStartsWith(
            '~^',
            Verifier::IPV4_LOCAL_REGEX
        );
        $this->assertStringEndsWith(
            '~x',
            Verifier::IPV4_LOCAL_REGEX
        );
        $this->assertStringStartsWith(
            '~^',
            Verifier::IPV4_REGEX
        );
        $this->assertStringEndsWith(
            '~x',
            Verifier::IPV4_REGEX
        );
        $this->assertStringStartsWith(
            '/',
            Verifier::REGEX_GLOBAL
        );
        $this->assertStringEndsWith(
            '/x',
            Verifier::REGEX_GLOBAL
        );
    }

    /**
     * Test All context
     */
    public function testAllContext()
    {
        $verifier = new Verifier(new DataGetter());

        $this->assertFalse(
            $verifier->isIPv4('::1')
        );
        $this->assertFalse(
            $verifier->isIPv4('12345678911234567')
        );
        $this->assertFalse(
            $verifier->isIPv4(true)
        );
        $this->assertTrue(
            $verifier->isIPv4('127.0.0.1')
        );

        $this->assertFalse(
            $verifier->isLocalIPv4('8.8.8.8')
        );
        $this->assertFalse(
            $verifier->isLocalIPv4(true)
        );
        $this->assertFalse(
            $verifier->isLocalIPv4('12345678911234567')
        );
        $this->assertTrue(
            $verifier->isLocalIPv4('127.0.0.1')
        );
        $this->assertFalse(
            $verifier->isTopDomain('example.invalid')
        );
        $this->assertTrue(
            $verifier->isTopDomain('example.com')
        );
        $this->assertArrayHasKey(
            Verifier::SELECTOR_DOMAIN_NAME,
            $verifier->validateDomain('example.com')
        );
        $this->assertFalse(
            $verifier->validateDomain(true)
        );
        $this->assertFalse(
            $verifier->validateDomain(' ')
        );
        $this->assertFalse(
            $verifier->validateDomain(str_repeat('A', 256))
        );

        $this->assertFalse(
            $verifier->isDomain('example.invalid')
        );
        $this->assertTrue(
            $verifier->isDomain('example.com')
        );
        $this->assertEquals(
            'example.com',
            $verifier->sanitizeDomain('ExampLe.com')
        );
        $this->assertFalse(
            $verifier->sanitizeDomain('domain.invalid')
        );
        $this->assertFalse(
            $verifier->sanitizeASN('Invalid')
        );
        $this->assertFalse(
            $verifier->sanitizeASN(true)
        );
        $this->assertFalse(
            $verifier->sanitizeASN('ABC12345')
        );
        $this->assertEquals(
            $verifier->sanitizeASN('AS12345'),
            '12345'
        );
        $this->assertEquals(
            $verifier->sanitizeASN('12345'),
            '12345'
        );
        $extensionInvalid = $verifier->getExtensionIDN(true);
        $this->assertFalse(
            $extensionInvalid
        );
        $this->assertEquals(
            $extensionInvalid,
            false
        );

        $this->assertFalse(
            $verifier->getExtensionIDN('       a')
        );
        $this->assertFalse(
            $verifier->getExtensionIDN('invalid')
        );
        $this->assertEquals(
            $verifier->getExtensionIDN('com'),
            'com'
        );
        $this->assertFalse(
            $verifier->isEmail('__-admin@example.com')
        );
        $this->assertTrue(
            $verifier->isEmail('admin@example.com')
        );
        $this->assertTrue(
            $verifier->isExtensionExist('com')
        );
        $this->assertFalse(
            $verifier->isExtensionExist('invalid')
        );
        $this->assertArrayHasKey(
            Verifier::SELECTOR_EMAIL_NAME,
            $verifier->validateEmail('admin@example.com')
        );
        $this->assertFalse(
            $verifier->validateEmail('admin.invalid@example')
        );
        $this->assertFalse(
            $verifier->validateEmail(true)
        );
        $this->assertFalse(
            $verifier->validateEmail('ABC.d')
        );
        $this->assertFalse(
            $verifier->validateEmail('@example_email_invalid.com')
        );
        $this->assertFalse(
            $verifier->validateEmail('example_email_invalid.com@')
        );
        $this->assertFalse(
            $verifier->validateEmail('invalid@sub.gmail.com')
        );

        $this->assertFalse(
            $verifier->sanitizeEmail('admin.invalid@example')
        );
        $this->assertEquals(
            'admin@example.com',
            $verifier->sanitizeEmail('admin@example.com')
        );
        $this->assertInstanceOf(
            DataGetter::class,
            $verifier->getDataGetter()
        );
        $this->assertEquals(
            $verifier->getDataGetter()->getTLDList(),
            $verifier->getExtensionList()
        );
        $this->assertArrayHasKey(
            'com',
            $verifier->getExtensionList()
        );
    }
}
