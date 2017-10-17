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
    const REGISTERED    = true;
    const RESERVED      = 1;
    const UNREGISTERED  = false;

    const UNKNOWN = 'UNKNOWN';
    const LIMIT   = 'LIMIT';

    const ASN_REGEX = '/^(ASN?)?([0-9]{1,20})$/i';

    const PORT_WHOIS = 43;

    // Uri
    const
        URI_IANA_WHOIS = 'whois.iana.org',
        URI_IANA_IDN   = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
        URI_PUBLIC_SUFFIX = 'https://publicsuffix.org/list/effective_tld_names.dat',
        URI_CACERT     = 'https://curl.haxx.se/ca/cacert.pem';

    // Path
    const
        PATH_WHOIS_SERVERS = __DIR__ . '/../../Data/Servers/AvailableServers.php',
        PATH_EXTENSIONS_AVAILABLE = __DIR__ . '/../../Data/Servers/AvailableExtensions.php',
        PATH_CACERT     = __DIR__ . '/../../Data/Certs/cacert.pem';

    const
        ARIN_NET_PREFIX_COMMAND    = 'n +',
        RIPE_NET_PREFIX_COMMAND    = '-V Md5.2',
        APNIC_NET_PREFIX_COMMAND   = '-V Md5.2',
        AFRINIC_NET_PREFIX_COMMAND = '-V Md5.2',
        LACNIC_NET_PREFIX_COMMAND  = '';

    const
        ARIN_SERVER     = 'whois.arin.net',
        RIPE_SERVER     = 'whois.ripe.net',
        APNIC_SERVER    = 'whois.apnic.net',
        AFRINIC_SERVER  = 'whois.afrinic.net',
        LACNIC_SERVER   = 'whois.lacnic.net';

    /**
     * @var array
     */
    protected static $serverPrefixList = [
        'whois.arin.net'    => self::ARIN_NET_PREFIX_COMMAND,
        'whois.ripe.net'    => self::RIPE_NET_PREFIX_COMMAND,
        'whois.apnic.net'   => self::APNIC_NET_PREFIX_COMMAND,
        'whois.afrinic.net' => self::AFRINIC_NET_PREFIX_COMMAND,
        'whois.lacnic.net'  => self::LACNIC_NET_PREFIX_COMMAND,
    ];

    /**
     * DataParser constructor.
     */
    final public function __construct()
    {
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
                \:\s*((?:[0-9]+[0-9\-\:\s\+TZGMU]+)?)
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
        $data = preg_replace(
            '~
            (?:
                \>\>\>?   # information
                | Terms\s+of\s+Use\s*:\s+Users?\s+accessing  # terms
                | URL\s+of\s+the\s+ICANN\s+WHOIS # informational from icann
                | NOTICE\s+AND\s+TERMS\s+OF\s+USE\s*: # dot ph comment
            ).*
            ~isx',
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
        if (!trim($data) === '') {
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
     * @uses DataParser::LIMIT
     * @uses DataParser::UNKNOWN
     */
    public static function getRegisteredDomainStatus(string $data)
    {
        // check if empty result
        if (($cleanData = static::cleanUnwantedWhoIsResult($data)) === '') {
            // if cleanData is empty & data is not empty check entries
            if ($data && preg_match('/No\s+entries(?:\s+found)?|Not(?:hing)?\s+found/i', $data)) {
                return static::UNREGISTERED;
            }

            return static::UNKNOWN;
        }

        // if invalid domain
        if (stripos($cleanData, 'Failure to locate a record in ') !== false) {
            return static::UNKNOWN;
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
            return static::UNREGISTERED;
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
            return static::UNREGISTERED;
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
                return static::UNREGISTERED;
            }

            if (preg_match(
                '/(?:Domain\s+)?Status\s*\:\s*
                    (NOT\s*AVAILABLE|RESERVED?|BANNED|Taken|Registered)
                /ix',
                $cleanData,
                $match
            )) {
                if (!empty($match[1]) && stripos($match[1], 'Reserv') !== false) {
                    return static::RESERVED;
                }

                return static::REGISTERED;
            }
        }

        if (stripos($cleanData, 'Status: Not Registered') !== false
            && preg_match('/[\n]+Query\s*\:[^\n]+/', $cleanData)
        ) {
            return static::UNREGISTERED;
        }

        // Reserved Domain
        if (preg_match(
            '/
                (^\s*Reserved\s*By)
                | (?:Th(?:is|e))?\s*domain\s*(?:(?:can|could)(?:not|n\'t))\s*be\s*register(?:ed)?
                /ix',
            $cleanData
        )) {
            return static::RESERVED;
        }

        // else check contact or status billing, tech or contact
        if (preg_match(
            '/
                    (
                        Registr(?:ar|y|nt)\s[^\:]+
                        | Whois\s+Server
                        | (?:Phone|Registrar|Contact|(?:admin|tech)-c|Organisations?)
                    )\s*\:\s*([^\n]+)
                    /ix',
            $cleanData,
            $matchData
        )
            && !empty($matchData[1])
            && (
               // match for name server
               preg_match(
                   '/(?:(?:Name\s+Servers?|n(?:ame)?servers?)\s*\:\s*)([^\n]+)/i',
                   $cleanData,
                   $matchServer
               )
               && !empty($matchServer[1])
               // match for billing
               || preg_match(
                   '/(?:Billing|Tech)\s+Email\s*:([^\n]+)/i',
                   $cleanData,
                   $matchDataResult
               ) && !empty($matchDataResult[1])
           )
        ) {
            return static::REGISTERED;
        }

        // check if on limit whois check
        if (static::hasContainLimitedResultData($data)) {
            return static::LIMIT;
        }

        return static::UNREGISTERED;
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
                | limit\s+exceed
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
        preg_match('~Whois(?:\s*Server)?\s*\:\s*([^\n]+)~i', $data, $match);
        return !empty($match[1])
            ? strtolower(trim($match[1]))
            : false;
    }

    /**
     * @param RequestInterface $request
     * @param bool $useClone
     *
     * @return string
     */
    public static function convertRequestBodyToString(RequestInterface $request, $useClone = true) : string
    {
        return self::convertStreamToString($request->getBody(), $useClone);
    }

    /**
     * Convert ResponseInterface body to string
     *
     * @param ResponseInterface $response
     * @param bool $useClone
     *
     * @return string
     */
    public static function convertResponseBodyToString(ResponseInterface $response, $useClone = true) : string
    {
        return self::convertStreamToString($response->getBody(), $useClone);
    }

    /**
     * Convert the given stream to string result
     *
     * @param StreamInterface $stream   the stream resource
     * @param bool            $useClone true if resource clone
     *
     * @return string
     */
    public static function convertStreamToString(StreamInterface $stream, $useClone = true) : string
    {
        $data = '';
        $stream = $useClone ? clone $stream : $stream;
        while (!$stream->eof()) {
            $data .= $stream->read(4096);
        }

        // if use clone close the resource otherwise let it open
        $useClone && $stream->close();

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
     * Is IP in Range
     *
     * @param string $ip
     * @param string $ipRange
     *
     * @return bool
     */
    public static function isIPInRange(string $ip, string $ipRange) : bool
    {
        if (strpos($ipRange, '/') === false) {
            $ipRange .= '/32';
        }

        // $range is in IP/CIDR format eg 127.0.0.1/24
        list( $ipRange, $netMask ) = explode('/', $ipRange, 2);
        $range_decimal = ip2long($ipRange);
        $ip_decimal    = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netMask)) - 1;
        $netMaskDecimal = ~ $wildcard_decimal;
        return (($ip_decimal & $netMaskDecimal) === ($range_decimal & $netMaskDecimal));
    }
}
