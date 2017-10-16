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
        $data = DataParser::normalizeWhoIsDomainResultData($data);

        preg_match_all(
            '~
                # DNSSEC Data & Status
                DNSSEC\s*\:(?P<domain_dnssec_status>[^\n]+)
                | DNSSEC\s+(?:[^\:]+)\s*\:(?P<domain_dnssec>[^\n]+)

                # Domain ID
                | (?:Registry)?\s*Domain\s+ID\:(?P<domain_id>[^\n]+)

                # Date
                | (Updated?\s*Date|Last\s*Updated?)(?:([^\:]+)?On)?(?:[^\:]+)?:(?P<date_updated>[^\n]+)
                | (?:Creat(?:e[ds]?|ions?)\s*(On|Date)|Registered)(?:[^\:]+)?:(?P<date_created>[^\n]+)
                | Expir(?:e[ds]?|y|ations?)\s*Date(?:([^\:]+)?On)?\s*:(?P<date_expired>[^\n]+)
                
                # Last Update DB & Status
                | (?:\>\>\>?)?\s*
                   (Last\s*Update\s*(?:[a-z0-9\s]+)?Whois\s*Database)\s*
                      \:\s*(?P<date_last_update_db>(?:[0-9]+[0-9\-\:\s\+TZGMU]+)?)
                | (?:Domain\s*)?(?:Flags|Status)\s*:(?P<domain_status>[^\n]+)

                # Other Data
                | Referral(?:[^\:]+)?\s*\:(?P<referral>[^\n]+)
                | Reseller(?:[^\:]+)?\s*\:(?P<reseller>[^\n]+)

                # Name Server
                | (?:N(?:ame)?\s*\_?Servers?)\s*\:(?P<name_server>[^\n]+)

                # whois
                | Whois\s*Server\s*\:(?P<whois_server>[^\n]+)

                # Registrar Data
                | Registr(?:ar|y)\s*ID\s*\:(?P<registrar_id>[^\n]+)
                | Registr(?:ar|y)(?:\s*IANA)(?:[^\:]+)?ID\s*\:(?P<registrar_iana_id>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?Name(?:[^\:]+)?\:(?P<registrar_name>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:Organiz[^\:]+|Company)(?:[^\:]+)?
                    \:(?P<registrar_org>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:Contact\s*)?Email(?:[^\:]+)?\:(?P<registrar_email>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?Country(?:[^\:]+)?\:(?P<registrar_country>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:State|Province)(?:[^\:]+)?\:(?P<registrar_state>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?City(?:[^\:]+)?\:(?P<registrar_city>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:Street|Addre)(?:[^\:]+)?\:(?P<registrar_street>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?(?:Postal|Post)(?:[^\:]+)?\:(?P<registrar_postal>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?Phone(?:[^\:]+)?\:(?P<registrar_phone>[^\n]+)
                | Registr(?:ar|y)\s*(?:Contact(?:[^\:]+)?)?Fax(?:[^\:]+)?\:(?P<registrar_fax>[^\n]+)

                | (?:Registr(?:ar|y)\s*)?Abuse\s*[^\:]+e?mail(?:[^\:]+)?\:(?P<registrar_abuse_mail>[^\n]+)
                | (?:Registr(?:ar|y)\s*)?Abuse\s*[^\:]+phone(?:[^\:]+)?\:(?P<registrar_abuse_phone>[^\n]+)

                # Registrant Data
                | (?:Registrant|owner)\s*ID(?:[^\:]+)?\:(?P<registrant_id>[^\n]+)
                | (?:Registrant|owner)\s*Name(?:[^\:]+)?\:(?P<registrant_name>[^\n]+)
                | (?:Registrant|owner)\s*(?:Organiz[^\:]+|Company)(?:[^\:]+)?\:(?P<registrant_org>[^\n]+)
                | (?:Registrant|owner)\s*(?:Contact\s*)?Email(?:[^\:]+)?\:(?P<registrant_email>[^\n]+)
                | (?:Registrant|owner)\s*Country(?:[^\:]+)?\:(?P<registrant_country>[^\n]+)
                | (?:Registrant|owner)\s*(?:State|Province)(?:[^\:]+)?\:(?P<registrant_state>[^\n]+)
                | (?:Registrant|owner)\s*City(?:[^\:]+)?\:(?P<registrant_city>[^\n]+)
                | (?:Registrant|owner)\s*(?:Street|Addre)(?:[^\:]+)?\:(?P<registrant_street>[^\n]+)
                | (?:Registrant|owner)\s*(?:Postal|Post)(?:[^\:]+)?\:(?P<registrant_postal>[^\n]+)
                | (?:Registrant|owner)\s*Phone(?:[^\:]+)?\:(?P<registrant_phone>[^\n]+)
                | (?:Registrant|owner)\s*Fax(?:[^\:]+)?\:(?P<registrant_fax>[^\n]+)

                # Registrant Billing
                | (?:Bill(?:s|ing)?)\s*ID(?:[^\:]+)?\:(?P<billing_id>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*Name(?:[^\:]+)?\:(?P<billing_name>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Organiz[^\:]+|Company)(?:[^\:]+)?\:(?P<billing_org>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Contact\s*)?Email(?:[^\:]+)?\:(?P<billing_email>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*Country(?:[^\:]+)?\:(?P<billing_country>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:State|Province)(?:[^\:]+)?\:(?P<billing_state>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*City(?:[^\:]+)?\:(?P<billing_city>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Street|Addre)(?:[^\:]+)?\:(?P<billing_street>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*(?:Postal|Post)(?:[^\:]+)?\:(?P<billing_postal>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*Phone(?:[^\:]+)?\:(?P<billing_phone>[^\n]+)
                | (?:Bill(?:s|ing)?)\s*Fax(?:[^\:]+)?\:(?P<billing_fax>[^\n]+)


                # Registrant Admin
                | (?:Admin)\s*ID(?:[^\:]+)?\:(?P<admin_id>[^\n]+)
                | (?:Admin)\s*Name(?:[^\:]+)?\:(?P<admin_name>[^\n]+)
                | (?:Admin)\s*(?:Organiz[^\:]+|Company)(?:[^\:]+)?\:(?P<admin_org>[^\n]+)
                | (?:Admin)\s*(?:Contact\s*)?Email(?:[^\:]+)?\:(?P<admin_email>[^\n]+)
                | (?:Admin)\s*Country(?:[^\:]+)?\:(?P<admin_country>[^\n]+)
                | (?:Admin)\s*(?:State|Province)(?:[^\:]+)?\:(?P<admin_state>[^\n]+)
                | (?:Admin)\s*City(?:[^\:]+)?\:(?P<admin_city>[^\n]+)
                | (?:Admin)\s*(?:Street|Addre)(?:[^\:]+)?\:(?P<admin_street>[^\n]+)
                | (?:Admin)\s*(?:Postal|Post)(?:[^\:]+)?\:(?P<admin_postal>[^\n]+)
                | (?:Admin)\s*Phone(?:[^\:]+)?\:(?P<admin_phone>[^\n]+)
                | (?:Admin)\s*Fax(?:[^\:]+)?\:(?P<admin_fax>[^\n]+)

                # Registrant Tech
                | (?:Tech(?:[^\:]+)?)\s*ID(?:[^\:]+)?\:(?P<tech_id>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*Name(?:[^\:]+)?\:(?P<tech_name>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*(?:Organiz[^\:]+|Company)(?:[^\:]+)?\:(?P<tech_org>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*(?:Contact\s*)?Email(?:[^\:]+)?\:(?P<tech_email>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*Country(?:[^\:]+)?\:(?P<tech_country>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*(?:State|Province)(?:[^\:]+)?\:(?P<tech_state>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*City(?:[^\:]+)?\:(?P<tech_city>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*(?:Street|Addre)(?:[^\:]+)?\:(?P<tech_street>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*(?:Postal|Post)(?:[^\:]+)?\:(?P<tech_postal>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*Phone(?:[^\:]+)?\:(?P<tech_phone>[^\n]+)
                | (?:Tech(?:[^\:]+)?)\s*Fax(?:[^\:]+)?\:(?P<tech_fax>[^\n]+)

                # ICANN Report Url
                | URL\s+of(?:\s+the)?\s+ICANN[^\:]+\:\s*(?P<icann_report_url>https?\:\/\/[^\n]+)
            ~xisx',
            $data,
            $match
        );

        if (empty($match)) {
            return new ArrayCollector();
        }
            // filtering result
        $match = array_filter($match, function ($key) {
            return ! is_int($key);
        }, ARRAY_FILTER_USE_KEY);
        $match = new ArrayCollector(
            array_map(function ($v) {
                $v = array_filter($v);
                return new ArrayCollector(array_map('trim', array_values($v)));
            }, $match)
        );

        return $match;
    }
}
