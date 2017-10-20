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
 * Interface RecordIPNetworkInterface
 * @package Pentagonal\WhoIs\Interfaces
 */
interface RecordIPNetworkInterface extends RecordNetworkInterface
{
    const NAME_IS_IPV4     = 'IS_IPV_4';
    const NAME_IS_IPV6     = 'IS_IPV_6';
    const NAME_IS_LOCAL_IP = 'IS_LOCAL_IP';
    const NAME_IP_ADDRESS  = 'IP_ADDRESS';
    const NAME_IS_RESERVED = 'IS_RESERVED';
    const NAME_IS_RESERVED_PRIVATE = 'IS_PRIVATE';
    const NAME_IS_RESERVED_FUTURE = 'IS_FUTURE';

    /**
     * Get IP Address
     *
     * @return string
     */
    public function getIPAddress() : string;

    /**
     * If is Local IP
     *
     * @return bool
     */
    public function isLocalIP() : bool;

    /**
     * If is IPV 4
     *
     * @return bool
     */
    public function IPv4() : bool;

    /**
     * @return bool
     */
    public function IPv6() : bool;

    /**
     * Check if IP is Reserved
     *
     * @return bool
     */
    public function isReserved() : bool;

    /**
     * Check if IP is Reserved Private
     *
     * @return bool
     */
    public function isReservedPrivate() : bool;

    /**
     * Check if IP is Reserved Future Usage
     *
     * @return bool
     */
    public function isReservedFuture() : bool;
}
