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

namespace Pentagonal\WhoIs\App;

use Pentagonal\WhoIs\Util\DataParser;

// @todo completion
/**
 * Class WhoIsResult
 * @package Pentagonal\WhoIs\App
 * By default on string result is parse on JSON
 */
class WhoIsResult implements \JsonSerializable, \ArrayAccess
{
    const TYPE_UNKNOWN = 'UNKNOWN';
    const TYPE_IP      = 'IP';
    const TYPE_DOMAIN  = 'DOMAIN';
    const TYPE_ASN     = 'ASN';

    const IP_IPV4    = 'IPV4';
    const IP_IPV6    = 'IPV6';

    // icann compliance
    const ICANN_COMPLIANCE_URI = 'https://www.icann.org/wicf/';
    const ICANN_EPP_URI        = 'https://www.icann.org/epp';

    /**
     * Constant Key for Collection detail
     */
    // domain
    const KEY_DOMAIN    = 'domain',
        KEY_NAME_SERVER = 'name_server',
        KEY_DNSSEC      = 'dnssec';

    const KEY_REGISTRAR  = 'registrar',
        KEY_ABUSE        = 'abuse';

    const KEY_URL        = 'url';

    const KEY_REGISTRANT = 'registrant',
        KEY_TECH     = 'tech',
        KEY_ADMIN    = 'admin',
        KEY_BILLING  = 'billing';

    const KEY_DATE    = 'date',
        KEY_CREATE    = 'create',
        KEY_UPDATE    = 'update',
        KEY_EXPIRE    = 'expire',
        # last update database
        KEY_UPDATE_DB = 'update_db';

    const KEY_ID       = 'id';
    const KEY_NAME     = 'name';

    // address
    const KEY_ORGANIZATION = 'organization';
    const KEY_STATUS   = 'status';
    const KEY_EMAIL    = 'email';
    const KEY_PHONE    = 'phone';
    const KEY_FAX      = 'fax';
    const KEY_COUNTRY  = 'country';
    const KEY_CITY     = 'city';
    const KEY_STREET   = 'street';
    const KEY_POSTAL_CODE = 'postal_code';
    const KEY_POSTAL_PROVINCE = 'province';
    const KEY_POSTAL_STATE    = 'state';

    // uri
    const KEY_WHOIS           = 'whois';
    const KEY_ICANN_COMPLIANCE = 'icann_compliance';
    const KEY_ICANN_EPP        = 'icann_epp';
    const KEY_REPORT           = 'report';

    const KEY_DATA   = 'data',
        KEY_REFERRAL = 'referral',
        KEY_RESELLER = 'reseller',
        // result
        KEY_RESULT = 'result',
        KEY_ORIGINAL  = 'original',
        KEY_CLEAN     = 'clean';

    /**
     * @var string|DataParser
     */
    protected $dataParser = DataParser::class;

    /**
     * @var WhoIsRequest
     */
    protected $whoIsRequest;

    /**
     * @var string
     */
    protected $type = self::TYPE_UNKNOWN;

    /**
     * @var string
     */
    protected $cleanData;

    /**
     * @var ArrayCollector
     */
    protected $dataDetail;

    /**
     * @var bool
     */
    private $hasParsed = false;

    /**
     * WhoIsResult constructor.
     *
     * @param WhoIsRequest $request
     */
    public function __construct(WhoIsRequest $request)
    {
        $this->whoIsRequest = $request;
        $this->hasParsed = false;
    }

    /* --------------------------------------------------------------------------------*
     |                                   UTILITY                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Parsing data
     *
     * @access internal
     * @final for fallback parser
     *
     * @throws \Throwable
     */
    final protected function parseData() : WhoIsResult
    {
        // if has parsed stop
        if ($this->hasParsed === true) {
            return $this;
        }

        // set has parsed
        $this->hasParsed = true;
        // normalize data parser
        $this->normalizeDataParser();
        $this->getWhoIsRequest()->send();
        if ($this->getWhoIsRequest()->isError()) {
            $response = $this->getWhoIsRequest()->getResponse();

            // if contain error and response is not throwable
            // throw RuntimeException
            if (!$response instanceof \Throwable) {
                throw new \RuntimeException(
                    "Invalid whois request that contains error but response is not throwable",
                    E_COMPILE_ERROR
                );
            }

            throw $response;
        }
        // determine type first
        $this->determineType();
        // create default array collector
        $this->createArrayCollector();
        $this->cleanData = $this
            ->dataParser
            ->cleanUnwantedWhoIsResult(
                $this->getWhoIsRequest()->getBodyString()
            );

        return $this;
    }

    /**
     * Determine Type
     */
    final protected function determineType()
    {
        $domainName = $this->getWhoIsRequest()->getDomainName();
        $domainName = preg_replace('/^https?\:\/\//i', '', $domainName);
        if (preg_match_all(DataParser::ASN_REGEX, $domainName)) {
            $this->type = static::TYPE_ASN;
            return;
        }

        $validator = new Validator();
        if ($validator->isValidIP($domainName)) {
            $this->type = $validator->isIPv6($domainName)
                ? static::IP_IPV6
                : static::IP_IPV4;
            return;
        }

        $this->type = $validator->isValidDomain($domainName)
            ? static::TYPE_DOMAIN
            : static::TYPE_UNKNOWN;
    }

