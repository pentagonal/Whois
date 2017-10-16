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

namespace Pentagonal\WhoIs\Abstracts;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\Interfaces\RecordASNNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordDomainNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordIPNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordNetworkInterface;
use Pentagonal\WhoIs\Interfaces\WhoIsNetworkResultInterface;
use Pentagonal\WhoIs\Traits\ResultParser;
use Pentagonal\WhoIs\Util\DataParser;

/**
 * Class WhoIsResultAbstract
 * @package Pentagonal\WhoIs\Abstracts
 */
abstract class WhoIsResultAbstract implements WhoIsNetworkResultInterface
{
    // GLOBAL
    const KEY_ID       = 'id';
    const KEY_IANA_ID  = 'iana_id';
    const KEY_NAME     = 'name';
    // data
    const KEY_DATA     = 'data';
    const KEY_REFERRAL = 'referral';
    const KEY_RESELLER = 'reseller';
    // result
    const KEY_RESULT    = 'result';
    const KEY_ORIGINAL  = 'original';
    const KEY_CLEAN     = 'clean';

    // for helper serialize
    const KEY_NETWORK = 'network';

    // icann compliance
    const ICANN_COMPLIANCE_URI = 'https://www.icann.org/wicf/';
    const ICANN_EPP_URI        = 'https://www.icann.org/epp';

    /**
     * Constant For Domain
     */
    // domain
    const KEY_DOMAIN      = 'domain';
    const KEY_NAME_SERVER = 'name_server';
    const KEY_DNSSEC      = 'dnssec';

    const KEY_REGISTRAR  = 'registrar';
    const KEY_ABUSE      = 'abuse';
    const KEY_URL        = 'url';

    const KEY_REGISTRANT = 'registrant';
    const KEY_TECH     = 'tech';
    const KEY_ADMIN    = 'admin';
    const KEY_BILLING  = 'billing';

    const KEY_DATE      = 'date';
    const KEY_CREATE    = 'create';
    const KEY_UPDATE    = 'update';
    const KEY_EXPIRE    = 'expire';
    // last update database
    const KEY_UPDATE_DB = 'update_db';

    // ADDRESS
    const KEY_ORGANIZATION = 'organization';
    const KEY_STATUS   = 'status';
    const KEY_EMAIL    = 'email';
    const KEY_PHONE    = 'phone';
    const KEY_FAX      = 'fax';
    const KEY_COUNTRY  = 'country';
    const KEY_CITY     = 'city';
    const KEY_STREET   = 'street';
    const KEY_POSTAL_CODE = 'postal_code';
    const KEY_STATE    = 'state';
    // const KEY_POSTAL_PROVINCE = 'province';

    // URL
    const KEY_WHOIS            = 'whois';
    const KEY_SERVER           = 'server';
    const KEY_ICANN_COMPLIANCE = 'icann_compliance';
    const KEY_ICANN_EPP        = 'icann_epp';
    const KEY_REPORT           = 'report';

    // extends trait
    use ResultParser;

    /**
     * @var DataParser|string
     */
    protected $dataParser = DataParser::class;

    /**
     * @var ArrayCollector
     */
    protected $dataDetail;

    /**
     * @var bool
     */
    protected $hasParsed = false;

    /**
     * @var RecordNetworkInterface
     */
    protected $networkRecord;

    /**
     * @var bool doing parse data when unserialize called
     *           give value into true use parse data after unserialize
     */
    protected $parseWhenUnSerialize = true;

