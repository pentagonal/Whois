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
class WhoIsResult implements \JsonSerializable, \ArrayAccess, \Serializable
{
    const TYPE_UNKNOWN = 'UNKNOWN';
    const TYPE_IP      = 'IP';
    const TYPE_DOMAIN  = 'DOMAIN';
    const TYPE_ASN     = 'ASN';

    const IP_IPV4    = 'IPV4';
    const IP_IPV6    = 'IPV6';

    const SERIALIZE_DATA_DETAIL = 'dataDetail';
    const SERIALIZE_DOMAIN_NAME = 'domainName';
    const SERIALIZE_DIRTY_DATA  = 'dirtyData';

    // icann compliance
    const ICANN_COMPLIANCE_URI = 'https://www.icann.org/wicf/';
    const ICANN_EPP_URI        = 'https://www.icann.org/epp';

    // for helper serialize
    const KEY_PARSER = 'dataParser';

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
    // const KEY_POSTAL_PROVINCE = 'province';
    const KEY_STATE    = 'state';

    // uri
    const KEY_WHOIS           = 'whois';
    const KEY_ICANN_COMPLIANCE = 'icann_compliance';
    const KEY_ICANN_EPP        = 'icann_epp';
    const KEY_REPORT           = 'report';

    const KEY_DATA   = 'data',
        KEY_TYPE     = 'type',
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
     * @param string $stringData
     * @param string $domainName
     */
    final public function __construct(string $domainName, string $stringData)
    {
        $this->hasParsed    = false;
        $this->dataParser   = $this->normalizeDataParser();
        $this->dataDetail   = $this->createCollectorFromData($domainName, $stringData);
    }

    /* --------------------------------------------------------------------------------*
     |                                   UTILITY                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Determine Type
     *
     * @param string $domainName
     * @return string
     */
    final protected function determineType(string $domainName) : string
    {
        $domainName = preg_replace('/^https?\:\/\//i', '', $domainName);
        if (preg_match_all(DataParser::ASN_REGEX, $domainName)) {
            return  static::TYPE_ASN;
        }

        $validator = new Validator();
        if ($validator->isValidIP($domainName)) {
            return $validator->isIPv6($domainName)
                ? static::IP_IPV6
                : static::IP_IPV4;
        }

        return $validator->isValidDomain($domainName)
            ? static::TYPE_DOMAIN
            : static::TYPE_UNKNOWN;
    }

    /**
     * Create Collector From Domain Name and Data
     *
     * @param string $domainName
     * @param string $stringData
     *
     * @return ArrayCollector
     */
    protected function createCollectorFromData(
        string $domainName,
        string $stringData
    ) : ArrayCollector {
        $dataParser = $this->dataParser;
        return new ArrayCollector([
            static::KEY_DOMAIN => [
                static::KEY_NAME => $domainName,
            ],
            static::KEY_DATA => [
                static::KEY_TYPE => $this->determineType($domainName),
                static::KEY_REFERRAL => null,
                static::KEY_RESELLER => null,
                static::KEY_RESULT => [
                    static::KEY_ORIGINAL => $stringData,
                    static::KEY_CLEAN => $dataParser::cleanUnwantedWhoIsResult($stringData),
                ]
            ],
        ]);
    }

