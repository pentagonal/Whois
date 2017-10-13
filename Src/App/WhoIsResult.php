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
 */
class WhoIsResult
{
    const TYPE_UNKNOWN = 'UNKNOWN';
    const TYPE_IP      = 'IP';
    const TYPE_DOMAIN  = 'DOMAIN';
    const TYPE_ASN     = 'ASN';

    const IP_IPV4    = 'IPV4';
    const IP_IPV6    = 'IPV6';

    /**
     * Constant Key for Collection detail
     */
    // domain
    const KEY_DOMAIN     = 'domain';
    const KEY_REGISTRAR  = 'registrar';
    const KEY_REGISTRANT = 'registrant';
    const KEY_ABUSE      = 'abuse';
    const KEY_RESULT     = 'result';
    const KEY_URL        = 'url';
    const KEY_NAME_SERVER = 'name_server';

    const KEY_ID       = 'id';
    const KEY_NAME     = 'name';
    const KEY_ORGANIZATION = 'organization';
    const KEY_STATUS   = 'status';
    const KEY_CREATE   = 'create';
    const KEY_UPDATE   = 'update';
    const KEY_EXPIRE   = 'expire';
    const KEY_EMAIL    = 'email';
    const KEY_PHONE    = 'phone';
    const KEY_FAX      = 'fax';
    // address
    const KEY_COUNTRY  = 'country';
    const KEY_CITY     = 'city';
    const KEY_STREET   = 'street';
    const KEY_POSTAL_CODE = 'postal_code';
    const KEY_POSTAL_PROVINCE = 'province';
    const KEY_POSTAL_STATE    = 'state';

    // uri
    const KEY_WHOIS           = 'whois';
    const KEY_REFERRAL        = 'referral';
    const KEY_ICANN_REPORT_URI = 'report';

    // informational
    const KEY_UPDATE_DB      = 'last_update_database';
    const KEY_RESELLER       = 'reseller';
    const KEY_DNSSEC         = 'dnssec';
    const KEY_ORIGINAL       = 'original';
    const KEY_CLEAN          = 'clean';
    const KEY_SERVER         = 'server';

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
    protected $type;

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

        $this->cleanData = $this
            ->dataParser
            ->cleanUnwantedWhoIsResult(
                $this->getWhoIsRequest()->getBodyString()
            );

        return $this;
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
     * @final for callback parser
     * @return string
     */
    final public function getCleanData(): string
    {
        $this->parseData();
        return $this->cleanData;
    }

    /**
     * @todo completion parsing detail
     */
    protected function parseDetail() : ArrayCollector
    {
        $collector = new ArrayCollector();
        return $collector;
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
}
