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

namespace Pentagonal\WhoIs\Interfaces;

/**
 * Interface RecordASNNetworkInterface
 * @package Pentagonal\WhoIs\Interfaces
 */
interface RecordASNNetworkInterface extends RecordNetworkInterface
{
    const NAME_ASN_ADDRESS = 'ASN_ADDRESS';
    const NAME_ASN_TYPE    = 'ASN_TYPE';
    const NAME_ASN_32      = 'ASN_32_BIT';
    const NAME_ASN_16      = 'ASN_16_BIT';

    const NAME_IS_RESERVED = 'IS_RESERVED';
    const NAME_IS_RESERVED_PRIVATE = 'IS_PRIVATE';
    const NAME_IS_RESERVED_SAMPLE  = 'IS_SAMPLE';
    const NAME_IS_UNALLOCATED      = 'IS_UNALLOCATED';

    /**
     * Get ASN Number
     *
     * @return string
     */
    public function getASNumber() : string;

    /**
     * Check if ASN is Reserved
     *
     * @return bool
     */
    public function isReserved() : bool;

    /**
     * Check if ASN is Reserved And for private
     *
     * @return bool
     */
    public function isReservedPrivate() : bool;

    /**
     * Check if ASN is Reserved And for sample
     *
     * @return bool
     */
    public function isReservedSample() : bool;

    /**
     * Check if ASN is Unallocated
     *
     * @return bool
     */
    public function isUnallocated() : bool;
}
