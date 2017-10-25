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

namespace Pentagonal\WhoIs\Util;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\Traits\ResultNormalizer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class DataParser
 * @package Pentagonal\WhoIs\Util
 *
 * Data parser or helper for some reason function to easier implement code
 * and for code competent
 */
class DataParser
{
    use ResultNormalizer;

    // status
    const STATUS_REGISTERED    = true;
    const STATUS_RESERVED      = 1;
    const STATUS_UNREGISTERED  = false;
    const STATUS_UNKNOWN = 'UNKNOWN';
    const STATUS_LIMIT   = 'LIMIT';

    // 16bit = 0-65535 & 32 bit 65536-4199999999
    const ASN_REGEX = '/^(ASN?)?([0-9]|[1-9][0-9]{0,4}|[1-4][1]?[0-9]{0,8})$/i';
    // asn bit range
    const ASN16_MIN_RANGE = 0;
    const ASN16_MAX_RANGE = 65535;
    const ASN32_MIN_RANGE = 65536;
    const ASN32_MAX_RANGE = 4199999999;

    // base socket whois server port
    const PORT_WHOIS = 43;

    // URL
    const
        URI_IANA_WHOIS = 'whois.iana.org',
        URI_IANA_IDN   = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
        URI_PUBLIC_SUFFIX = 'https://publicsuffix.org/list/effective_tld_names.dat',
        URI_CACERT     = 'https://curl.haxx.se/ca/cacert.pem',
        // asn table
        URI_IANA_ASN_TABLE     = 'https://www.iana.org/assignments/as-numbers/as-numbers.xhtml',
        URI_IANA_ASN_TABLE_XML = 'https://www.iana.org/assignments/as-numbers/as-numbers.xml',
        // ip table
        URI_IANA_IPV4_TABLE     = 'https://www.iana.org/assignments/ipv4-address-space/ipv4-address-space.xhtml',
        URI_IANA_IPV4_TABLE_XML = 'https://www.iana.org/assignments/ipv4-address-space/ipv4-address-space.xml',
        URI_IANA_IPV6_TABLE     = 'https://www.iana.org/assignments'
                              . '/ipv6-unicast-address-assignments/ipv6-unicast-address-assignments.xhtml',
        URI_IANA_IPV6_TABLE_XML = 'https://www.iana.org/assignments'
                              . '/ipv6-unicast-address-assignments/ipv6-unicast-address-assignments.xml';

    // Path
    const
        DATA_PATH                 = __DIR__ . '/../../Data',

        // WHOIS EXTENSIONS & SERVER
        PATH_WHOIS_SERVERS        = self::DATA_PATH . '/Extensions/AvailableServers.php',
        PATH_EXTENSIONS_AVAILABLE = self::DATA_PATH . '/Extensions/AvailableExtensions.php',
        PATH_CACERT               = self::DATA_PATH . '/Certs/cacert.pem',

        // ASN
        PATH_AS16_DEL_BLOCKS      = self::DATA_PATH . '/Blocks/ASN16Blocks.dat',
        PATH_AS32_DEL_BLOCKS      = self::DATA_PATH . '/Blocks/ASN32Blocks.dat',

        // IPv(6|4)
        PATH_IP4_BLOCKS           = self::DATA_PATH . '/Blocks/IPv4Blocks.dat',
        PATH_IP6_BLOCKS           = self::DATA_PATH . '/Blocks/Ipv6Blocks.dat',

        // COUNTRIES
        PATH_COUNTRY_ISO3_AS_KEY  = self::DATA_PATH . '/Countries/ISO_3166_alpha-iso3-key.php',
        PATH_COUNTRY_ISO2_AS_KEY  = self::DATA_PATH . '/Countries/ISO_3166_alpha-iso2-key.php',
        PATH_COUNTRY_ISO2         = self::DATA_PATH . '/Countries/ISO_3166_alpha-2.php',
        PATH_COUNTRY_ISO3         = self::DATA_PATH . '/Countries/ISO_3166_alpha-3.php';

    const
        ARIN_SERVER      = 'whois.arin.net',
        RIPE_SERVER      = 'whois.ripe.net',
        APNIC_SERVER     = 'whois.apnic.net',
        AFRINIC_SERVER   = 'whois.afrinic.net',
        LACNIC_SERVER    = 'whois.lacnic.net',
        UNALLOCATED      = 'unallocated',
        RESERVED         = 'reserved',
        RESERVED_LOCAL   = 'reserved_local',
        RESERVED_FUTURE  = 'reserved_future',
        RESERVED_MULTICAST = 'reserved_multicast',
        RESERVED_PRIVATE = 'reserved_private',
        RESERVED_SAMPLE  = 'reserved_sample';

