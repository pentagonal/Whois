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

namespace Pentagonal\WhoIs\Record\Result;

use Pentagonal\WhoIs\Abstracts\RecordResultAbstract;
use Pentagonal\WhoIs\Abstracts\WhoIsResultAbstract;
use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\Util\DataParser;

/**
 * Class ASN
 * @package Pentagonal\WhoIs\Record\Result
 * @todo Parsing Process
 */
class ASN extends RecordResultAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function initGenerateRecord(WhoIsResultAbstract $result) : ArrayCollector
    {
        return $this->parseASNDetail($result);
    }

    /* --------------------------------------------------------------------------------*
     |                                   UTILITY                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Parse data for result
     *
     * @param WhoIsResultAbstract $result
     *
     * @return ArrayCollector
     */
    protected function parseASNDetail(WhoIsResultAbstract $result) : ArrayCollector
    {
        return new ArrayCollector();
    }

    /**
     * Get type of Server
     *
     * @param WhoIsResultAbstract $result
     *
     * @return string
     */
    protected function getTypeOfServerFromResultString(WhoIsResultAbstract $result) : string
    {
        preg_match(
            '~
              (?P<RIPE>
                \s*\%(?:.+)?This\s*is\s*the\s*RIPE\s*Database\s*query\s*service
                | \s*\%\s*To\+receive\s+output\s+for\s+a\s+database\s+update\,\s+use\s+the[^\n]+
              )
              | (?P<APNIC>
                ^\s*\%\s*\[\s*whois\.apnic\.net\s*\]
              )
              | (?P<AFRINIC>
                ^\s*%\s*This\s*is\s+the\s+AfriNIC\s+Whois\s+server
              )
              | (?P<LACNIC>
                ^\s*%\s*Joint\s*Whois\s*\-\s*?\s+whois\.lacnic\.net
                | \s%\s*LACNIC\s+resource\s*\:\s*whois\.lacnic\.net
              )
              | (?P<ARIN>
                \s*\#\s*ARIN\s*WHOIS\s*data\s*and\s*services
                | \s*\#\s*https?\:\/\/www\.arin\.net\/public\/whoisinaccuracy
              )
            ~mxi',
            substr($result->getOriginalResultString(), 0, 1024),
            $match
        );

        $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
        $match = array_filter(array_map('trim', $match));
        $match = key($match);
        $server = [
            'RIPE' => DataParser::RIPE_SERVER,
            'APNIC' => DataParser::APNIC_SERVER,
            'ARIN' => DataParser::ARIN_SERVER,
            'LACNIC' => DataParser::LACNIC_SERVER,
            'AFRINIC' => DataParser::AFRINIC_SERVER,
        ];

        return isset($server[$match])
            ? $server[$match]
            : DataParser::STATUS_UNKNOWN;
    }

    /**
     * Parse Get Handler
     *
     * @param WhoIsResultAbstract $result
     * @param string|null $server
     *
     * @return ArrayCollector
     * @tod completion
     */
    protected function parseHandlerFromData(WhoIsResultAbstract $result, string $server = null) : ArrayCollector
    {
        $dataParser = new DataParser();
        $data = $dataParser->normalizeWhiteSpace($result->getOriginalResultString(), true);
        preg_match(
            '~
                \%\s*Abuse\s*contact\s*for(?:.+)\s*is\s*\'(?P<abuse_contact>[^\']+)\'[\n]?
            ~xi',
            $data,
            $abuseContact
        );
        $abuseContact = !empty($abuseContact['abuse_contact'])
            ? (trim($abuseContact['abuse_contact']) ?: null)
            : null;
        $data = rtrim($dataParser->cleanComment($data));
        $dataArray = array_filter(
            array_map('trim', explode("\n\n", $data))
        );

        /*
            aut-num: AS2764
            as-name: AAPT
            descr: AAPT Limited
            descr: 180-188 Burnley St
            descr: Richmond VIC 3121
            country: AU
            org: ORG-AL1-AP
            admin-c: ANO2-AP
            tech-c: ANO2-AP
            remarks: Send email about security incidents to security@connect.com.au
            remarks: Send email about UBE to abuse@connect.com.au
            notify: peering@connect.com.au
            mnt-by: CONNECT
            mnt-irt: IRT-AAPT-AU
            last-modified: 2017-10-16T00:15:09Z
            source: APNIC
         */
        $offset = 0;
        foreach ($dataArray as $key => $value) {
            if (strpos(trim($value), 'aut-num') === 0) {
                $firstInfo = $value;
                unset($dataArray[$key]);
                $offset = $key;
                break;
            }
        }
        $firstInfo = !isset($firstInfo) ? array_shift($dataArray) : $firstInfo;
        preg_match_all(
            '~
              aut\-num([^\:]+)?\:(?:[ ]+)?(?P<number>[^\n]+)
              | as\-name([^\:]+)?\:(?:[ ]+)?(?P<name>[^\n]+)
              | owner\-([^\:]+)?\:(?:[ ]+)?(?P<owner>[^\n]+)
              | admin([^\:]+)?\:(?:[ ]+)?(?P<admin>[^\n]+)
              | tech([^\:]+)?\:(?:[ ]+)?(?P<tech>[^\n]+)
              | org([^\:]+)?\:(?:[ ]+)?(?P<org>[^\n]+)
              | abuse([^\:]+)?\:(?:[ ]+)?(?P<abuse>[^\n]+)
              | routing([^\:]+)?\:(?:[ ]+)?(?P<routing>[^\n]+)
              | \-irt([^\:]+)?\:(?:[ ]+)?(?P<irt>[^\n]+)
              | responsible([^\:]+)?\:(?:[ ]+)?(?P<responsible>[^\n]+)
              | owners\s*\:(?:[ ]+)?(?P<owner_name>[^\n]+)
              | country([^\:]+)?\:(?:[ ]+)?(?P<country>[^\n]+)
              | desc([^\:]+)?\:(?:[ ]+)?(?P<description>[^\n]+)
              | remarks([^\:]+)?\:(?:[ ]+)?(?P<remarks>[^\n]+)
              | last\-mo([^\:]+)\:(?:[ ]+)?(?P<last_mod>[^\n]+)
              | notify([^\:]+)?\:(?:[ ]+)?(?P<notify>[^\n]+)
              | created([^\:]+)?\:(?:[ ]+)?(?P<created>[^\n]+)
              | change([^\:]+)?\:(?:[ ]+)?(?P<change>[^\n]+)
            ~xmi',
            $firstInfo,
            $matchFirstInfo
        );
        $matchFirstInfo = array_filter($matchFirstInfo, 'is_string', ARRAY_FILTER_USE_KEY);
        // make 2D array as sorted integer start with 0 if not empty
        $matchFirstInfo = array_map(
            'array_values',
            // filter empty value
            array_map(
                function ($v) {
                    return array_filter(array_map('trim', $v));
                },
                $matchFirstInfo
            )
        );

        $matchFirstInfo = array_merge(
            ['abuse_contact' => $abuseContact],
            $matchFirstInfo
        );

        return new ArrayCollector([
            'info'   => $matchFirstInfo,
            'first'  => $firstInfo,
            'offset' => $offset,
            'data' => implode("\n\n", $dataArray)
        ]);
    }
}
