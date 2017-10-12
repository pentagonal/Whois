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

/**
 * Class DataParser
 * @package Pentagonal\WhoIs\Util
 */
class DataParser
{
    const REGISTERED    = true;
    const UNREGISTERED  = false;
    const UNKNOWN = 'UNKNOWN';
    const LIMIT   = 'LIMIT';

    /**
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
            '/^(?:\;|\#)[^\n]+\n?/sm',
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
     *
     * @return string
     */
    public static function cleanMultipleWhiteSpaceTrim(string $data) : string
    {
        $data = str_replace("\r\n", "\n", $data);
        return trim(
            preg_replace(
                [
                    '/^(\s)+/sm',
                    '/(\s)+/sm'
                ],
                ['', '$1'],
                $data
            )
        );
    }

    /**
     * Clean Whois result
     *
     * @param string $data
     *
     * @return string
     */
    public static function cleanWhoIsSocketResult(string $data) : string
    {
        $data = static::cleanMultipleWhiteSpaceTrim($data);
        $data = preg_replace(
            '/^\s?(\%|#)/sm',
            '',
            $data
        );
        $data = preg_replace(
            '~
            (?:
                \>\>\>?   # information
                |Terms\s+of\s+Use\s*:\s+Users?\s+accessing  # terms
                |URL\s+of\s+the\s+ICANN\s+WHOIS # informational from icann 
            ).*
            ~isx',
            '',
            $data
        );

        return preg_replace(
            '/([\n])+/s',
            '$1',
            $data
        );
    }

    /**
     * Domain Parser Registered or Not Callback
     *
     * @param string $data
     *
     * @return bool|string string if unknown result or maybe empty result / limit exceeded or bool if registered or not
     * @uses DataParser::LIMIT
     * @uses DataParser::UNKNOWN
     */
    public static function hasRegisteredDomain(string $data)
    {
        // clean the data
        $cleanData = self::cleanWhoIsSocketResult($data);
        // check if empty result
        if ($cleanData === '') {
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
        if (preg_match('/Domain\s*(?:\_name)?\:(?:[^\n]+)/i', $cleanData)) {
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
                '/(?:Domain\s+)?Status\s*\:\s*NOT\s*AVAILABLE/i',
                $cleanData
            )) {
                return static::REGISTERED;
            }
        }

        if (stripos($cleanData, 'Status: Not Registered') !== false
            && preg_match('/[\n]+Query\s*\:[^\n]+/', $cleanData)
        ) {
            return static::UNREGISTERED;
        }

        // Reserved Domain
        if (preg_match('/^\s*Reserved\s*By/i', $cleanData)
            // else check contact or status billing, tech or contact
            || preg_match(
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
        if (self::hasContainLimitedResultData($data)) {
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

        $data = self::cleanWhoIsSocketResult($data);
        // check if on limit whois check
        if ($data && preg_match(
            '/
                (?:Resource|Whois)\s+Limit
                | exceeded\s(.+)?limit
                |limit\s+exceed
                |allowed\s+queries\s+exceeded
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

        preg_match('/Whois\s*Server\s*:([^\n]+)/i', $data, $match);
        return !empty($match[1])
            ? trim($match[1])
            : false;
    }
}
