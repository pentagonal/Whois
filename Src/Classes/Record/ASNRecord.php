<?php
namespace Pentagonal\WhoIs\Record;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\Interfaces\RecordASNNetworkInterface;

/**
 * Class ASNRecord
 * @package Pentagonal\WhoIs\Record
 */
class ASNRecord extends ArrayCollector implements RecordASNNetworkInterface
{
    /**
     * Get ASN Address
     *
     * @return string
     */
    public function getPointer(): string
    {
        return $this->getASNumber();
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function getWhoIsServers(): array
    {
        return (array) $this[self::WHOIS_SERVER];
    }

    /**
     * @return string
     */
    public function getASNumber(): string
    {
        return $this[self::NAME_ASN_ADDRESS];
    }
}
