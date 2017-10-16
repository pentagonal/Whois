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
        $data = DataParser::normalizeWhoIsResultData($data);

        preg_match_all(
            '~
                    # dnssec
                    DNSSEC\s*\:(?P<domain_dnssec_status>[^\n]+)
                    | DNSSEC\s+(?:[^\:]+)\s*\:(?P<domain_dnssec>[^\n]+)

                    # detail top
                    | (?:Registry)?\s*Domain\s+ID\:(?P<domain_id>[^\n]+)

                    # date
                    | Updated?\s*Date\s*:(?P<date_updated>[^\n]+)
                    | (?:Creat(?:e[ds]?|ions?)\s*Date|Registered)\s*:(?P<date_created>[^\n]+)
                    | Expir(?:e[ds]?|y|ation)\s*Date\s*:(?P<date_expired>[^\n]+)
                    
                    # last update db
                    | (?:\>\>\>?)?\s*
                       (?:Last\s*Update\s*(?:[a-z0-9\s]+)?(?:\s+Whois\s*)?(?:\s+Database(?:Whois)?)?)\s*
                          \:\s*(?P<date_last_update_db>(?:[0-9]+[0-9\-\:\s\+TZGMU]+)?)
                    | (?:Domain\s*)?(?:Flags|Status)\s*:(?P<domain_status>[^\n]+)

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

                    # icann report url
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

        return new ArrayCollector(
            array_map(function ($v) {
                $v = array_filter($v);
                return new ArrayCollector(array_map('trim', array_values($v)));
            }, $match)
        );
    }
}
