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

namespace Pentagonal\WhoIs\Util;

use Pentagonal\WhoIs\Interfaces\TransCodeInterface;
use TrueBV\Punycode;

/**
 * Class Puny
 * @package Pentagonal\WhoIs\Util
 */
class Puny implements TransCodeInterface
{
    /**
     * @var Punycode
     */
    protected $puny;

    /**
     * @var bool
     */
    protected $isIntlEnabled = false;

    /**
     * Puny constructor.
     */
    public function __construct()
    {
        $this->puny = new Punycode();
        $this->isIntlEnabled = function_exists('idn_to_ascii')
            && function_exists('idn_to_utf8');
    }

    /**
     * Encode Puny Code
     *
     * @param string $string
     *
     * @return string
     */
    public function encode(string $string) : string
    {
        if (trim($string) === '') {
            return $string;
        }

        return $this->isIntlEnabled
            ? (idn_to_ascii($string)?: $string)
            : $this->puny->encode($string);
    }

    /**
     * Decode Puny Code
     *
     * @param string $string
     *
     * @return string
     */
    public function decode(string $string) : string
    {
        if (trim($string) === '') {
            return $string;
        }

        return $this->isIntlEnabled
            ? (idn_to_utf8($string)?: $string)
            : $this->puny->encode($string);
    }
}
