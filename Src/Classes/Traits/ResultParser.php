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

namespace Pentagonal\WhoIs\Traits;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\Util\DataParser;

/**
 * Trait ResultParser
 * @package Pentagonal\WhoIs\Traits
 */
trait ResultParser
{
    use ResultNormalizer;

    /**
     * Parse Domain Detail
     *
     * @param string $data
     *
     * @return ArrayCollector|ArrayCollector[]
     */
    protected function parseDomainDetail(string $data) : ArrayCollector
    {
        // just check for first use especially for be domain
        $data = $this->normalizeWhoIsDomainResultData($data);
        if (preg_match('~\#\s*ENGLISH\s+~smix', $data)
            && preg_match('~\#\s*[a-z0-9\_\-]+\(UTF8\)\s+~smix', $data)
        ) {
            $data = preg_replace('~^query([^\:]+)?\:(?:[^\n]+)?\s*~miu', '', $data);
            $data = preg_replace(
                '~(?:\#\s*[a-z0-9\_\-]+\(UTF8\)\s+(?:.*))(?:\#\s*ENGLISH\s+(.*))~smiu',
                '$1',
                $data
            );
        }

        preg_match_all(
            '~
                # DNSSEC Data & Status
                DNSSEC\s*\:(?P<domain_dnssec_status>[^\n]+)
                | (DNSSEC\s+(?:[^\:]+)|Signing\s*Key(?:[^\:]+)?)\:(?P<domain_dnssec>[^\n]+)

                # Domain ID
                | (?:Registry)?\s*Domain\s+ID\:(?P<domain_id>[^\n]+)

                # Date
                | (Updated?\s*Date|Last\s*Updated?)(?:([^\:]+)?On)?(?:[^\:]+)?\:(?P<date_updated>[^\n]+)
                | (?:Creat(?:e[ds]?|ions?)\s*(On|Date)|(?:Date\s*)?Registered)(?:[^\:]+)?\:(?P<date_created>[^\n]+)
                | Expir(?:e[ds]?|y|ations?)\s*(?:Date)?(?:([^\:]+)?On)?\s*\:(?P<date_expired>[^\n]+)
                
                # Last Update DB & Status
                | (?:\>\>\>?)?\s*
                   (Last\s*Update\s*(?:[a-z0-9\s]+)?Whois\s*Database)\s*
                      \:\s*(?P<date_last_update_db>(?:[0-9]+[0-9\-\:\s\+TZGMU\.]+)?)
                | (?:Domain\s*)?(?:Flags|Status)\s*\:(?P<domain_status>[^\n]+)

                # Other Data
                | Referral(?:[^\:]+)?\:(?P<referral>[^\n]+)
                | Reseller(?:[^\:]+)?\:(?P<reseller>[^\n]+)

                # Name Server
                | (?:N(?:ame)?\s*\_?Servers?)\s*\:(?P<name_server>[^\n]+)

                # whois
                | Whois\s*Server(?:[^\:]+)?\:(?P<whois_server>[^\n]+)

                # Registrar Data
                | Registr(?:ar|y)\s*ID\s*\:(?P<registrar_id>[^\n]+)
                | Registr(?:ar|y)(?:\s*IANA)(?:[^\:]+)?ID\s*\:(?P<registrar_iana_id>[^\n]+)
                | Registr(?:ar|y)(?:Contact)?(?:\s*Name(?:[^\:]+)?|\s*)\:(?P<registrar_name>[^\n]+)
                | (?:
                    Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:Organi[zs][^\:]+|Company|Org\.?\s*)
                    | Authorized\s*Agency
                  )
                  (?:[^\:]+)?
                    \:(?P<registrar_org>[^\n]+)

                | Registr(?:ar|y)\s*((?:Contact|Admin)\s+)?E\-?mail(?:[^\:]+)?\:(?P<registrar_email>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?Country(?:[^\:]+)?\:(?P<registrar_country>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:State|Province)(?:[^\:]+)?
                    \:(?P<registrar_state>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?City(?:[^\:]+)?\:(?P<registrar_city>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:Street|Addre)(?:[^\:]+)?\:
                    (?P<registrar_street>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:Postal|Post|Zip)(?:[^\:]+)?\:
                    (?P<registrar_postal>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?Phone(?:[^\:]+)?\:(?P<registrar_phone>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?Fax(?:[^\:]+)?\:(?P<registrar_fax>[^\n]+)

                | (?:Registr(?:ar|y)\s*)?(Abuse|Customer\s+Service)
                    [^\:]+(?:E\-?)?mail(?:[^\:]+)?\:(?P<registrar_abuse_mail>[^\n]+)
                | (?:Registr(?:ar|y)\s*)?Abuse\s*[^\:]+phone(?:[^\:]+)?\:(?P<registrar_abuse_phone>[^\n]+)

                # Registrant Data
                | (?:Registrant|owner)\s*ID(?:[^\:]+)?\:(?P<registrant_id>[^\n]+)
                | (?:Registrant|owner)(?:(?:Contact)?(?:\s*Name(?:[^\:]+)?|\s*))\:(?P<registrant_name>[^\n]+)
                | (?:(?:Registrant|owner)\s*|\n)(?:Organi[zs][^\:]+|Company|Org\.?\s*)
                    (?:[^\:]+)?\:(?P<registrant_org>[^\n]+)
                | (?:Registrant|owner)\s*(?:Contact\s*)?(?:E\-?)?mail(?:[^\:]+)?\:(?P<registrant_email>[^\n]+)
                | (?:Registrant|owner)\s*Country(?:[^\:]+)?\:(?P<registrant_country>[^\n]+)
                | (?:Registrant|owner)\s*(?:State|Province)(?:[^\:]+)?\:(?P<registrant_state>[^\n]+)
                | (?:Registrant|owner)\s*City(?:[^\:]+)?\:(?P<registrant_city>[^\n]+)
                | (?:Registrant|owner)\s*(?:Street|Address)(?:[^\:]+)?\:(?P<registrant_street>[^\n]+)
                | (?:Registrant|owner)\s*(?:Postal|Post|Zip)(?:[^\:]+)?\:(?P<registrant_postal>[^\n]+)
                | (?:Registrant|owner)\s*Phone(?:[^\:]+)?\:(?P<registrant_phone>[^\n]+)
                | (?:Registrant|owner)\s*Fax(?:[^\:]+)?\:(?P<registrant_fax>[^\n]+)

                # Registrant Billing
                | (?:Bill(?:s|ing)?)\s*ID(?:[^\:]+)?\:(?P<billing_id>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Contact)?(?:\s*Name(?:[^\:]+)?|\s*)\:(?P<billing_name>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Organi[zs][^\:]+|Company|Org\.?\s*)(?:[^\:]+)?\:(?P<billing_org>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Contact\s*)?(?:E\-?)?mail(?:[^\:]+)?\:(?P<billing_email>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*Country(?:[^\:]+)?\:(?P<billing_country>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:State|Province)(?:[^\:]+)?\:(?P<billing_state>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*City(?:[^\:]+)?\:(?P<billing_city>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Street|Address)(?:[^\:]+)?\:(?P<billing_street>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Postal|Post|Zip)(?:[^\:]+)?\:(?P<billing_postal>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*Phone(?:[^\:]+)?\:(?P<billing_phone>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*Fax(?:[^\:]+)?\:(?P<billing_fax>[^\n]+)


                # Registrant Admin
                | (?:Admin(?:istra(?:tive|sions?))?|AC\s+)\s*ID(?:[^\:]+)?\:(?P<admin_id>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*
                      (?:(?:Contact)?(?:\s*Name(?:[^\:]+)?|\s*))\:(?P<admin_name>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*(?:Organi[zs][^\:]+|Company|Org\.?\s*)
                      (?:[^\:]+)?\:(?P<admin_org>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*(?:Contact\s*)?(?:E\-?)?mail(?:[^\:]+)?\:
                    (?P<admin_email>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*Country(?:[^\:]+)?\:(?P<admin_country>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*(?:State|Province)(?:[^\:]+)?\:(?P<admin_state>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*City(?:[^\:]+)?\:(?P<admin_city>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*(?:Street|Address)
                    (?:[^\:]+)?\:(?P<admin_street>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*(?:Postal|Post|Zip)(?:[^\:]+)?\:
                    (?P<admin_postal>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*Phone(?:[^\:]+)?\:(?P<admin_phone>[^\n]+)
                | (?:Admin(?:istrative(?:tive|sions?))?|AC\s+)\s*Fax(?:[^\:]+)?\:(?P<admin_fax>[^\n]+)

                # Registrant Tech
                | (?:Tech(?:[^\:\s]+)?)\s*ID(?:[^\:]+)?\:(?P<tech_id>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*(?:(?:Contact)?(?:\s*Name(?:[^\:]+)?|\s*))\:(?P<tech_name>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*(?:Organi[zs][^\:]+|Company|Org\.?\s*)(?:[^\:]+)?\:(?P<tech_org>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*(?:Contact\s*)?Email(?:[^\:]+)?\:(?P<tech_email>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*Country(?:[^\:]+)?\:(?P<tech_country>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*(?:State|Province)(?:[^\:]+)?\:(?P<tech_state>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*City(?:[^\:]+)?\:(?P<tech_city>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*(?:Street|Addre)(?:[^\:]+)?\:(?P<tech_street>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*(?:Postal|Post)(?:[^\:]+)?\:(?P<tech_postal>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*Phone(?:[^\:]+)?\:(?P<tech_phone>[^\n]+)
                | (?:Tech(?:[^\:\s]+)?)\s*Fax(?:[^\:]+)?\:(?P<tech_fax>[^\n]+)

                # ICANN Report Url
                | URL\s+of(?:\s+the)?\s+ICANN[^\:]+\:\s*(?P<icann_report_url>https?\:\/\/[^\n]+)
            ~xisxu',
            $data,
            $match
        );

        if (empty($match)) {
            return new ArrayCollector();
        }
        // filtering result
        $match =  array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
        // make 2D array as sorted integer start with 0 if not empty
        $match = array_map(
            'array_values',
            // filter empty value
            array_map(
                function ($v) {
                    return array_filter(array_map('trim', $v));
                },
                $match
            )
        );

        if (!array_filter($match)) {
            return new ArrayCollector();
        }

        return new ArrayCollector(
            array_map(function ($v) {
                return new ArrayCollector(array_map('trim', $v));
            }, $match)
        );
    }

    /**
     * @param string $data
     *
     * @return ArrayCollector
     * @todo completion
     */
    public function parseASNDetail(string $data) : ArrayCollector
    {
        /**
         * Normalize White space and allow double new line
         */
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
            $data,
            $match
        );

        $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
        $match = array_filter(array_map('trim', $match));
        $match = key($match);
        switch ($match) {
            case 'RIPE':
                return $this->parseRIPEASNData($data);
            case 'APNIC':
                return $this->parseAPNICASNData($data);
            case 'AFRINIC':
                return $this->parseAFRINICASNData($data);
            case 'LACNIC':
                return $this->parseLACNICASNData($data);
            case 'ARIN':
                return $this->parseARINASNData($data);
        }

        return $this->parseCommonASNData($data);
    }

    /**
     * @param string $data
     * @param string|null $server
     *
     * @return array
     * @tod completion
     */
    protected function parseHandlerFromData(string $data, string $server = null)
    {
        $data = $this->normalizeWhiteSpace($data, true);
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
        $data = rtrim($this->cleanComment($data));
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

        print_r([
            'info'   => $matchFirstInfo,
            'first'  => $firstInfo,
            'offset' => $offset,
            'data' => implode("\n\n", $dataArray)
        ]);
        exit;
    }

    /**
     * @param string $data
     *
     * @return ArrayCollector
     * @todo completion
     */
    protected function parseCommonASNData(string $data) : ArrayCollector
    {
        $this->parseHandlerFromData($data);
        return new ArrayCollector();
    }

    /**
     * @param string $data
     *
     * @return ArrayCollector
     * @todo completion
     */
    protected function parseRIPEASNData(string $data) : ArrayCollector
    {
        $this->parseHandlerFromData($data, DataParser::RIPE_SERVER);
        return new ArrayCollector();
    }

    protected function parseAPNICASNData(string $data) : ArrayCollector
    {
        $this->parseHandlerFromData($data, DataParser::APNIC_SERVER);
        $collector = new ArrayCollector();
        return $collector;
    }

    /**
     * @param string $data
     *
     * @return ArrayCollector
     */
    protected function parseAFRINICASNData(string $data) : ArrayCollector
    {
        // afrinic & apnic has same whois result data
        return $this->parseAPNICASNData($data);
    }

    /**
     * @param string $data
     *
     * @return ArrayCollector
     * @todo completion
     */
    protected function parseLACNICASNData(string $data) : ArrayCollector
    {
        $this->parseHandlerFromData($data, DataParser::LACNIC_SERVER);
        return new ArrayCollector();
    }

    /**
     * @param string $data
     *
     * @return ArrayCollector
     * @todo completion
     */
    protected function parseARINASNData(string $data) : ArrayCollector
    {
        $this->parseHandlerFromData($data, DataParser::ARIN_SERVER);
        return new ArrayCollector();
    }
}
