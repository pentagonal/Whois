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
     * @var bool
     */
    protected $whoIsServerHasCheck = false;

    /**
     * {@inheritdoc}
     */
    public function getPointer(): string
    {
        return $this->getIPAddress();
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function getWhoIsServers(): array
    {
        if (!$this->whoIsServerHasCheck) {
            $servers = (array) $this[self::WHOIS_SERVER];
            $server = DataParser::URI_IANA_WHOIS;
            $this->whoIsServerHasCheck = true;
            if (!$this->isLocalIP()) {
                try {
                    $request = new WhoIsRequest($this->getIPAddress(), $server);
                    $server = DataParser::getWhoIsServerFromResultData($request->getBodyString()) ?: $server;
                    $this[self::WHOIS_SERVER] = array_merge([$server], $servers);
                } catch (\Throwable $e) {
                    // pass
                }
            }
        }

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
        return $this[self::NAME_IS_LOCAL_IP];
    }

    /**
     * {@inheritdoc}
     */
    public function IPv4() : bool
    {
        return $this[self::NAME_IS_IPV4];
    }

    /**
     * {@inheritdoc}
     */
    public function IPv6() : bool
    {
        return $this[self::NAME_IS_IPV6];
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