    const
        ARIN_NET_PREFIX_COMMAND    = 'n +',
        RIPE_NET_PREFIX_COMMAND    = '-V Md5.2',
        APNIC_NET_PREFIX_COMMAND   = '-V Md5.2',
        AFRINIC_NET_PREFIX_COMMAND = '-V Md5.2',
        LACNIC_NET_PREFIX_COMMAND  = '';

    /**
     * Prefix List command
     *
     * @var array
     */
    protected static $serverPrefixList = [
        self::ARIN_SERVER    => self::ARIN_NET_PREFIX_COMMAND,
        self::RIPE_SERVER    => self::RIPE_NET_PREFIX_COMMAND,
        self::APNIC_SERVER   => self::APNIC_NET_PREFIX_COMMAND,
        self::AFRINIC_SERVER => self::AFRINIC_NET_PREFIX_COMMAND,
        self::LACNIC_SERVER  => self::LACNIC_NET_PREFIX_COMMAND,
    ];

    /**
     * DataParser constructor.
     * @final
     */
    final public function __construct()
    {
        // pass empty for @fina;
    }

    /**
     * Get Whois Date Update of database record
     *
     * @param string $data
     *
     * @return string|null
     */
    public static function getWhoIsLastUpdateDatabase(string $data)
    {
        if (($data = str_replace(["\r", "\t"], ["", " "], trim($data))) === '') {
            return null;
        }
        preg_match(
            '/
                (?:\>\>\>?)?\s*
                (Last\s*Update\s*(?:[a-z0-9\s]+)?Whois\s*Database)\s*
                \:\s*((?:[0-9]+[0-9\-\:\s\+TZGMU\.]+)?)
            /ixU',
            $data,
            $match
        );

        if (empty($match[2])) {
            return null;
        }

        $match[1] = trim($match[1]);
        $match[2] = trim($match[2]);
        return trim(preg_replace('/(\s)+/', '$1', "{$match[1]}: {$match[2]}"));
    }

    /**
     * Get Whois Date Update
     *
     * @param string $data
     *
     * @return string|null
     */
    public static function getICANNReportUrl(string $data)
    {
        $data = str_replace(["\r", "\t"], ["", " "], trim($data));
        preg_match(
            '/
                URL\s+of(?:\s+the)?\s+ICANN[^\:]+\:\s*
                (https?\:\/\/[^\n]+)
            /ixU',
            $data,
            $match
        );

        if (empty($match[1])) {
            return null;
        }

        $data = preg_replace('/(\s)+/', '$1', trim($match[1]));
        return trim($data);
    }

    /**
     * Clean all unwanted result from whois result data
     *
     * @uses cleanComment
     * @uses cleanInformationalData
     * @uses trimReplaceWhiteSpace
     * @uses getWhoIsLastUpdateDatabase
     *
     * @param string $data
     * @param bool  $allowNewLine
     *
     * @return mixed|string
     */
    public static function cleanUnwantedWhoIsResult(string $data, $allowNewLine = false)
    {
        if (trim($data) === '') {
            return '';
        }
        $currentObj = new static();
        // clean the data
        $cleanData = $currentObj->cleanInformationalData(
            $currentObj->cleanComment(
                $currentObj->normalizeWhoIsDomainResultData($data)
            )
        );
        $cleanData = $currentObj->normalizeWhiteSpace($cleanData, $allowNewLine);
        if ($cleanData && ($dateUpdated = $currentObj->getWhoIsLastUpdateDatabase($data))) {
            $cleanData .= "\n{$dateUpdated}";
        }

        return $cleanData;
    }