    /**
     * WhoIsResult constructor.
     *
     * @param RecordNetworkInterface $network
     * @param string $originalString
     * @param string $server optional server as additional url data
     *                      if empty will be fallback to default
     *                      on parsing process
     */
    final public function __construct(
        RecordNetworkInterface $network,
        string $originalString,
        string $server = null
    ) {
        $this->hasParsed     = false;
        if (! $network instanceof RecordDomainNetworkInterface
            && ! $network instanceof RecordIPNetworkInterface
            && ! $network instanceof RecordASNNetworkInterface
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Network Must be instance of : %1$s, %2$s or %3$s',
                    RecordDomainNetworkInterface::class,
                    RecordIPNetworkInterface::class,
                    RecordASNNetworkInterface::class
                )
            );
        }

        $this->networkRecord = $network;
        $this->dataParser    = $this->normalizeDataParser();
        $this->dataDetail    = $this->createArrayCollector($originalString);
        if ($server && ($server = trim($server))) {
            $this->dataDetail[static::KEY_URL][static::KEY_SERVER] = strtolower($server);
        }
    }

    /* --------------------------------------------------------------------------------*
     |                              JSON SERIALIZABLE                                  |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /* --------------------------------------------------------------------------------*
     |                                ARRAY ACCESS                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Magic method @uses \ArrayAccess::offsetExists()
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return $this->getDataDetail()->offsetExists($offset);
    }

    /**
     * Magic method @uses \ArrayAccess::offsetGet()
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getDataDetail()->offsetGet($offset);
    }

    /**
     * Magic method @uses \ArrayAccess::offsetSet()
     *
     * @param string $offset
     * @param mixed $value
     * @final to prevent override
     */
    final public function offsetSet($offset, $value)
    {
        // no set please
    }

    /**
     * Magic method @uses \ArrayAccess::offsetUnset()
     *
     * @param string $offset
     * @final to prevent override
     */
    final public function offsetUnset($offset)
    {
        // no unset please
    }

    /* --------------------------------------------------------------------------------*
     |                                SERIALIZABLE                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @final to prevent override and make result consequent
     * @return string
     */
    final public function serialize() : string
    {
        return serialize([
            static::KEY_NETWORK   => $this->networkRecord,
            static::KEY_RESULT    => $this->getOriginalResultString(),
        ]);
    }

    /**
     * Unserialize object value
     *
     * @param string $serialized
     * @final to prevent override and make result consequent
     * @throws \InvalidArgumentException
     */
    final public function unserialize($serialized)
    {
        $unSerialized = @unserialize($serialized);
        if (! is_array($unSerialized)
            || ! isset($unSerialized[static::KEY_RESULT])
            || ! isset($unSerialized[static::KEY_NETWORK])
            || ! is_string($unSerialized[static::KEY_RESULT])
            || ! $unSerialized[static::KEY_NETWORK] instanceof RecordNetworkInterface
        ) {
            throw new \InvalidArgumentException(
                'Invalid serialized value',
                E_WARNING
            );
        }

        $this->networkRecord = $unSerialized[static::KEY_NETWORK];
        if (! $this->networkRecord instanceof RecordDomainNetworkInterface
            && ! $this->networkRecord instanceof RecordIPNetworkInterface
            && ! $this->networkRecord instanceof RecordASNNetworkInterface
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid serialized value. Network Must be instance of : %1$s, %2$s or %3$s',
                    RecordDomainNetworkInterface::class,
                    RecordIPNetworkInterface::class,
                    RecordASNNetworkInterface::class
                )
            );
        }

        // set parsed as false
        $this->hasParsed  = false;
        $this->dataParser = $this->normalizeDataParser();
        $this->dataDetail = $this->createArrayCollector($unSerialized[static::KEY_RESULT]);
        // doing parse data
        if (!empty($this->parseWhenUnSerialize)) {
            $this->parseData();
        }
    }

    /* --------------------------------------------------------------------------------*
     |                                MAGIC METHOD                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Magic Method to string
     * @return string
     */
    public function __toString() : string
    {
        // call parse data
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    /* --------------------------------------------------------------------------------*
     |                                   GETTERS                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Get Data Parser
     * @return DataParser
     */
    public function getDataParser() : DataParser
    {
        return $this->normalizeDataParser();
    }

    /**
     * @return RecordNetworkInterface
     */
    public function getNetworkRecord(): RecordNetworkInterface
    {
        return $this->networkRecord;
    }

    /**
     * Get Domain Name
     *
     * @return string
     */
    public function getDomainName() : string
    {
        return $this->networkRecord->getPointer();
    }

    /**
     * Get WhoIs Server if exists on result
     *
     * @return string|null
     */
    public function getWhoIsServerFromResult()
    {
        return reset($this->dataDetail[static::KEY_URL][static::KEY_WHOIS]);
    }

    /**
     * @return string
     */
    public function getOriginalResultString() : string
    {
        return $this->dataDetail[static::KEY_DATA][static::KEY_RESULT][static::KEY_ORIGINAL];
    }

    /**
     * Get Array Detail data that convert into ArrayCollector
     *
     * @final to prevent override and make result consequent
     * @return ArrayCollector
     */
    final public function getDataDetail() : ArrayCollector
    {
        $this->parseData();
        return $this->dataDetail;
    }

    /**
     * Returning Detail into array
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->getDataDetail()->toArray();
    }

    /* --------------------------------------------------------------------------------*
     |                                   UTILITY                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Normalize data parser
     *
     * @access internal
     * @final for normalize
     * @return DataParser
     */
    final protected function normalizeDataParser() : DataParser
    {
        if (!$this->dataParser) {
            return new DataParser();
        }

        if ($this->dataParser instanceof DataParser) {
            return $this->dataParser;
        }

        $dataParser = $this->dataParser;
        if (! is_string($dataParser)) {
            $dataParser = $dataParser instanceof DataParser
                ? $dataParser
                : new DataParser();
        } elseif (! is_string($dataParser)
                  || ! class_exists($dataParser)
                  || ! is_subclass_of($dataParser, DataParser::class)
                  || strtolower(ltrim($dataParser, '\\')) !== strtolower(DataParser::class)
        ) {
            // fall back to default
            $dataParser = new DataParser();
        }

        return $dataParser;
    }

    /**
     * Parsing data
     *
     * @access internal
     * @final for fallback parser
     *
     * @throws \Throwable
     */
    final public function parseData() : WhoIsResultAbstract
    {
        // if has parsed stop
        if ($this->hasParsed === true) {
            return $this;
        }

        // set has parsed
        // behaviour to make it sure put has parse on before create array collector
        $this->hasParsed = true;
        $this->parseDetail();
        return $this;
    }

    /**
     * Create Collector
     *
     * @param string $stringData
     *
     * @return ArrayCollector
     */
    protected function createArrayCollector(string $stringData) : ArrayCollector
    {
        $whoIsServer = [];
        if (($server = DataParser::getWhoIsServerFromResultData($stringData))) {
            $whoIsServer = [$server];
        }

        $collection = [
            static::KEY_DATA => [
                static::KEY_REFERRAL => null,
                static::KEY_RESELLER => null,
                static::KEY_RESULT => [
                    static::KEY_ORIGINAL => $stringData,
                    static::KEY_CLEAN    => null,
                ]
            ],
            static::KEY_URL  => [
                static::KEY_SERVER => null,
                static::KEY_WHOIS  => $whoIsServer,
            ]
        ];

        if ($this->networkRecord instanceof RecordDomainNetworkInterface) {
            // registrant data default
            $registrationDefault = [
                static::KEY_ID           => null,
                static::KEY_NAME         => null,
                static::KEY_ORGANIZATION => null,
                static::KEY_EMAIL        => null,
                static::KEY_COUNTRY      => null,
                static::KEY_CITY         => null,
                static::KEY_STREET       => null,
                static::KEY_POSTAL_CODE  => null,
                static::KEY_STATE        => null,
                static::KEY_PHONE        => [],
                static::KEY_FAX          => [],
            ];

            $collection = [
                static::KEY_DOMAIN     => [
                    static::KEY_ID          => null,
                    static::KEY_NAME        => $this->networkRecord->getDomainName(),
                    static::KEY_STATUS      => [],
                    static::KEY_NAME_SERVER => [],
                    static::KEY_DNSSEC      => [
                        static::KEY_STATUS => null,
                        static::KEY_DATA   => [],
                    ],
                ],
                static::KEY_DATE       => [
                    static::KEY_CREATE    => null,
                    static::KEY_UPDATE    => null,
                    static::KEY_EXPIRE    => null,
                    static::KEY_UPDATE_DB => null,
                ],
                static::KEY_REGISTRAR  => array_merge(
                    array_merge(
                        [
                            static::KEY_IANA_ID => null
                        ],
                        $registrationDefault
                    ),
                    [
                        static::KEY_ABUSE => [
                            static::KEY_URL   => [],
                            static::KEY_EMAIL => [],
                            static::KEY_PHONE => [],
                        ],
                    ]
                ),
                static::KEY_REGISTRANT => [
                    static::KEY_DATA    => $registrationDefault,
                    static::KEY_BILLING => $registrationDefault,
                    static::KEY_TECH    => $registrationDefault,
                    static::KEY_ADMIN   => $registrationDefault,
                ],
                static::KEY_URL        => [
                    static::KEY_SERVER           => $collection[static::KEY_URL][static::KEY_SERVER],
                    static::KEY_WHOIS            => $collection[static::KEY_URL][static::KEY_WHOIS],
                    static::KEY_REPORT           => static::ICANN_COMPLIANCE_URI,
                    static::KEY_ICANN_COMPLIANCE => static::ICANN_COMPLIANCE_URI,
                    static::KEY_ICANN_EPP        => static::ICANN_EPP_URI,
                ],
                static::KEY_DATA => $collection[static::KEY_DATA],
            ];
        }

        return new ArrayCollector($collection);
    }

    /**
     * Parse Detail
     *
     * @return ArrayCollector
     */
    abstract protected function parseDetail() : ArrayCollector;
}
