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

namespace Pentagonal\WhoIs\Record;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\App\WhoIsRequest;
use Pentagonal\WhoIs\Interfaces\RecordIPNetworkInterface;
use Pentagonal\WhoIs\Util\DataParser;

/**
 * Class IPRecord
 * @package Pentagonal\WhoIs\Record
 */
class IPRecord extends ArrayCollector implements RecordIPNetworkInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPointer() : string
    {
        return $this->getIPAddress();
    }

    /**
     * {@inheritdoc}
     */
    public function getWhoIsServers() : array
    {
        return (array) $this[self::WHOIS_SERVER];
    }

    /**
     * {@inheritdoc}
     */
    public function getIPAddress() : string
    {
        return $this[self::NAME_IP_ADDRESS];
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalIP() : bool
    {
        return $this[self::NAME_IS_LOCAL_IP] === true;
    }

    /**
     * {@inheritdoc}
     */
    public function IPv4() : bool
    {
        return $this[self::NAME_IS_IPV4] === true;
    }

    /**
     * {@inheritdoc}
     */
    public function IPv6() : bool
    {
        return $this[self::NAME_IS_IPV6] === true;
    }

    /**
     * {@inheritdoc}
     */
    public function isReserved() : bool
    {
        if ($this[self::NAME_IS_RESERVED] !== true) {
            $this[self::NAME_IS_RESERVED] = $this->isLocalIP()
                || $this->isReservedPrivate()
                || $this->isReservedFuture();
        }

        return $this[self::NAME_IS_RESERVED];
    }

    /**
     * {@inheritdoc}
     */
    public function isReservedPrivate() : bool
    {
        return $this[self::NAME_IS_RESERVED_PRIVATE] === true;
    }

    /**
     * {@inheritdoc}
     */
    public function isReservedFuture(): bool
    {
        return $this[self::NAME_IS_RESERVED_FUTURE];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $this->getWhoIsServers();
        return parent::jsonSerialize();
    }
}
