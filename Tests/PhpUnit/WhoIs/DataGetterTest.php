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
use PHPUnit\Framework\TestCase;

/**
 * Class DataGetterTest
 * @package Pentagonal\Tests\PhpUnit\WhoIs
 */
class DataGetterTest extends TestCase
{
    /**
     * Test Instance Invalid
     */
    public function testInstance()
    {
        try {
            // must be thrown
            new DataGetter(false);
        } catch (\InvalidArgumentException $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }
    }

    /**
     * Test Extension lists that is not empty data
     */
    public function testExtensionLists()
    {
        $getter = new DataGetter();
        $this->assertNotEmpty(
            $getter->getTLDList(),
            'DataGetter::getTLDList() must be not empty as default'
        );
    }

    /**
     * Test Constant
     */
    public function testConstant()
    {
        $this->assertStringStartsWith(
            'https://data.iana.org/TLD/',
            DataGetter::BASE_ORG_TLD_ALPHA_URL,
            sprintf(
                '%s::BASE_ORG_TLD_ALPHA_URL is https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
                DataGetter::class
            )
        );

        $this->assertSame(
            'https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
            DataGetter::BASE_ORG_TLD_ALPHA_URL,
            sprintf(
                '%s::BASE_ORG_TLD_ALPHA_URL is https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
                DataGetter::class
            )
        );
        $this->assertStringStartsWith(
            'whois.iana.org',
            DataGetter::BASE_ORG_URL,
            sprintf(
                '%s::BASE_ORG_URL is whois.iana.org',
                DataGetter::class
            )
        );

        $this->assertSame(
            'whois.iana.org',
            DataGetter::BASE_ORG_URL,
            sprintf(
                '%s::BASE_ORG_URL is whois.iana.org',
                DataGetter::class
            )
        );
        $this->assertStringStartsWith(
            'https://publicsuffix.org/list/',
            DataGetter::TLD_PUBLIC_SUFFIX_URL,
            sprintf(
                '%s::TLD_PUBLIC_SUFFIX_URL is https://publicsuffix.org/list/effective_tld_names.dat',
                DataGetter::class
            )
        );

        $this->assertSame(
            'https://publicsuffix.org/list/effective_tld_names.dat',
            DataGetter::TLD_PUBLIC_SUFFIX_URL,
            sprintf(
                '%s::TLD_PUBLIC_SUFFIX_URL is whois.iana.org',
                DataGetter::class
            )
        );
    }

    public function testOtherContext()
    {
        $getter = new DataGetter();
        $this->assertArrayHasKey(
            'com',
            $getter->getTLDList()
        );
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $void = $getter->setTldList([]);
        $this->assertEmpty(
            $void
        );
        $this->assertEmpty(
            $getter->getTLDList()
        );
    }
    /**
     * Test set & unset extensions
     */
    public function testExtensionSet()
    {
        $getter = new DataGetter();
        $newGetter = new DataGetter();
        $extension = $getter->createNewRecordExtension();
        $newTld = $newGetter->getTLDList();
        $this->assertEquals(
            $getter->getTLDList(),
            $extension
        );
        $this->assertArrayHasKey(
            'net',
            $extension,
            'Create new Record Extensions'
        );
        $this->assertArrayHasKey(
            'net',
            $newTld,
            'Create new Record Extensions'
        );
        unset($newTld['net']);

        try {
            $newGetter->setTldList('string');
        } catch (\InvalidArgumentException $e) {
            $this->assertInstanceOf(
                \InvalidArgumentException::class,
                $e
            );
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $setVoid = $newGetter->setTldList($newTld);
        $this->assertEmpty(
            $setVoid,
            sprintf(
                '%s::setTldList is returning void',
                DataGetter::class
            )
        );
        $this->assertArrayNotHasKey(
            'net',
            $newGetter->getTLDList(),
            'DataGetter::getTLDList() must be not contain `net` as key after unset'
        );
        $this->assertNotSameSize(
            $getter->getTLDList(),
            $newGetter->getTLDList(),
            'DataGetter::getTLDList() must be not same counted as key after unset'
        );

        // set default to false
        $getter->setDefault(false);
        $tmpPath = sys_get_temp_dir() ?: __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
        $tmpPath = $tmpPath .'/extensionDir';
        $jsonPath = $tmpPath . '/extension_list_tmp.json';
        if (file_exists($jsonPath)) {
            unlink($jsonPath);
        }

        if (is_dir($tmpPath)) {
            rmdir($tmpPath);
        }

        $newGetter = new DataGetter($jsonPath);
        $this->assertEquals(
            $newGetter->getTLDList(),
            $getter->createNewRecordExtension()
        );
    }
}