    /**
     * Normalize data parser
     *
     * @access internal
     * @final for normalize
     * @return string
     */
    final protected function normalizeDataParser() : string
    {
        if (!$this->dataParser) {
            return DataParser::class;
        }

        $dataParser = $this->dataParser;
        if (! is_string($dataParser)) {
            $dataParser = $dataParser instanceof DataParser
                ? get_class($dataParser)
                : DataParser::class;
        }

        if (! is_string($dataParser)
            || ! class_exists($dataParser)
            || ! is_subclass_of($dataParser, DataParser::class)
            || strtolower(ltrim($dataParser, '\\')) !== strtolower(DataParser::class)
        ) {
            // fall back to default
            $dataParser = DataParser::class;
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
    final protected function parseData() : WhoIsResult
    {
        // if has parsed stop
        if ($this->hasParsed === true) {
            return $this;
        }

        // set has parsed
        // behaviour to make it sure put has parse on before create array collector
        $this->hasParsed = true;
        // create default array collector first
        $this->createArrayCollector(
            $this->getType(),
            $this->getDomainName()
        );
        $this->parseDetail();
        return $this;
    }

    /**
     * Create Array Collector
     * For Detail Result
     * @param string $type
     * @param string $domainName
     * @todo completion for array collector
     */
    protected function createArrayCollector(string $type, string $domainName)
    {
        $this->hasParsed = true;
        switch ($type) {
            case static::TYPE_DOMAIN:
                // registrant data default
                $registrantDefault = [
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
                $this->dataDetail->merge([
                    static::KEY_DOMAIN     => [
                        static::KEY_ID          => null,
                        static::KEY_NAME        => $domainName,
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
                            static::KEY_URL   => [],
                            static::KEY_EMAIL => [],
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
                ]);
                $data = $this->dataDetail[static::KEY_DATA];
                unset($this->dataDetail[static::KEY_DATA]);
                $this->dataDetail[static::KEY_DATA] = $data;
                break;
        }
    }

    /**
     * @todo completion parsing detail
     */
    protected function parseDetail() : ArrayCollector
    {
        $type = $this->getType();
        $domainName = $this->getDomainName();
        if ($type === static::TYPE_DOMAIN) {
            error_reporting(~0);
            ini_set('display_errors', 'on');
            // just check for first use especially for be domain
            $resultString = $this->getResultString();
            $resultString = str_replace("\r", "", $resultString);
            if (strpos($resultString, ":\n\t")) {
                $arr = explode("\n", $resultString);
                $currentKey = null;
                foreach ($arr as $key => $value) {
                    $arr[$key] = trim($value);
                    if (trim($value) == '' || substr(trim($value), 0, 1) == '%') {
                        continue;
                    }

                    if (substr(rtrim($arr[$key]), -1) === ':'
                        && isset($arr[$key+1])
                        && substr($arr[$key+1], 0, 1) === "\t"
                    ) {
                        unset($arr[$key]);
                        $currentKey = trim($value);
                        if (preg_match('/\t[a-z0-9\s]+\s*\:/i', $arr[$key+1])) {
                            $currentKey = rtrim($currentKey, ':');
                        }
                        continue;
                    }

                    if (substr($value, 0, 1) === "\t") {
                        $arr[$key] = "{$currentKey} {$arr[$key]}";
                    }
                }
                $resultString = implode("\n", $arr);
                unset($arr);
            }

            preg_match_all(
                '~
                    # detail top
                    (?:Registry)?\s*Domain\s+ID\:(?P<domain_id>[^\n]+)
                    | Updated?\s*Date\s*:(?P<updated_date>[^\n]+)
                    | (?:Creat(?:e[ds]?|ions?)\s*Date|Registered)\s*:(?P<created_date>[^\n]+)
                    | Expir(?:e[ds]?|y|ation)\s*Date\s*:(?P<expired_date>[^\n]+)
                    | (?:Domain\s*)?(?:Flags|Status)\s*:(?P<domain_status>[^\n]+)

                    # dnssec
                    | DNSSEC\s*\:(?P<domain_dnssec>[^\n]+)
                    # other
                    | Referral(?:[^\:]+)?\s*\:(?P<referral>[^\n]+)
                    | Reseller(?:[^\:]+)?\s*\:(?P<reseller>[^\n]+)

                    # name server
                    | (?:N(?:ame)?\s*\_?Servers?)\s*\:(?P<name_server>[^\n]+)

                    # whois
                    | Whois\s*Server\s*\:(?P<whois_server>[^\n]+)

                    # registrar
                    | (?:Registrar\s*(?:IANA)|Registrar)\s*ID\s*\:(?P<registrar_id>[^\n]+)
                    | Registr(?:ar|y)(?:\s*Company)?\s*\:(?P<registrar_name>[^\n]+)
                    | Registr(?:ar|y)\s*(?:URL|Web?site)\s*\:(?P<registrar_url>[^\n]+)
                    | (?:Registr(?:ar|y)\s*)?Abuse\s*[^\:]+e?mail\s*\:(?P<registrar_abuse_mail>[^\n]+)
                    | (?:Registr(?:ar|y)\s*)?Abuse\s*[^\:]+phone\s*\:(?P<registrar_abuse_phone>[^\n]+)

                    # Registrant Data
                    | (?:Registrant|owner)\s*ID\s*\:(?P<registrant_id>[^\n]+)
                    | (?:Registrant|owner)\s*Name\s*\:(?P<registrant_name>[^\n]+)
                    | (?:Registrant|owner)\s*(?:Organiz[^\:]+|Company)\s*\:(?P<registrant_org>[^\n]+)
                    | (?:Registrant|owner)\s*(?:Contact\s*)?Email(?:[\:]+)?\s*\:(?P<registrant_email>[^\n]+)
                    | (?:Registrant|owner)\s*Country?\s*\:(?P<registrant_country>[^\n]+)
                    | (?:Registrant|owner)\s*(?:State|Province)(?:[^\:]+)?\s*\:(?P<registrant_state>[^\n]+)
                    | (?:Registrant|owner)\s*City\s*\:(?P<registrant_city>[^\n]+)
                    | (?:Registrant|owner)\s*(?:Street|Addre[^\:]+)\s*\:(?P<registrant_street>[^\n]+)
                    | (?:Registrant|owner)\s*(?:Postal|Post)(?:[^\:]+)?\s*\:(?P<registrant_postal>[^\n]+)
                    | (?:Registrant|owner)\s*Phone(?:[\:]+)?\s*\:(?P<registrant_phone>[^\n]+)
                    | (?:Registrant|owner)\s*Fax(?:[\:]+)?\s*\:(?P<registrant_fax>[^\n]+)

                    # Registrant Billing
                    | (?:Billing(?:[^\:]+)?)\s*ID\s*\:(?P<billing_id>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*Name\s*\:(?P<billing_name>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*(?:Organiz[^\:]+|Company)\s*\:(?P<billing_org>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*(?:Contact\s*)?Email(?:[\:]+)?\s*\:(?P<billing_email>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*Country?\s*\:(?P<billing_country>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*(?:State|Province)(?:[^\:]+)?\s*\:(?P<billing_state>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*City\s*\:(?P<billing_city>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*(?:Street|Addre[^\:]+)\s*\:(?P<billing_street>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*(?:Postal|Post)(?:[^\:]+)?\s*\:(?P<billing_postal>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*Phone(?:[\:]+)?\s*\:(?P<billing_phone>[^\n]+)
                    | (?:Billing(?:[^\:]+)?)\s*Fax(?:[\:]+)?\s*\:(?P<billing_fax>[^\n]+)

                    # Registrant Admin
                    | (?:Admin(?:[^\:]+)?)\s*ID\s*\:(?P<admin_id>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*Name\s*\:(?P<admin_name>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*(?:Organiz[^\:]+|Company)\s*\:(?P<admin_org>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*(?:Contact\s*)?Email(?:[\:]+)?\s*\:(?P<admin_email>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*Country?\s*\:(?P<admin_country>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*(?:State|Province)(?:[^\:]+)?\s*\:(?P<admin_state>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*City\s*\:(?P<admin_city>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*(?:Street|Addre[^\:]+)\s*\:(?P<admin_street>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*(?:Postal|Post)(?:[^\:]+)?\s*\:(?P<admin_postal>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*Phone(?:[\:]+)?\s*\:(?P<admin_phone>[^\n]+)
                    | (?:Admin(?:[^\:]+)?)\s*Fax(?:[\:]+)?\s*\:(?P<admin_fax>[^\n]+)

                    # Registrant Tech
                    | (?:Tech(?:[^\:]+)?)\s*ID\s*\:(?P<tech_id>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*Name\s*\:(?P<tech_name>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*(?:Organiz[^\:]+|Company)\s*\:(?P<tech_org>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*(?:Contact\s*)?Email(?:[\:]+)?\s*\:(?P<tech_email>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*Country?\s*\:(?P<tech_country>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*(?:State|Province)(?:[^\:]+)?\s*\:(?P<tech_state>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*City\s*\:(?P<tech_city>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*(?:Street|Addre[^\:]+)\s*\:(?P<tech_street>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*(?:Postal|Post)(?:[^\:]+)?\s*\:(?P<tech_postal>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*Phone(?:[\:]+)?\s*\:(?P<tech_phone>[^\n]+)
                    | (?:Tech(?:[^\:]+)?)\s*Fax(?:[\:]+)?\s*\:(?P<tech_fax>[^\n]+)
                    # last update db
                    | (?:\>\>\>?)?\s*(?:Last\s*Update\s*(?:[a-z0-9\s]+)?(?:\s+Whois\s*)?(?:\s+Database(?:Whois)?)?)\s*
                          \:\s*(?P<last_update_db>(?:[0-9]+[0-9\-\:\s\+TZGMU]+)?)
                    # icann report url
                    | URL\s+of(?:\s+the)?\s+ICANN[^\:]+\:\s*(?P<icann_report>https?\:\/\/[^\n]+)
                ~xisx',
                $resultString,
                $match
            );
            if (empty($match)) {
                return $this->dataDetail;
            }

            // filtering result
            $match = array_filter($match, function ($key) {
                return ! is_int($key);
            }, ARRAY_FILTER_USE_KEY);
            $match = array_map(function ($v) {
                $v = array_filter($v);
                return array_map('trim', array_values($v));
            }, $match);

            $reportUrl = reset($match['icann_report']);

            // domain
            $dataDomain = $this->dataDetail[static::KEY_DOMAIN];
            $dataDomain[static::KEY_ID] = reset($match['domain_id'])?: null;
            $dataDomain[static::KEY_STATUS] = $match['domain_status'];
            $dataDomain[static::KEY_NAME_SERVER] = $match['name_server'];
            $dataDomain[static::KEY_DNSSEC] = reset($match['domain_dnssec']) ?: null;
            $this->dataDetail[static::KEY_DOMAIN] = $dataDomain;

            // date
            $dataDate = $this->dataDetail[static::KEY_DATE];
            $createdDate = reset($match['created_date'])?: null;
            $updateDate = reset($match['updated_date'])?: null;
            $expireDate = reset($match['expired_date'])?: null;
            if ($createdDate && is_int($createdDateNew = @strtotime($createdDate))) {
                $createdDate = gmdate('c', $createdDateNew);
            }
            if ($updateDate && is_int($updateDateNew = @strtotime($updateDate))) {
                $updateDate = gmdate('c', $updateDateNew);
            }
            if ($expireDate && is_int($expireDateNew = @strtotime($expireDate))) {
                $expireDate = gmdate('c', $expireDateNew);
            }
            $updateDb = reset($match['last_update_db']);
            $updateDb = $updateDb
                ? preg_replace('/^[^\:]+\:\s*/', '', $updateDb)
                : null;
            if ($updateDb && ($updateDbNew = strtotime($updateDb))) {
                $updateDb = gmdate('c', $updateDbNew);
            }
            $dataDate[static::KEY_CREATE] = $createdDate;
            $dataDate[static::KEY_UPDATE] = $updateDate;
            $dataDate[static::KEY_EXPIRE] = $expireDate;
            $dataDate[static::KEY_UPDATE_DB] = $updateDb;
            $this->dataDetail[static::KEY_DATE] = $dataDate;
            // registrar
            $registrar = $this->dataDetail[static::KEY_REGISTRAR];
            $registrar[static::KEY_ID] = reset($match['registrar_id']) ?: null;
            $registrar[static::KEY_NAME] = reset($match['registrar_name']) ?: null;
            if (!empty($match['registrar_url'])) {
                $match['registrar_url'] = array_map(function ($v) {
                    $v = trim($v);
                    if (!preg_match('/^(?:(?:http|ftp)s?)\:\/\//i', $v)) {
                        $v = "http://{$v}";
                    }
                    return $v;
                }, $match['registrar_url']);
            }
            $registrar[static::KEY_ABUSE] = [
                static::KEY_URL   => $match['registrar_url'],
                static::KEY_EMAIL => $match['registrar_abuse_mail'],
                static::KEY_PHONE => $match['registrar_abuse_phone'],
            ];
            $this->dataDetail[static::KEY_REGISTRAR] = $registrar;
            // ----------------- REGISTRANT
            $registrant = $this->dataDetail[static::KEY_REGISTRANT];
            $registrant[static::KEY_DATA] = [
                static::KEY_ID => reset($match['registrant_id']) ?: null,
                static::KEY_NAME => reset($match['registrant_name']) ?: null,
                static::KEY_ORGANIZATION => reset($match['registrant_org']) ?: null,
                static::KEY_EMAIL        => reset($match['registrant_email']) ?: null,
                static::KEY_COUNTRY      => reset($match['registrant_country']) ?: null,
                static::KEY_CITY         => reset($match['registrant_city']) ?: null,
                static::KEY_STREET       => reset($match['registrant_street']) ?: null,
                static::KEY_POSTAL_CODE  => reset($match['registrant_postal']) ?: null,
                static::KEY_STATE        => reset($match['registrant_state']) ?: null,
                static::KEY_PHONE        => $match['registrant_phone'],
                static::KEY_FAX          => $match['registrant_fax'],
            ];
            $registrant[static::KEY_BILLING] = [
                static::KEY_ID           => reset($match['billing_id']) ?: null,
                static::KEY_NAME         => reset($match['billing_name']) ?: null,
                static::KEY_ORGANIZATION => reset($match['billing_org']) ?: null,
                static::KEY_EMAIL        => reset($match['billing_email']) ?: null,
                static::KEY_COUNTRY      => reset($match['billing_country']) ?: null,
                static::KEY_CITY         => reset($match['billing_city']) ?: null,
                static::KEY_STREET       => reset($match['billing_street']) ?: null,
                static::KEY_POSTAL_CODE  => reset($match['billing_postal']) ?: null,
                static::KEY_STATE        => reset($match['billing_state']) ?: null,
                static::KEY_PHONE        => $match['billing_phone'],
                static::KEY_FAX          => $match['billing_fax'],
            ];
            $registrant[static::KEY_TECH] = [
                static::KEY_ID           => reset($match['tech_id']) ?: null,
                static::KEY_NAME         => reset($match['tech_name']) ?: null,
                static::KEY_ORGANIZATION => reset($match['tech_org']) ?: null,
                static::KEY_EMAIL        => reset($match['tech_email']) ?: null,
                static::KEY_COUNTRY      => reset($match['tech_country']) ?: null,
                static::KEY_CITY         => reset($match['tech_city']) ?: null,
                static::KEY_STREET       => reset($match['tech_street']) ?: null,
                static::KEY_POSTAL_CODE  => reset($match['tech_postal']) ?: null,
                static::KEY_STATE        => reset($match['tech_state']) ?: null,
                static::KEY_PHONE        => $match['tech_phone'],
                static::KEY_FAX          => $match['tech_fax'],
            ];
            $registrant[static::KEY_ADMIN] = [
                static::KEY_ID           => reset($match['admin_id']) ?: null,
                static::KEY_NAME         => reset($match['admin_name']) ?: null,
                static::KEY_ORGANIZATION => reset($match['admin_org']) ?: null,
                static::KEY_EMAIL        => reset($match['admin_email']) ?: null,
                static::KEY_COUNTRY      => reset($match['admin_country']) ?: null,
                static::KEY_CITY         => reset($match['admin_city']) ?: null,
                static::KEY_STREET       => reset($match['admin_street']) ?: null,
                static::KEY_POSTAL_CODE  => reset($match['admin_postal']) ?: null,
                static::KEY_STATE        => reset($match['admin_state']) ?: null,
                static::KEY_PHONE        => $match['admin_phone'],
                static::KEY_FAX          => $match['admin_fax'],
            ];
            $this->dataDetail[static::KEY_REGISTRANT] = $registrant;
            unset($registrar, $dataDate, $resultString, $dataDomain);
            $dataUrl = $this->dataDetail[static::KEY_URL];
            $whoIsServers = $match['whois_server'];
            try {
                $validator = new Validator();
                $extension = $validator->splitDomainName($domainName);
                $tld = new TLDCollector();
                $newServers = $tld->getServersFromExtension($extension[Validator::NAME_EXTENSION]);
                unset($tld, $validator);
                if (!empty($newServers)) {
                    $whoIsServers = array_merge($whoIsServers, (array) $newServers);
                    $whoIsServers = array_unique($whoIsServers);
                }
            } catch (\Throwable $e) {
                // pass
            }

            $dataUrl[static::KEY_WHOIS] = $whoIsServers;
            $dataUrl[static::KEY_REPORT] = $reportUrl != '' ? $reportUrl : $dataUrl[static::KEY_REPORT];
            $this->dataDetail[static::KEY_URL] = $dataUrl;
            unset($dataUrl);
        }

        return $this->dataDetail;
    }

    /**
     * @param string $domainName
     * @param string $dirtyData
     *
     * @return WhoIsResult
     */
    public static function create(string $domainName, string $dirtyData) : WhoIsResult
    {
        return new static($domainName, $dirtyData);
    }

    /* --------------------------------------------------------------------------------*
     |                                   GETTERS                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Get Domain Name
     *
     * @return string
     */
    public function getDomainName() : string
    {
        return $this->getDetail()[static::KEY_DOMAIN][static::KEY_NAME];
    }

    /**
     * @return ArrayCollector
     */
    public function getDetail() : ArrayCollector
    {
        $this->parseData();
        return $this->dataDetail;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->getDetail()[static::KEY_DATA][static::KEY_TYPE];
    }

    /**
     * @final for callback parser
     * @return string
     */
    final public function getCleanData() : string
    {
        return $this->getDetail()[static::KEY_DATA][static::KEY_CLEAN];
    }

    /**
     * @return string
     */
    public function getResultString() : string
    {
        return $this->getDetail()[static::KEY_DATA][static::KEY_RESULT][static::KEY_ORIGINAL];
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
        return (array) $this->getDetail();
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
         return $this->getDetail()->offsetExists($offset);
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
        return $this->getDetail()->offsetGet($offset);
    }

    /**
     * Magic method @uses \ArrayAccess::offsetSet()
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
         // no set please
    }

    /**
     * Magic method @uses \ArrayAccess::offsetUnset()
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        // no unset please
    }

    /* --------------------------------------------------------------------------------*
     |                                SERIALIZABLE                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @return string
     */
    final public function serialize() : string
    {
        // parse data first
        $this->parseData();
        $detail = [
            static::KEY_PARSER   => $this->dataParser,
            static::KEY_DOMAIN   => $this->getDomainName(),
            static::KEY_RESULT   => $this->getResultString(),
        ];

        return serialize($detail);
    }

    /**
     * Unserialize object value
     *
     * @param string $serialized
     * @throws \InvalidArgumentException
     */
    final public function unserialize($serialized)
    {
        $unSerialized = @unserialize($serialized);
        if (!is_array($unSerialized)
            || ! isset($unSerialized[static::KEY_DOMAIN])
            || ! isset($unSerialized[static::KEY_RESULT])
            || ! isset($unSerialized[static::KEY_PARSER])
            || ! is_string($unSerialized[static::KEY_DOMAIN])
            || ! is_string($unSerialized[static::KEY_RESULT])
            || ! class_exists($unSerialized[static::KEY_PARSER])
        ) {
            throw new \InvalidArgumentException(
                'Invalid serialized value',
                E_WARNING
            );
        }

        if (ltrim($unSerialized[static::KEY_PARSER], '\\') !== DataParser::class
           && ! is_subclass_of($unSerialized[static::KEY_PARSER], DataParser::class)
        ) {
            $parser = strtolower(ltrim($unSerialized[static::KEY_PARSER], '\\'));
            if ($parser !== strtolower(DataParser::class)) {
                throw new \InvalidArgumentException(
                    'Invalid serialized value',
                    E_WARNING
                );
            }

            $unSerialized[static::KEY_PARSER] = DataParser::class;
        }

        $this->hasParsed    = false;
        $this->dataParser   = $unSerialized[static::KEY_PARSER];
        $this->dataDetail   = $this->createCollectorFromData(
            $unSerialized[static::KEY_DOMAIN],
            $unSerialized[static::KEY_RESULT]
        );
        // and parse data
        $this->parseData();
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
        return json_encode($this->getDetail(), JSON_PRETTY_PRINT);
    }
}
