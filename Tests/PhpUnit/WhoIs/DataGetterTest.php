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
     * @var DataGetter
     */
    protected $getter;

    /**
     * DataGetterTest constructor.
     *
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->getter = new DataGetter();
    }

    /**
     * Test Extension lists that is not empty data
     */
    public function testExtensionLists()
    {
        $this->assertNotEmpty(
            $this->getter,
            'DataGetter::getTLDList() must be not empty as default'
        );
    }

    /**
     * Test set & unset extensions
     */
    public function testExtensionSet()
    {
        $newGetter = clone $this->getter;
        $newTld = $newGetter->getTLDList();
        unset($newTld['net']);
        $newGetter->setTldList($newTld);
        $this->assertArrayNotHasKey(
            'net',
            $newGetter->getTLDList(),
            'DataGetter::getTLDList() must be not contain `net` as key after unset'
        );

        $this->assertNotSameSize(
            $this->getter->getTLDList(),
            $newGetter->getTLDList(),
            'DataGetter::getTLDList() must be not same counted as key after unset'
        );
    }
}
