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
    public function testInstanceFromDataGetter()
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
    public function testExtensionListArrayFromDataGetterAndMethodGetTLDList()
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
     * Test Domain validation
     */
    public function testIsValidDomain()
    {
        $verifier = new Verifier(new DataGetter());

        /**
         * Test Domain Validity
         */
        $this->assertTrue(
            $verifier->isDomain('example.id'),
            'example.id must be valid domain'
        );

        $this->assertFalse(
            $verifier->isDomain('example.invalid'),
            'example.invalid is not a valid domain'
        );

        /**
         * Test Top Domain
         */
        $this->assertTrue(
            $verifier->isTopDomain('example.id'),
            'example.id must be valid top level domain'
        );

        /**
         * Test Top Domain
         */
        $this->assertTrue(
            $verifier->isTopDomain('example.com'),
            'example.com must be valid top level domain'
        );

        // tes for sub TLD
        $this->assertTrue(
            $verifier->isTopDomain('example.co.id'),
            'example.co.id must be valid top level domain'
        );
        // tes for invalid Top Domain
        $this->assertFalse(
            $verifier->isTopDomain('example.invalid.id'),
            'example.invalid.id is not a valid top domain'
        );
    }

    /**
     * Test for validation email address
     */
    public function testIsValidEmail()
    {
        $verifier = new Verifier(new DataGetter());

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
    }

    /**
     * Test Validation of Domain
     */
    public function testDomainValidation()
    {
        $verifier = new Verifier(new DataGetter());
        $this->assertFalse(
            $verifier->validateDomain(true),
            sprintf(
                '%s::validateDomain() must be as a string',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateDomain(' '),
            sprintf(
                '%s::validateDomain can not contains whitespace only',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateDomain(str_repeat('A', 256)),
            sprintf(
                '%s::validateDomain() can not more than 255 characters length',
                Verifier::class
            )
        );

        // valid domain
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

        $this->assertArrayHasKey(
            Verifier::SELECTOR_DOMAIN_NAME,
            $domain,
            sprintf(
                'Result of %1$s::ValidateDomain() of `examPle.ID` must be contain %1$s::SELECTOR_DOMAIN_NAME',
                Verifier::class
            )
        );

        $this->assertArrayHasKey(
            Verifier::SELECTOR_SUB_DOMAIN_NAME,
            $domain,
            sprintf(
                'Result of %1$s::ValidateDomain() of `examPle.ID` must be contain %1$s::SELECTOR_SUB_DOMAIN_NAME',
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
                '`examPle.ID.id`'
            )
        );

        // invalid domain
        $this->assertFalse(
            $verifier->validateDomain('examPle.ID.ids'),
            sprintf(
                '%s::ValidateDomain() with value `examPle.ID.ids` must be invalid and return false',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateDomain('_examPledomain.cz'),
            sprintf(
                '%s::ValidateDomain() with value `_examPledomain.cz` must be invalid and return false',
                Verifier::class
            )
        );

        /**
         * Sanitize Domain Name
         */
        $this->assertEquals(
            'example.com',
            $verifier->sanitizeDomain('ExampLe.com'),
            sprintf(
                '%s::sanitizeDomain() with value `ExampLe.com` must be equals with example.com',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->sanitizeDomain('domain.invalid'),
            sprintf(
                '%s::sanitizeDomain() with value `domain.invalid` must be return false',
                Verifier::class
            )
        );
    }

    /**
     * Test for sanitation & validation domain & email address
     */
    public function testEmailValidation()
    {
        $verifier = new Verifier(new DataGetter());

        $this->assertFalse(
            $verifier->validateEmail(true),
            sprintf(
                '%s::validateEmail() value must be as a string',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->isEmail('__-admin@example.com'),
            '__-admin@example.com is not a valid email and return false'
        );

        $this->assertTrue(
            $verifier->isEmail('admin@example.com'),
            'admin@example.com is a valid email returning boolean'
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

        $this->assertArrayHasKey(
            Verifier::SELECTOR_EMAIL_NAME,
            $verifier->validateEmail('admin@example.com'),
            sprintf(
                '%1$s::validateEmail() is returning array if valid with contain key %1$s::SELECTOR_EMAIL_NAME',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateEmail('admin.invalid@example'),
            sprintf(
                '%s::validateEmail() with value `admin.invalid@example` is not a valid email',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateEmail('ABC.id'),
            sprintf(
                '%s::validateEmail() with value `ABC.id` is not a valid email',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateEmail('@example_email_invalid.com'),
            sprintf(
                '%s::validateEmail() email can not start with @',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateEmail('example_email_invalid.com@'),
            sprintf(
                '%s::validateEmail() email can not end with @',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->validateEmail('invalid@sub.gmail.com'),
            sprintf(
                '%s::validateEmail() common email eg : gmail have no sub domain',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->sanitizeEmail('admin.invalid@example'),
            sprintf(
                '%s::sanitizeEmail() with value `admin.invalid@example` is invalid and returning false',
                Verifier::class
            )
        );
        $this->assertEquals(
            'admin@example.com',
            $verifier->sanitizeEmail('admin@eXAMPLE.COM'),
            sprintf(
                '%s::sanitizeEmail() with value `admin@eXAMPLE.COM` is equals with `admin@example.com`',
                Verifier::class
            )
        );
    }

    /**
     * Test IPv4
     */
    public function testIPV4()
    {
        $verifier = new Verifier(new DataGetter());

        $this->assertFalse(
            $verifier->isIPv4(true),
            'IPv4 must be as a string'
        );

        $this->assertTrue(
            $verifier->isIPv4('192.168.100.1'),
            sprintf(
                '%s::isIPV4() with value `192.168.100.1` must be valid IPV 4',
                Verifier::class
            )
        );

        $this->assertTrue(
            $verifier->isIPv4('127.0.0.1'),
            '127.0.0.1 is local address also valid as IPv4'
        );

        $this->assertFalse(
            $verifier->isIPv4('::1'),
            '::1 is not an IPV 4'
        );

        $this->assertFalse(
            $verifier->isIPv4('123.456.789.112.345.67'),
            'Length of IP must be less than 15 characters length'
        );

        /**
         * Local
         */
        $this->assertTrue(
            $verifier->isLocalIPv4('192.168.100.1'),
            sprintf(
                '%s::isLocalIPv4() with value `192.168.100.1` must be valid local Ipv4',
                Verifier::class
            )
        );

        $this->assertTrue(
            $verifier->isLocalIPv4('127.0.0.1'),
            sprintf(
                '%s::isLocalIPv4() with value `127.0.0.1` must be valid local Ipv4',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->isLocalIPv4(true),
            'Local IPv4 must be as a string'
        );

        $this->assertFalse(
            $verifier->isLocalIPv4('8.8.8.8'),
            '8.8.8.8 is not a local IPV4'
        );

        $this->assertFalse(
            $verifier->isLocalIPv4('123.456.789.112.345.67'),
            'Length of IP must be less than 15 characters length'
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
     * Test ASN
     */
    public function testASNValidation()
    {
        $verifier = new Verifier(new DataGetter());

        $this->assertFalse(
            $verifier->sanitizeASN(true),
            sprintf(
                '%s::sanitizeASN() must be numeric or string numeric',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->sanitizeASN('Invalid'),
            sprintf(
                '%s::sanitizeASN() with value `invalid` must be return false',
                Verifier::class
            )
        );


        $this->assertFalse(
            $verifier->sanitizeASN('ABC12345'),
            sprintf(
                '%s::sanitizeASN() must be start with `AS` or `ASN` or numeric',
                Verifier::class
            )
        );

        $this->assertEquals(
            $verifier->sanitizeASN('AS12345'),
            '12345',
            sprintf(
                '%s::sanitizeASN() with value `AS12345` is a valid ASN with return `12345`',
                Verifier::class
            )
        );

        $this->assertEquals(
            $verifier->sanitizeASN('ASN12345'),
            '12345',
            sprintf(
                '%s::sanitizeASN() with value `ASN12345` is a valid ASN with return `12345`',
                Verifier::class
            )
        );

        $this->assertEquals(
            $verifier->sanitizeASN('12345'),
            '12345',
            sprintf(
                '%s::sanitizeASN() with value string `12345` is a valid ASN with return `12345`',
                Verifier::class
            )
        );

        $this->assertEquals(
            $verifier->sanitizeASN(12345),
            '12345',
            sprintf(
                '%s::sanitizeASN() with value integer `12345` is a valid ASN with return `12345`',
                Verifier::class
            )
        );
    }

    /**
     * Test Extension IDN
     */
    public function testExtensionIDN()
    {
        $verifier = new Verifier(new DataGetter());
        $this->assertFalse(
            $verifier->getExtensionIDN(true),
            sprintf(
                '%s::getExtensionIDN() must be value string',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->getExtensionIDN('       a '),
            sprintf(
                '%s::getExtensionIDN() with white space on '
                    . 'start or end will be trimming and character must be more than 1',
                Verifier::class
            )
        );

        $this->assertFalse(
            $verifier->getExtensionIDN('invalid'),
            '`invalid` is not a valid Extension'
        );
        $this->assertEquals(
            $verifier->getExtensionIDN('COM'),
            'com',
            '`COM` is equals with `com` of valid Extension'
        );

        /**
         * Extension list
         */
        $this->assertTrue(
            $verifier->isExtensionExist('com'),
            'com is on extension list and exists'
        );

        $this->assertFalse(
            $verifier->isExtensionExist('invalid'),
            '`invalid` is not a valid extension and does not exists'
        );
    }
}