    /**
     * Domain Parser Registered or Not - Callback method
     *
     * @param string $data
     *
     * @return bool|string string if unknown result or maybe empty result / limit exceeded or bool if registered or not
     * @uses DataParser::STATUS_LIMIT
     * @uses DataParser::STATUS_UNKNOWN
     */
    public static function getRegisteredDomainStatus(string $data)
    {
        // check if empty result
        if (($cleanData = static::cleanUnwantedWhoIsResult($data)) === '') {
            // if cleanData is empty & data is not empty check entries
            if ($data && preg_match('/No\s+entries(?:\s+found)?|Not(?:hing)?\s+found/i', $data)) {
                return self::STATUS_UNREGISTERED;
            }

            return self::STATUS_UNKNOWN;
        }

        if (preg_match('~Failure\s+to\+locate\+a\+record in\s+~xiU', $cleanData)) {
            return self::STATUS_UNKNOWN;
        }

        // dot kr domain
        if (stripos($cleanData, 'The requested domain was not found in the') !== false) {
            return self::STATUS_UNREGISTERED;
        }

        // Reserved Domain
        if (preg_match(
            '/
                (^\s*Reserved\s*By)
                | (?:Th(?:is|e))?\s*domain\s*(?:(?:can|could)(?:not|n\'t))\s*be\s*register(?:ed)?
                | The(?:\s+requested)?\s+domain(?:\s+name)?(?:is)?\s+restricted
                /ixU',
            $cleanData
        )) {
            return self::STATUS_RESERVED;
        }

        $nameServer = [];
        if (stripos($cleanData, 'Name') !== false) {
            preg_match(
                '/(?:(?:Name\s+Servers?|n(?:ame)?servers?)(?:[^\:]+)?\:\s*)([^\n]+)/i',
                $cleanData,
                $nameServer
            );
        }
        $nameServer = !empty($nameServer[1]) ? $nameServer[1] : null;
        $domainName = null;
        if (strpos($cleanData, 'Domain') !== false) {
            preg_match(
                '~\s*Domain(?:\s*\_?Name)?(?:[^\:]+)?\:\s*([^:\n]+)~i',
                $cleanData,
                $domainName
            );
            $domainName = !empty($domainName[1])
                ? strtolower(trim($domainName[1]))
                : null;
        }

        $domainDataExists = preg_match(
            '~^\s*(
                | (?:[a-z]+\.\s*)?\[(?:Domain|Registrant)
                | (?:
                    Owner
                    | (?:Registr(?:y|ations?|ant|ar))?
                        ?(Creat(?:ions?|ed?)|Expir(?:es?|y))\s+date
                    | Admin(?:istrative)
                    | Registrant
                    | Billing
                    | AC
                    | Tech(?:nical)?
                    )(?:[^\:]+)?\:
                )~miU',
            $cleanData,
            $match
        ) && !empty($match);
        if ($domainDataExists && $nameServer === null) {
            if (($dns = (array) @dns_get_record($domainName, DNS_NS))) {
                $nameServer = reset($dns);
                $nameServer = isset($nameServer['target'])
                    ? $nameServer['target']
                    : null;
            }
        }

        preg_match(
            '~\n?(query_status|(?:Domain\s+)?Status)(?:[^\:]+)?\:\s*([^\:\n]{2,})~xiU',
            $cleanData,
            $match
        );

        $domainStatus = isset($match[1]) ? trim($match[1]) : null;
        $domainStatus = $domainStatus ?: null;
        if ($nameServer && $domainDataExists) {
            return $domainStatus && stripos($domainStatus, 'Reserv') !== false
                ? self::STATUS_RESERVED
                : self::STATUS_REGISTERED;
        }

        if (preg_match(
            '~^(
                Available(?:[^\n]+)?\s*Domain:\s*[^\n]+
                | No\s+entries(?:\s+found)?
                | domain\s+(?:is\s+available|not\s+found)
                | no(?:(?:t|data)\s+found[\:\s\.]?|match|such\s+domain)
                | (?:th(?:is|e)\s+)?domain\s+name\s+(?:has\+not\+been|(?:is)?not)\s+register
                | (?:the\s+)?queried\s+object(?:\s+does|(?:ha|i)s)?\s+not\s+exist
                | (?:.+)\s*(?:No\s+match|not\s+exist\s+[io]n\s+database(?:[\!]+)?)
            )~xiU',
            trim($cleanData, '. ')
        )) {
            return self::STATUS_UNREGISTERED;
        }

        // match domain with name and with status available extension for eg: .be
        if ($domainName && $domainStatus) {
            if (preg_match(
                '~AVAILABLE|(?:No\s+Object|Not)\s*Found|220\s*Available~i',
                $domainStatus
            ) || stripos($domainStatus, 'Not Registered') !== false
                 && preg_match('/[\n]+Query\s*\:[^\n]+/', $cleanData)
            ) {
                return self::STATUS_UNREGISTERED;
            }

            return stripos($domainStatus, 'Reserv') !== false
                ? self::STATUS_RESERVED
                : self::STATUS_REGISTERED;
        }

        // check if on limit whois check
        if (!$nameServer && static::hasContainLimitedResultData($data)) {
            return self::STATUS_LIMIT;
        }

        return $nameServer
            ? self::STATUS_REGISTERED
            : self::STATUS_UNREGISTERED;
    }

    /**
     * Check if Whois Result is Limited
     *
     * @param string $data clean string data from whois result
     *
     * @return bool
     */
    public static function hasContainLimitedResultData(string $data) : bool
    {
        if (preg_match(
            '/passed\s+(?:the)?\s+daily\s+limit|temporarily\s+denied/i',
            $data
        )) {
            return true;
        }

        // check if on limit whois check
        if (($data = static::cleanUnwantedWhoIsResult($data)) !== ''
            && preg_match(
                '/
                (?:Resource|Whois)\s+Limit
                | exceeded\s(.+)?limit
                | (?:limit|quota)\s+exceed
                |allow(?:ed|ing)?\s+quer(?:ies|y)\s+exceeded
            /ix',
                $data
            )) {
            return true;
        }

        return false;
    }

    /**
     * Parse Whois Server from whois result data
     *
     * @param string $data
     *
     * @return bool|string
     */
    public static function getWhoIsServerFromResultData(string $data)
    {
        if (trim($data) === '') {
            return false;
        }

        $currentObject = new static();
        $data = $currentObject->cleanComment($data);
        if (!preg_match(
            '~(
                Whois(?:\s*Server)?\s*[\:\]]\s*(?:whois\:\/\/)? # Domain
                | ReferralServer\s*[\:]\s+whois\:\/\/   # ASN
            )
            ([^\n]+)~ixU',
            $data,
            $match
        ) || empty($match[1])) {
            return false;
        }