    /**
     * Normalize data parser
     *
     * @access internal
     * @final for normalize
     */
    final protected function normalizeDataParser()
    {
        if (!$this->dataParser) {
            $this->dataParser = DataParser::class;
        }
        if (! is_string($this->dataParser)) {
            $this->dataParser = $this->dataParser instanceof DataParser
                ? get_class($this->dataParser)
                : DataParser::class;
        }

        if (!is_string($this->dataParser)
            || !class_exists($this->dataParser)
            || ! is_subclass_of($this->dataParser, DataParser::class)
        ) {
            // fall back to default
            $this->dataParser = DataParser::class;
        }
    }

    /**
     * Create Array Collector
     * For Detail Result
     * @todo completion for array collector
     */
    protected function createArrayCollector()
    {
        if ($this->dataDetail instanceof ArrayCollector && count($this->dataDetail) > 0) {
            return;
        }

        // Redetermine
        if ($this->getType() === 'UNKNOWN') {
            $this->determineType();
        }

        // registrant data default
        $registrantDefault = [
            static::KEY_ID => null,
            static::KEY_NAME => null,
            static::KEY_ORGANIZATION => null,
            static::KEY_EMAIL    => null,
            static::KEY_COUNTRY => null,
            static::KEY_CITY => null,
            static::KEY_STREET => null,
            static::KEY_POSTAL_CODE => null,
            static::KEY_POSTAL_PROVINCE => null,
            static::KEY_POSTAL_STATE => null,
            static::KEY_PHONE => [],
            static::KEY_FAX => [],
        ];

        $collection = [
            static::KEY_DATA => [
                static::KEY_REFERRAL => null,
                static::KEY_RESELLER => null,
                static::KEY_RESULT => [
                    static::KEY_CLEAN => [],
                    static::KEY_ORIGINAL => [],
                ]
            ],
        ];

        switch ($this->getType()) {
            case static::TYPE_DOMAIN:
                $collection = [
                    static::KEY_DOMAIN     => [
                        static::KEY_ID          => null,
                        static::KEY_NAME        => null,
                        static::KEY_STATUS      => [],
                        static::KEY_NAME_SERVER => [],
                        static::KEY_DNSSEC      => null,
                    ],
                    static::KEY_DATE       => [
                        static::KEY_CREATE    => null,
                        static::KEY_UPDATE    => null,
                        static::KEY_EXPIRE    => null,
                        static::KEY_UPDATE_DB => null,
                    ],
                    static::KEY_REGISTRAR  => [
                        static::KEY_ID    => null,
                        static::KEY_NAME  => null,
                        static::KEY_ABUSE => [
                            static::KEY_EMAIL => [],
                            static::KEY_URL   => [],
                            static::KEY_PHONE => [],
                        ]
                    ],
                    static::KEY_REGISTRANT => [
                        static::KEY_DATA    => $registrantDefault,
                        static::KEY_BILLING => $registrantDefault,
                        static::KEY_TECH    => $registrantDefault,
                        static::KEY_ADMIN   => $registrantDefault,
                    ],
                    static::KEY_URL        => [
                        static::KEY_WHOIS            => [],
                        static::KEY_REPORT           => static::ICANN_COMPLIANCE_URI,
                        static::KEY_ICANN_COMPLIANCE => static::ICANN_COMPLIANCE_URI,
                        static::KEY_ICANN_EPP        => static::ICANN_EPP_URI,
                    ],
                    // fallback
                    static::KEY_DATA => $collection[static::KEY_DATA]
                ];
                break;
        }

        $this->dataDetail = new ArrayCollector($collection);
    }

    /**
     * @todo completion parsing detail
     */
    protected function parseDetail() : ArrayCollector
    {
        $this->createArrayCollector();

        return $this->dataDetail;
    }

    /**
     * @param WhoIsRequest $request
     *
     * @return WhoIsResult
     */
    public static function create(WhoIsRequest $request) : WhoIsResult
    {
        return new static($request);
    }

    /* --------------------------------------------------------------------------------*
     |                                   GETTERS                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @final for callback parser
     * @return string
     */
    final public function getCleanData(): string
    {
        $this->parseData();
        return $this->cleanData;
    }

    /**
     * @return WhoIsRequest
     */
    public function getWhoIsRequest() : WhoIsRequest
    {
        return $this->whoIsRequest;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        $this->parseData();
        return $this->type;
    }

    /**
     * @return ArrayCollector
     */
    public function getDetail() : ArrayCollector
    {
        $this->parseData();
        return $this->dataDetail;
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
        return (array) $this->dataDetail;
    }

    /* --------------------------------------------------------------------------------*
     |                                ARRAY ACCESS                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
         return $this->getDetail()->offsetExists($offset);
    }

    /**
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getDetail()->offsetGet($offset);
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
         $this->getDetail()->offsetSet($offset, $value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->getDetail()->offsetUnset($offset);
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
        $this->parseData();
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
