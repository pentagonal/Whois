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
     * {@inheritdoc}
     */
    public function getASNumber(): string
    {
        return $this[self::NAME_ASN_ADDRESS];
    }

    /**
     * {@inheritdoc}
     */
    public function isReserved() : bool
    {
        if ($this[self::NAME_IS_RESERVED]
            || $this[self::NAME_IS_RESERVED_PRIVATE]
            || $this[self::NAME_IS_RESERVED_SAMPLE]
        ) {
            if (!$this[self::NAME_IS_RESERVED]) {
                $this[self::NAME_IS_RESERVED] = true;
            }
            return true;
        }
        return false;
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
    public function isReservedSample() : bool
    {
        return $this[self::NAME_IS_RESERVED_SAMPLE] === true;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnallocated() : bool
    {
        return $this[self::NAME_IS_UNALLOCATED] === true;
    }
}
