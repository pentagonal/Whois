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
        DATA_PATH          = __DIR__ . '/../../Data',
        PATH_WHOIS_SERVERS = self::DATA_PATH . '/Extensions/AvailableServers.php',
        PATH_EXTENSIONS_AVAILABLE = self::DATA_PATH . '/Extensions/AvailableExtensions.php',
        PATH_CACERT     = self::DATA_PATH . '/Certs/cacert.pem',
        // ASN
        PATH_AS16_DEL_BLOCKS  = self::DATA_PATH . '/Blocks/ASN16Blocks.dat',
        PATH_AS32_DEL_BLOCKS  = self::DATA_PATH . '/Blocks/ASN32Blocks.dat',
        // IPv(6|4)
        PATH_IP4_BLOCKS  = self::DATA_PATH . '/Blocks/IPv4Blocks.dat',
        PATH_IP6_BLOCKS  = self::DATA_PATH . '/Blocks/Ipv6Blocks.dat';

    const
        ARIN_NET_PREFIX_COMMAND    = 'n +',
        RIPE_NET_PREFIX_COMMAND    = '-V Md5.2',
        APNIC_NET_PREFIX_COMMAND   = '-V Md5.2',
        AFRINIC_NET_PREFIX_COMMAND = '-V Md5.2',
        LACNIC_NET_PREFIX_COMMAND  = '';

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
     * Clean string ini comment
     * dot ini comment is # and ;
     *
     * @param string $data
     *
     * @return string
     */
    public static function cleanIniComment(string $data) : string
    {
        $data = trim($data);
        if ($data == '') {
            return $data;
        }

        return preg_replace(
            '/^(?:\;|\#)[^\n]+\n?/m',
            '',
            $data
        );
    }

    /**
     * Clean Slashed Comment
     *
     * @param string $data
     *
     * @return string
     */
    public static function cleanSlashComment(string $data) : string
    {
        $data = trim($data);
        if ($data == '') {
            return $data;
        }

        return preg_replace(
            '/^(?:\/\/)[^\n]+\n?/sm',
            '',
            $data
        );
    }

    /**
     * Clean Multiple whitespace
     *
     * @param string $data
     * @param bool $allowEmptyNewLine allow one new line
     *
     * @return string
     */
    public static function cleanMultipleWhiteSpaceTrim(string $data, $allowEmptyNewLine = false) : string
    {
        $data = str_replace(
            ["\r\n", "\t",],
            ["\n", " "],
            $data
        );

        if (!$allowEmptyNewLine) {
            return trim(preg_replace(['/^[\s]+/m', '/(\n)[ ]+/'], ['', '$1'], $data));
        }

        $data = preg_replace(
            ['/(?!\n)([\s])+/m', '/(\n)[ ]+/', '/([\n][\n])[\n]+/m'],
            '$1',
            $data
        );

        return trim($data);
    }

    /**
     * Normalize Whois Result
     *
     * @param string $data
     *
     * @return string
     */
    public static function normalizeWhoIsDomainResultData(string $data) : string
    {
        $data = str_replace("\r", "", $data);
        // sanitize for .BE domain
        if (strpos($data, ":\n\t")) {
            $arr = explode("\n", $data);
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
            $data = implode("\n", $arr);
            unset($arr);
        }

        // sanitize .kr domain
        if (preg_match('/Name\s*Server\s+Host\s*Name[^\:]+\:/mi', $data)) {
            $callBack = function ($match) {
                $prefix = stripos($match[0], 'Primary') !== false
                    ? 'Primary'
                    : 'Secondary';
                $match = $match[1];
                $match = preg_replace(
                    [
                        '/\s*IP\s*Address[^\n]+/smi',
                        '/\s*Host\s*Name\s*/smi',
                    ],
                    ['', "\n{$prefix} Name Server"],
                    $match
                );
                return trim($match);
            };
            $data = preg_replace_callback(
                [
                    '~
                      Primary\s+Name\s*Server[\n]+\s*
                        ((?:Host\s*Name|IP\s*Address)[^\:]+\:\s+(?:(?!\n\n)[\s\S])*)
                    ~xsmi',
                    '~
                      Secondary\s+Name\s*Server[\n]+\s*
                        ((?:Host\s*Name|IP\s*Address)[^\:]+\:\s+(?:(?!\n\n)[\s\S])*)      
                    ~xsmi',
                ],
                $callBack,
                $data
            );
        }
        // sanitize .jp domain
        if (stripos($data, '.jp') !== false && preg_match('~\[(Domain\s*)?Name\]|\[Name\s+Server\]~xsi', $data)) {
            $arrayData = [];
            // convert comment
            $data = preg_replace('/\[\s+([^\n]+)\]/m', '% $1', $data);
            if (stripos($data, '[Registrant]') !== false && strpos($data, '[Name]') !== false) {
                $data = str_ireplace("\n[Registrant]", "\n[Registrar] ", $data);
            }
            $arrayDataSplit = explode("\n\n", $data);
            foreach ($arrayDataSplit as $key => $v) {
                $v = preg_replace('/^(?:[a-z]+\.\s?)?\[([^\]]+)\]/m', '$1:', $v);
                if ($v && $v[0] != '%' && preg_match('~([a-z]+[^\n]+)(\n\s{3,}[^\n]+)+~smi', $v)) {
                    $v = preg_replace_callback(
                        '~(?P<name>^[a-z]+[^\:]+)(?P<line>\:[^\n]+)(?P<val>(?:\n\s{3,}[^\n]+)+)~smi',
                        function ($match) {
                            $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
                            $match['name'] = rtrim($match['name']);
                            $match['line'] = rtrim($match['line'], '( )');
                            $match['val']  = preg_replace(
                                '~(\s)+~',
                                '$1',
                                str_replace("\n", ' ', $match['val'])
                            );
                            $match['val'] = rtrim($match['val'], '( )');
                            return ($match['name'] . $match['line'] . $match['val']);
                        },
                        $v
                    );
                }
                if (stripos(trim($v), 'Domain Information') === 0
                    || ($isContact = stripos(trim($v), 'Contact Information') === 0)
                ) {
                    $v = ltrim(preg_replace('/^[^\n]+/', '', ltrim($v)));
                    $v = preg_replace('/\n\s+/', ' ', $v);
                    if (isset($isContact) && $isContact) {
                        // fix name
                        $v = preg_replace_callback(
                            '~^([^\:]+\:)([^\n]+)?~m',
                            function ($match) {
                                $registrant = 'Registrant';
                                // fix spaces
                                $length   = strlen($registrant) + 1;
                                $match[1] = "{$registrant} {$match[1]}";
                                $match[2] = preg_replace(
                                    "~^[\s]{1,{$length}}~",
                                    '',
                                    $match[2]
                                );
                                return $match[1] . $match[2];
                            },
                            $v
                        );
                        $matchCountry = false;
                        if (preg_match('~Country\:\s+([^\n]+)?~', $data, $match)) {
                            $matchCountry = $match[1];
                        }
                        $v = preg_replace('/Postal\s+(Address(?:[^\:]+)?\:)/i', '$1       ', $v);
                        // split city, state & address
                        $v = preg_replace_callback(
                            '/^Registrant\s+Address\:[^\n]+/m',
                            function ($match) use ($matchCountry) {
                                $match = rtrim($match[0]);
                              // get space
                                preg_match('/^Registrant\s+Address\:(\s*)/', $match, $space);
                                $space = !empty($space[1]) ? $space[1] : '    ';
                                $explodeArrayAddress = array_map('trim', explode(',', $match));
                                $state  = array_pop($explodeArrayAddress);
                                $city   = array_pop($explodeArrayAddress);
                                $country = $matchCountry;
                                $street = implode(', ', $explodeArrayAddress);
                                if (!$country && preg_match('~(.+)\s+([0-9]+[0-9\-][0-9]{2,})$~', $city, $match)) {
                                    $country = $state;
                                    $state  = $match[1];
                                    if (substr_count($street, ' ') > 2) {
                                        $explodeArrayAddress = explode(' ', $street);
                                        $city = array_pop($explodeArrayAddress);
                                        $street = rtrim(implode(' ', $explodeArrayAddress));
                                    }
                                }
                                $content = "{$street}\n";
                                $content .= "Registrant City:   {$space}{$city}\n";
                                $content .= "Registrant State:  {$space}{$state}\n";
                                $content .= "Registrant Country:  {$space}{$country}\n";
                                return $content;
                            },
                            $v
                        );
                    }
                }

                $arrayData[] = $v;
            }

            $data = implode("\n\n", $arrayData);
            unset($arrayDataSplit);
        }

        if (stripos($data, 'Algorithm') === false || stripos($data, 'Digest') === false) {
            return $data;
        }

        // fix for DNSSSEC
        $placeHolder = "[__".microtime(true)."__]";
        $data = preg_replace_callback(
            '/
                (?P<name>
                  (?:
                    DS\s*Key(?:\s*Tag)?
                    | Algorithm
                    | Digest\s*Type
                    | Digest\s*
                  )
                )\s*(?P<selector>[0-9]+)(?:[^\:]+)?\:(?P<values>[0-9a-f]+)[^\n]*
            /mxi',
            function ($match) use ($placeHolder) {
                if (strpos($match['name'], 'DS') !== false) {
                    return "DNSSEC DS Data: {$match['values']}";
                }
                return $placeHolder.$match['values'];
            },
            $data
        );

        $data = str_replace(["\n{$placeHolder}", $placeHolder,], " ", $data);
        $data = static::cleanMultipleWhiteSpaceTrim($data);
        return $data;
    }

    /**
     * Clean whois comment result
     * the comment string started with # and %
     *
     * @param string $data
     *
     * @return mixed|string
     */
    public static function cleanWhoIsResultComment(string $data)
    {
        $data = str_replace("\r", "", $data);
        $data = preg_replace('/^(\#|\%)[^\n]+\n?/m', '', $data);
        return trim($data);
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
        $data = str_replace(["\r", "\t"], ["", " "], trim($data));
        preg_match(
            '/
                (?:\>\>\>?)?\s*
                (Last\s*Update\s*(?:[a-z0-9\s]+)?Whois\s*Database)\s*
                \:\s*((?:[0-9]+[0-9\-\:\s\+TZGMU\.]+)?)
            /ix',
            $data,
            $match
        );

        if (empty($match[2])) {
            return null;
        }

        $match[1] = trim($match[1]);
        $match[2] = trim($match[2]);
        $data = preg_replace('/(\s)+/', '$1', "{$match[1]}: {$match[2]}");
        return trim($data);
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
            /ix',
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
     * Clean Whosi result informational data like, ICANN URL or comment whois
     * or ads or etc.
     *
     * @param string $data
     *
     * @return mixed|string
     */
    public static function cleanWhoIsResultInformationalData(string $data)
    {
        $data = preg_replace('/query[^\:]+[^\n]+/mi', '', $data);
        $data = preg_replace(
            '~
            (?:
                \>\>\>?   # information
                | Terms\s+of\s+Use\s*:\s+Users?\s+accessing  # terms
                | URL\s+of\s+the\s+ICANN\s+WHOIS # informational from icann
                | NOTICE\s+AND\s+TERMS\s+OF\s+USE\s*: # dot ph comment
                | (\#\s*KOREAN\s*\(UTF8\)\s*)?상기\s*도메인이름은 
            ).*
            ~isxu',
            '',
            $data
        );

        return $data;
    }

    /**
     * Clean all unwanted result from whois result data
     *
     * @uses cleanWhoIsResultComment
     * @uses cleanWhoIsResultInformationalData
     * @uses cleanMultipleWhiteSpaceTrim
     * @uses getWhoIsLastUpdateDatabase
     *
     * @param string $data
     *
     * @return mixed|string
     */
    public static function cleanUnwantedWhoIsResult(string $data)
    {
        if (trim($data) === '') {
            return '';
        }

        // clean the data
        $cleanData = static::normalizeWhoIsDomainResultData($data);
        $cleanData = static::cleanWhoIsResultComment($cleanData);
        $cleanData = static::cleanWhoIsResultInformationalData($cleanData);
        $cleanData = static::cleanMultipleWhiteSpaceTrim($cleanData);
        if ($cleanData && ($dateUpdated = static::getWhoIsLastUpdateDatabase($data))) {
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
                return static::STATUS_UNREGISTERED;
            }

            return static::STATUS_UNKNOWN;
        }

        // if invalid domain
        if (stripos($cleanData, 'Failure to locate a record in ') !== false
            || stripos($cleanData, 'The requested domain name is restricted because') !== false
        ) {
            return static::STATUS_UNKNOWN;
        }

        // array check for detailed content only that below is not registered
        $matchUnRegistered = [
            'domain is available',
            'domain not found',
            'not found',
            'no data found',
            'no match',
            'No such domain',
            'this domain name has not been registered',
            'the queried object does not exist',
        ];

        // clean dot on both side
        $cleanData = trim($cleanData, '.');
        if (in_array(strtolower($cleanData), $matchUnRegistered)
            // for za domain eg: co.za
            || stripos($cleanData, 'Available') === 0 && strpos($cleanData, 'Domain:')
        ) {
            return static::STATUS_UNREGISTERED;
        }

        // regex not match or not found on start tag
        if (preg_match(
            '/^(?:
                    No\s+match\s+for
                    | No\s+Match
                    | Not\s+found\s*\:?
                    | No\s*Data\s+Found
                    | Domain\s+not\s+found
                    | Invalid\s+query\s+or\s+domain
                    | The\s+queried\s+object\s+does\s+not\s+exist
                    | (?:Th(?:is|e))\s+domain(?:\s*name)?\s+has\s*not\s+been\s+register
                )/ix',
            $cleanData
        )
            || preg_match(
                '/Domain\s+Status\s*\:\s*(available|No\s+Object\s+Found)/im',
                $cleanData
            )
            // match for queried object
            || preg_match(
                '/^(?:.+)\s*(?:No\s+match|not\s+exist\s+[io]n\s+database(?:[\!]+)?)$/',
                $cleanData
            )
        ) {
            return static::STATUS_UNREGISTERED;
        }

        // match domain with name and with status available extension for eg: .be
        if (preg_match('/Domain\s*(?:\_?Name\s*)?\:(?:[^\n]+)/i', $cleanData)) {
            if (preg_match(
                '/
                    (?:Domain\s+)?Status\s*\:\s*(?:AVAILABLE|(?:No\s+Object|Not)\s+Found)
                    | query_status\s*:\s*220\s*Available
                /ix',
                $cleanData
            )) {
                return static::STATUS_UNREGISTERED;
            }

            if (preg_match(
                '/(?:Domain\s+)?Status\s*\:\s*
                    (
                        NOT\s*AVAILABLE|RESERVED?|BANNED|Taken|Registered
                        |No\s*Object\s*Found
                    )
                /ix',
                $cleanData,
                $match
            )) {
                if (!empty($match[1]) && stripos($match[1], 'Reserv') !== false) {
                    return static::STATUS_RESERVED;
                }

                return static::STATUS_REGISTERED;
            }
        }

        if (stripos($cleanData, 'Status: Not Registered') !== false
            && preg_match('/[\n]+Query\s*\:[^\n]+/', $cleanData)
        ) {
            return static::STATUS_UNREGISTERED;
        }

        // Reserved Domain
        if (preg_match(
            '/
                (^\s*Reserved\s*By)
                | (?:Th(?:is|e))?\s*domain\s*(?:(?:can|could)(?:not|n\'t))\s*be\s*register(?:ed)?
                /ix',
            $cleanData
        )) {
            return static::STATUS_RESERVED;
        }

        // match for name server
        if (preg_match(
            '/(?:(?:Name\s+Servers?|n(?:ame)?servers?)(?:[^\:]+)?\:\s*)([^\n]+)/i',
            $cleanData,
            $matchServer
        )
            && preg_match(
                '/(
                    (?:Registrant|owner)
                    (?:
                        (?:Contact|Name|E\-?mail|Phone|Fax|Street)(?:[^\:]+)?
                        |\s*
                    )
                )
                \:/xi',
                $cleanData,
                $match
            )
            && !empty($match[1])
            // else check contact or status billing, tech or contact
            || preg_match(
                '/
                    (
                        Registr(?:ar|y|nt)
                        | Whois\s+Server
                        | (?:Phone|Registrar|Contact|(?:admin|tech)-c|Organi[z|s]ations?)
                    )(?:[^\:]+)?\:\s*([^\n]+)
                    /ix',
                $cleanData,
                $matchData
            )
            && !empty($matchData[1])
            && (
               // match for billing
               preg_match(
                   '/(?:Billing|Tech|AC|Registrant)
                        (?:\s+Contact)?\s+
                        (?:E\-?mail|Phone|Fax|Organi)(?:[^\:]+)?:([^\n]+)
                    /ix',
                   $cleanData,
                   $matchDataResult
               ) && !empty($matchDataResult[1])
           )
        ) {
            return static::STATUS_REGISTERED;
        }

        // check if on limit whois check
        if (static::hasContainLimitedResultData($data)) {
            return static::STATUS_LIMIT;
        }

        return static::STATUS_UNREGISTERED;
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
        $data = static::cleanWhoIsResultComment($data);
        preg_match('~Whois(?:\s*Server)?\s*\[\:\]]\s*([^\n]+)~i', $data, $match);
        return !empty($match[1])
            ? strtolower(trim($match[1]))
            : false;
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
        $data = '';
        // Rewind position
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        while (!$stream->eof()) {
            $data .= $stream->read(4096);
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
     * @param string $ip
     * @param string $server
     *
     * @return string
     */
    public static function buildASNCommandServer(string $ip, string $server) : string
    {
        // if contain white space on IP ignore it
        if (! preg_match('/\s+/', trim($ip))
            && ($server = strtolower(trim($server))) !== ''
            && isset(static::$serverPrefixList[$server])
            && static::$serverPrefixList[$server]
        ) {
            $ip     = ltrim($ip);
            if ($server === 'whois.arin.net') {
                return "a + {$ip}";
            }

            $ip     = ltrim($ip);
            $prefix = static::$serverPrefixList[$server];
            if (strpos($ip, "{$prefix} ") !== 0) {
                $ip = "{$prefix} $ip";
            }
        }

        return $ip;
    }

    /**
     * Parse The Selector Matches
     *
     * @param string $tag
     * @param string $html
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
        $regex = '/\<('.$tag.')\b(?P<selector>[^\>]*)?>(?P<content>.*)\<\/\b\\1\>/sU';
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
}