        return strtolower(trim($match[1]));
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    public static function convertRequestBodyToString(RequestInterface $request) : string
    {
        return self::convertStreamToString($request->getBody());
    }

    /**
     * Convert ResponseInterface body to string
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function convertResponseBodyToString(ResponseInterface $response) : string
    {
        return self::convertStreamToString($response->getBody());
    }

    /**
     * Convert the given stream to string result
     * Behaviour to make sure the content size is *not a huge size*
     *
     * @param StreamInterface $stream   the stream resource
     *
     * @return string
     */
    public static function convertStreamToString(StreamInterface $stream) : string
    {
        $currentPos = -1;
        try {
            $currentPos = $stream->tell();
        } catch (\Exception $e) {
            // pass
        }

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $data = '';
        // Rewind position
        while (! $stream->eof()) {
            // sanitize for safe utf8
            $data .= Sanitizer::normalizeInvalidUTF8($stream->read(4096));
        }

        // if seekable fallback to previous position
        if ($currentPos > -1 && $stream->isSeekable()) {
            $stream->seek($currentPos);
        }

        return $data;
    }

    /**
     * @param string $ip
     * @param string $server
     *
     * @return string
     */
    public static function buildNetworkAddressCommandServer(string $ip, string $server) : string
    {
        // if contain white space on IP ignore it
        if (! preg_match('/\s+/', trim($ip))
            && ($server = strtolower(trim($server))) !== ''
            && isset(static::$serverPrefixList[$server])
            && static::$serverPrefixList[$server]
        ) {
            $prefix = static::$serverPrefixList[$server];
            if (strpos($ip, "{$prefix} ") !== 0) {
                $ip = "{$prefix} {$ip}";
            }
        }

        return $ip;
    }

    /**
     * @param string $asnNumber
     * @param string $server
     *
     * @return string
     */
    public static function buildASNCommandServer(string $asnNumber, string $server) : string
    {
        // if contain white space on IP ignore it
        if (! preg_match('/\s+/', trim($asnNumber))
            && ($server = strtolower(trim($server))) !== ''
            && isset(static::$serverPrefixList[$server])
            && static::$serverPrefixList[$server]
        ) {
            $asnNumber = strtoupper($asnNumber);
            $asnNumber = 'AS'.ltrim($asnNumber, ' AS');
            if ($server === self::ARIN_SERVER) {
                $asnNumber = ltrim($asnNumber, ' AS');
                return "a + {$asnNumber}";
            }
            $prefix    = static::$serverPrefixList[$server];
            if (strpos($asnNumber, "{$prefix} ") !== 0) {
                $asnNumber = "{$prefix} $asnNumber";
            }
        }

        return $asnNumber;
    }

