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
     * @var Verifier
     */
    protected $verifier;

    /**
     * VerifierTest constructor.
     *
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->verifier = new Verifier(new DataGetter());
    }

    /**
     * Test Getter @uses DataGetter
     */
    public function testInstanceGetter()
    {
        $this->assertInstanceOf(
            DataGetter::class,
            $this->verifier->getDataGetter(),
            sprintf(
                '%s::getDataGetter() must be instance of %s',
                Verifier::class,
                DataGetter::class
            )
        );
    }

    /**
     * Test Extension lists
     * @uses DataGetter::getTLDList()
     * @uses Verifier::getExtensionList()
     */
    public function testExtensionListArray()
    {
        $this->assertEquals(
            $this->verifier->getExtensionList(),
            $this->verifier->getDataGetter()->getTLDList(),
            sprintf(
                '%s::getExtensionList() must be equals with %s::getTLDList()',
                Verifier::class,
                DataGetter::class
            )
        );
    }

    /**
     * Test for validation domain & email address
     * @uses Verifier
     */
    public function testValidationDomainAndEmail()
    {
        $this->assertTrue(
            $this->verifier->isDomain('example.id'),
            'example.id must be valid domain'
        );

        $this->assertTrue(
            $this->verifier->isTopDomain('example.id'),
            'example.id must be valid top level domain'
        );

        $this->assertTrue(
            $this->verifier->isDomain('example.co.id'),
            'example.co.id must be valid domain'
        );

        $this->assertTrue(
            $this->verifier->isTopDomain('example.co.id'),
            'example.co.id must be valid top level domain'
        );

        $this->assertTrue(
            $this->verifier->isEmail('admin@example.id'),
            'admin@example.id is must be valid email'
        );

        $this->assertTrue(
            $this->verifier->isEmail('admin@example.co.id'),
            'admin@example.co.id is must be valid email'
        );

        $this->assertFalse(
            $this->verifier->isEmail('admin----@example.co.id'),
            'admin----@example.co.id is must be not a valid email'
        );

        $this->assertFalse(
            $this->verifier->isEmail('admin@example'),
            'admin@example is must be not email'
        );

        $this->assertFalse(
            $this->verifier->isDomain('example.invalid'),
            'example.invalid must be not valid domain'
        );
    }

    /**
     * Test for sanitation & validation domain & email address
     * @uses Verifier
     */
    public function testDomainAndEmailSanity()
    {
        $domain = $this->verifier->validateDomain('examPle.ID');
        $this->assertNotEmpty(
            $domain,
            sprintf(
                '%s::ValidateDomain() with value `examPle.ID` must be valid and not empty',
                Verifier::class
            )
        );
        $this->assertEquals(
            $this->verifier->validateTopDomain('exAmple.iD'),
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
            $this->verifier->validateDomain('examPle.ID.ids'),
            sprintf(
                '%s::ValidateDomain() with value `examPle.ID.ids` must be not valid and return false',
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

        $email = $this->verifier->validateEmail('admin@exampLE.com');
        $this->assertNotEmpty(
            $email,
            sprintf(
                '%s::validateEmail with value %s must be as array not empty',
                Verifier::class,
                'admin@exampLE.com'
            )
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
     * @uses Verifier
     */
    public function testIPV4()
    {
        $this->assertTrue(
            $this->verifier->isIPv4('192.168.100.1'),
            sprintf(
                '%s::isIPV4() with value `192.168.100.1` must be valid IPV 4',
                Verifier::class
            )
        );
        $this->assertTrue(
            $this->verifier->isLocalIPv4('192.168.100.1'),
            sprintf(
                '%s::isLocalIPv4() with value `192.168.100.1` must be valid local Ipv4',
                Verifier::class
            )
        );
    }
}