    /**
     * Parse The Selector Matches till end of tags
     * This only for helper parser.
     * Don't use this method if you use for common purpose.
     * Please use another library such as: Symfony DOM Crawler or etc.
     *
     * @param string $tag  tag to be select
     * @param string $html full html content
     *
     * @return ArrayLoopAbleCallback|ArrayCollector[]
     */
    public static function htmlParenthesisParser(string $tag, string $html) : ArrayLoopAbleCallback
    {
        if (preg_match('/[^a-z0-9\-\_]/i', $tag)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is Invalid html tag',
                    $tag
                )
            );
        }

        $tag = preg_quote($tag, '/');
        $regex = '#<('.$tag.')\b(?P<selector>[^>]*)?>(?P<content>.*?)</\b\1>#s';
        preg_match_all(
            $regex,
            $html,
            $match
        );
        array_shift($match);
        $array = [];
        foreach ($match['content'] as $key => $value) {
            $selector = $match['selector'][$key];
            preg_match_all(
                '~
                        (?P<selector>
                            [a-z]+(?:(?:[a-z0-9\_\-]+)?[a-z]+)
                        )
                        \=
                        [\'\"](?P<content>[^\"\']+)
                    ~ix',
                $selector,
                $filtered
            );
            $filtered = array_filter($filtered, 'is_string', ARRAY_FILTER_USE_KEY);
            $selector = new ArrayCollector();
            foreach ($filtered['selector'] as $k => $val) {
                if (strtolower($val) === 'class') {
                    // special for class
                    $filtered['content'][$k] = array_map('trim', explode(' ', $filtered['content'][$k]));
                    $filtered['content'][$k] = array_unique(array_filter($filtered['content'][$k]));
                    $filtered['content'][$k] = array_values($filtered['content'][$k]);
                }

                $selector[$val] = $filtered['content'][$k];
            }
            $array[$key] = new ArrayCollector([
                'selector' => $selector,
                'html'     => $value,
            ]);
        }

        return new ArrayLoopAbleCallback($array);
    }

    /**
     * Get Country From code
     *
     * @param string $code
     *
     * @return array|null
     */
    public static function getCountryFromCode(string $code)
    {
        $code = strtoupper(trim($code));
        if (!$code) {
            return null;
        }

        /** @noinspection PhpIncludeInspection */
        $data = strlen($code) === 2
            ? require self::PATH_COUNTRY_ISO2_AS_KEY
            : (
                strlen($code) === 3
                ? require self::PATH_COUNTRY_ISO3_AS_KEY
                : []
            );

        if (empty($data)) {
            return null;
        }

        return isset($data[$code])
            ? $data[$code]
            : null;
    }

    /**
     * Get From Country Match
     *
     * @param string $countryName
     *
     * @return array[]|null country code ISO2 will be used as key arrays
     */
    public static function getFromCountryName(string $countryName)
    {
        if (($countryName = trim($countryName)) === '') {
            return null;
        }

        $quoted = preg_quote($countryName, '~');
        /** @noinspection PhpIncludeInspection */
        $grep = preg_grep('~^'.$quoted.'$~i', require self::PATH_COUNTRY_ISO2);
        $key = !empty($grep)
            ? key($grep)
            : null;
        return $key
            ? static::getCountryFromCode($key)
            : null;
    }

    /**
     * Get Search By Country Name
     * Will be search of similarity if nothing found on start offset
     *
     * @param string $code
     *
     * @return array[]|null country code ISO2 will be used as key arrays
     */
    public static function getSearchCountryISO2(string $code)
    {
        if (trim($code) === '') {
            return null;
        }
        $code = trim($code);
        /** @noinspection PhpIncludeInspection */
        $data = require self::PATH_COUNTRY_ISO2;
        $regexQuote = preg_quote($code, '~');
        $grep = preg_grep('~\s*^' . $regexQuote . '~i', $data)
            ?:preg_grep('~\s*' . $regexQuote . '~i', $data);
        if (empty($grep)) {
            return null;
        }
        // sort by similarity
        uasort($grep, function ($a, $b) use ($code) {
            if (($posA = (stripos($a, $code) === 0)) || ($posB = (stripos($b, $code) === 0))) {
                !isset($posB)
                    && $posB = (stripos($b, $code) === 0);
                return $posA
                    ? (
                        strpos($b, $code) === 0
                        ? 0
                        : -1
                    ) : ($posB ? -1 : 0);
            }

            similar_text($code, $a, $percent_a);
            similar_text($code, $b, $percent_b);
            return $percent_a === $percent_b ? 0 : ($percent_a > $percent_b ? -1 : 1);
        });

        foreach ($grep as $key => $value) {
            $grep[$key] = static::getCountryFromCode($key);
        }

        return $grep;
    }
}
