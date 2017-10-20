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

use Pentagonal\WhoIs\Abstracts\WhoIsResultAbstract;
use Pentagonal\WhoIs\Interfaces\RecordASNNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordDomainNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordNetworkInterface;
use Pentagonal\WhoIs\Util\DataParser;
use Pentagonal\WhoIs\Util\TransportClient;

/**
 * Class WhoIsResult
 * @package Pentagonal\WhoIs\App
 * By default on string result is parse on JSON
 * @todo completion Detail & Method
 */
class WhoIsResult extends WhoIsResultAbstract
{
    /* --------------------------------------------------------------------------------*
     |                                   UTILITY                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Helper to Get First Data or ->
     *
     * @param ArrayCollector $collector
     * @param $offset
     * @param null $default
     *
     * @return null
     */
    protected function getFirstOr(ArrayCollector $collector, $offset, $default = null)
    {
        /**
         * @var ArrayCollector $collector
         */
        $collector = $collector->get($offset, []);
        if (is_array($collector)) {
            return reset($collector) ?: $default;
        }

        if ($collector instanceof ArrayCollector) {
            return $collector->first()?: $default;
        }

        return $default;
    }

    /**
     * Parse Domain Detail
     *
     * @param string $data
     * @param ArrayCollector $collector
     *
     * @return ArrayCollector
     */
    protected function parseDetailDataForDomain(string $data, ArrayCollector $collector) : ArrayCollector
    {
        $dataParser = $this->getDataParser();
        $dataDomain  = $collector[static::KEY_DOMAIN];
        // just check for first use especially for be domain
        $match = $this->parseDomainDetail($this->getOriginalResultString());
        $collector[static::KEY_DOMAIN] = $dataDomain;
        if ($match->count() === 0) {
            $registeredStatus = DataParser::getRegisteredDomainStatus($data);
            $dataDomain[static::KEY_REGISTERED] = $registeredStatus === $dataParser::STATUS_UNKNOWN
                ? null
                : (
                    $registeredStatus === $dataParser::STATUS_RESERVED
                    || $registeredStatus !== $dataParser::STATUS_UNREGISTERED
                       && ! empty($match['name_server'])
                       && count($match['name_server']) > 0
                );
            return $collector;
        }

        $reportUrl = $this->getFirstOr($match, 'icann_report_url');
        $dnsSecStatusArray = (array) $match['domain_dnssec_status'];
        $dnsSecStatus = reset($dnsSecStatusArray) ?: null;
        if (count($dnsSecStatusArray) > 0) {
            $dnsSecStatus = reset($dnsSecStatusArray);
            foreach ($dnsSecStatusArray as $value) {
                if (preg_match('/[^a-z0-9\_\-\s]/i', $value)) {
                    continue;
                }
                $dnsSecStatus = $value;
                break;
            }
        }

        // domain
        $dataDomain[static::KEY_ID]                         = $this->getFirstOr($match, 'domain_id');
        $dataDomain[static::KEY_STATUS]                     = (array) $match['domain_status'];
        $dataDomain[static::KEY_NAME_SERVER]                = (array) $match['name_server'];
        $dataDomain[static::KEY_NAME_SERVER]                = array_map(
            'strtolower',
            array_map('trim', $dataDomain[static::KEY_NAME_SERVER])
        );
        $dataDomain[static::KEY_DNSSEC][static::KEY_STATUS] = $dnsSecStatus;
        $dataDomain[static::KEY_DNSSEC][static::KEY_DATA]   = (array) $match['domain_dnssec'];
        $collector[static::KEY_DOMAIN] = $dataDomain;

        // date
        $dataDate = $collector[static::KEY_DATE];
        $createdDate = $this->getFirstOr($match, 'date_created');
        $createdDate = $createdDate
            ? str_replace(['. ', '. '], '', trim($createdDate, '.'))
            : $createdDate;
        $updateDate = $this->getFirstOr($match, 'date_updated');
        $updateDate = $updateDate
            ? str_replace(['. ', '. '], '', trim($updateDate, '.'))
            : $updateDate;
        $expireDate = $this->getFirstOr($match, 'date_expired');
        $expireDate = $expireDate
            ? str_replace(['. ', '. '], '', trim($expireDate, '.'))
            : $expireDate;
        if ($createdDate && is_int($createdDateNew = @strtotime($createdDate))) {
            $createdDate = gmdate('c', $createdDateNew);
        }
        if ($updateDate && is_int($updateDateNew = @strtotime($updateDate))) {
            $updateDate = gmdate('c', $updateDateNew);
        }

        if ($expireDate && is_int($expireDateNew = @strtotime($expireDate))) {
            $expireDate = gmdate('c', $expireDateNew);
        }

        $updateDb = $this->getFirstOr($match, 'date_last_update_db');
        $updateDb = $updateDb
            ? str_replace(['. ', '. '], '', trim($updateDb, '.'))
            : $updateDb;
        $updateDb = $updateDb
            ? (
                @strtotime($updateDb)
                ? $updateDb
                : preg_replace('/^[^\:]+\:\s*/', '', $updateDb)
            )
            : null;
        if ($updateDb && ($updateDbNew = strtotime($updateDb))) {
            $updateDb = gmdate('c', $updateDbNew);
        }

        $dataDate[static::KEY_CREATE] = $createdDate;
        $dataDate[static::KEY_UPDATE] = $updateDate;
        $dataDate[static::KEY_EXPIRE] = $expireDate;
        $dataDate[static::KEY_UPDATE_DB] = $updateDb;
        $collector[static::KEY_DATE] = $dataDate;
        // registrar
        $registrar = $collector[static::KEY_REGISTRAR];
        $registrar[static::KEY_IANA_ID] = $this->getFirstOr($match, 'registrar_iana_id');
        $registrar[static::KEY_ID]      = $this->getFirstOr($match, 'registrar_id');
        $registrar[static::KEY_NAME]    = $this->getFirstOr($match, 'registrar_name');
        $registrar[static::KEY_ORGANIZATION] = $this->getFirstOr($match, 'registrar_org');
        $registrar[static::KEY_EMAIL]        = $this->getFirstOr($match, 'registrar_email');
        $registrar[static::KEY_COUNTRY]      = $this->getFirstOr($match, 'registrar_country');
        $registrar[static::KEY_CITY]         = $this->getFirstOr($match, 'registrar_city');
        $registrar[static::KEY_STREET]       = (array) $match['registrar_street'];
        $registrar[static::KEY_POSTAL_CODE]  = $this->getFirstOr($match, 'registrar_postal');
        $registrar[static::KEY_STATE]        = $this->getFirstOr($match, 'registrar_state');
        $registrar[static::KEY_PHONE]        = (array) $match['registrar_phone'];
        $registrar[static::KEY_FAX]          = (array) $match['registrar_fax'];

        $match['registrar_url'] = (array) $match->get('registrar_url');
        if (!empty($match['registrar_url'])) {
            $match['registrar_url'] = array_map(function ($v) {
                $v = trim($v);
                if (!preg_match('/^(?:(?:http|ftp)s?)\:\/\//i', $v)) {
                    $v = "http://{$v}";
                }
                return $v;
            }, $match['registrar_url']);
        }

        $registrar[static::KEY_ABUSE][static::KEY_URL] = (array) $match['registrar_url'];
        $registrarAbuseEmail = (array) $match['registrar_abuse_mail'];
        // filtering invalid mail contain double periods
        $registrarAbuseEmail = array_map(function ($email) {
            return ! $email || ! is_string($email) || strpos($email, '..') !== false
                ? null
                : $email;
        }, $registrarAbuseEmail);
        $registrarAbuseEmail = array_filter($registrarAbuseEmail);

        if (empty($registrarAbuseEmail) && !empty($registrar[static::KEY_EMAIL])) {
            $registrarAbuseEmail = [$registrar[static::KEY_EMAIL]];
        }
        $registrarAbusePhone = (array) $match['registrar_abuse_phone'];
        if (empty($registrarAbusePhone) && !empty($registrar[static::KEY_PHONE])) {
            $registrarAbusePhone = $registrar[static::KEY_PHONE];
        }
        $registrar[static::KEY_ABUSE][static::KEY_EMAIL] = $registrarAbuseEmail;
        $registrar[static::KEY_ABUSE][static::KEY_PHONE] = $registrarAbusePhone;

        $collector[static::KEY_REGISTRAR] = $registrar;

        // ----------------- REGISTRANT
        $registrant = $collector[static::KEY_REGISTRANT];
        // check if is maybe invalid mail eg: dot ph is hidden
        $email = $this->getFirstOr($match, 'registrant_email');
        $email = ! $email || ! is_string($email) || strpos($email, '..') !== false
            ? null
            : $email;
        $registrant[static::KEY_DATA] = [
            static::KEY_ID => $this->getFirstOr($match, 'registrant_id'),
            static::KEY_NAME => $this->getFirstOr($match, 'registrant_name'),
            static::KEY_ORGANIZATION => $this->getFirstOr($match, 'registrant_org'),
            static::KEY_EMAIL        => $email,
            static::KEY_COUNTRY      => $this->getFirstOr($match, 'registrant_country'),
            static::KEY_CITY         => $this->getFirstOr($match, 'registrant_city'),
            static::KEY_STREET       => (array) $match['registrant_street'],
            static::KEY_POSTAL_CODE  => $this->getFirstOr($match, 'registrant_postal'),
            static::KEY_STATE        => $this->getFirstOr($match, 'registrant_state'),
            static::KEY_PHONE        => (array) $match['registrant_phone'],
            static::KEY_FAX          => (array) $match['registrant_fax'],
        ];

        // check if is maybe invalid mail eg: dot ph is hidden
        $email = $this->getFirstOr($match, 'billing_email');
        $email = ! $email || ! is_string($email) || strpos($email, '..') !== false
            ? null
            : $email;
        $registrant[static::KEY_BILLING] = [
            static::KEY_ID           => $this->getFirstOr($match, 'billing_id'),
            static::KEY_NAME         => $this->getFirstOr($match, 'billing_name'),
            static::KEY_ORGANIZATION => $this->getFirstOr($match, 'billing_org'),
            static::KEY_EMAIL        => $email,
            static::KEY_COUNTRY      => $this->getFirstOr($match, 'billing_country'),
            static::KEY_CITY         => $this->getFirstOr($match, 'billing_city'),
            static::KEY_STREET       => (array) $match['billing_street'],
            static::KEY_POSTAL_CODE  => $this->getFirstOr($match, 'billing_postal'),
            static::KEY_STATE        => $this->getFirstOr($match, 'billing_state'),
            static::KEY_PHONE        => (array) $match['billing_phone'],
            static::KEY_FAX          => (array) $match['billing_fax'],
        ];

        // check if is maybe invalid mail eg: dot ph is hidden
        $email = $this->getFirstOr($match, 'tech_email');
        $email = ! $email || ! is_string($email) || strpos($email, '..') !== false
            ? null
            : $email;
        $registrant[static::KEY_TECH] = [
            static::KEY_ID           => $this->getFirstOr($match, 'tech_id'),
            static::KEY_NAME         => $this->getFirstOr($match, 'tech_name'),
            static::KEY_ORGANIZATION => $this->getFirstOr($match, 'tech_org'),
            static::KEY_EMAIL        => $email,
            static::KEY_COUNTRY      => $this->getFirstOr($match, 'tech_country'),
            static::KEY_CITY         => $this->getFirstOr($match, 'tech_city'),
            static::KEY_STREET       => (array) $match['tech_street'],
            static::KEY_POSTAL_CODE  => $this->getFirstOr($match, 'tech_postal'),
            static::KEY_STATE        => $this->getFirstOr($match, 'tech_state'),
            static::KEY_PHONE        => (array) $match['tech_phone'],
            static::KEY_FAX          => (array) $match['tech_fax'],
        ];

        // check if is maybe invalid mail eg: dot ph is hidden
        $email = $this->getFirstOr($match, 'admin_email');
        $email = ! $email || ! is_string($email) || strpos($email, '..') !== false
            ? null
            : $email;
        $registrant[static::KEY_ADMIN] = [
            static::KEY_ID           => $this->getFirstOr($match, 'admin_id'),
            static::KEY_NAME         => $this->getFirstOr($match, 'admin_name'),
            static::KEY_ORGANIZATION => $this->getFirstOr($match, 'admin_org'),
            static::KEY_EMAIL        => $email,
            static::KEY_COUNTRY      => $this->getFirstOr($match, 'admin_country'),
            static::KEY_CITY         => $this->getFirstOr($match, 'admin_city'),
            static::KEY_STREET       => (array) $match['admin_street'],
            static::KEY_POSTAL_CODE  => $this->getFirstOr($match, 'admin_postal'),
            static::KEY_STATE        => $this->getFirstOr($match, 'admin_state'),
            static::KEY_PHONE        => (array) $match['admin_phone'],
            static::KEY_FAX          => (array) $match['admin_fax'],
        ];

        $collector[static::KEY_REGISTRANT] = $registrant;
        $dataUrl = $collector[static::KEY_URL];
        $whoIsServers = (array) $match['whois_server'];
        if (!empty($whoIsServers) && empty($dataUrl[static::KEY_SERVER])) {
            $dataUrl[static::KEY_SERVER] = reset($whoIsServers);
        }

        // merge whois server
        $whoIsServers = array_merge(
            $whoIsServers,
            array_map(function ($server) {
                if (stripos($server, 'POST[') === 0) {
                    return trim(preg_replace('~^POST[^\|]+\|~', '', $server));
                }
                return is_string($server)
                    ? str_replace('{{domain}}', $this->getDomainName(), $server)
                    : $server;
            }, $this->networkRecord->getWhoIsServers())
        );

        $whoIsServers = array_unique(array_filter($whoIsServers));
        $dataUrl[static::KEY_WHOIS] = array_map('strtolower', array_values($whoIsServers));
        $dataUrl[static::KEY_REPORT] = $reportUrl != '' ? $reportUrl : $dataUrl[static::KEY_REPORT];
        $collector[static::KEY_URL] = $dataUrl;
        $hasSet = false;
        if (!empty($dataDomain[static::KEY_NAME_SERVER])
            && (
                !empty($dataDate[static::KEY_EXPIRE])
                && !empty($dataDate[static::KEY_CREATE])
            )
            || (
                ! empty($registrant[static::KEY_DATA][static::KEY_STREET])
                && ! empty($registrant[static::KEY_DATA][static::KEY_PHONE])
            )
            || (
                !empty($registrant[static::KEY_DATA][static::KEY_NAME])
                && !empty($registrant[static::KEY_DATA][static::KEY_CITY])
            )
        ) {
            $collector[static::KEY_DOMAIN][static::KEY_REGISTERED] = true;
            $hasSet = true;
        }

        if (!$hasSet) {
            $registeredStatus = DataParser::getRegisteredDomainStatus($this->getOriginalResultString());
            $registeredStatus = $registeredStatus === $dataParser::STATUS_UNKNOWN
                ? null
                : (
                    $registeredStatus === $dataParser::STATUS_RESERVED
                    || $registeredStatus !== $dataParser::STATUS_UNREGISTERED
                       && ! empty($dataDomain[static::KEY_NAME_SERVER])
                       && count($dataDomain[static::KEY_NAME_SERVER]) > 0
                );
            $collector[static::KEY_DOMAIN][static::KEY_REGISTERED] = $registeredStatus;
        }
        unset($registrar, $dataDate, $resultString, $dataDomain, $dataUrl);
        return $collector;
    }

    /**
     * Parse Result Autonomous System Numbers
     *
     * @param string $data
     * @param ArrayCollector $collector
     *
     * @return ArrayCollector
     * @todo completion parsing detail
     */
    protected function parseDetailDataForASN(string $data, ArrayCollector $collector) : ArrayCollector
    {
        return $collector;
    }

    /**
     * Parse Result IP Address
     *
     * @param string $data
     * @param ArrayCollector $collector
     *
     * @return ArrayCollector
     * @todo completion parsing detail
     */
    protected function parseDetailDataForIP(string $data, ArrayCollector $collector) : ArrayCollector
    {
        return $collector;
    }

    /**
     * Parse Detail
     *
     * @return ArrayCollector
     */
    protected function parseDetail() : ArrayCollector
    {
        $dataParser = $this->getDataParser();

        $this->dataDetail[static::KEY_DATA][static::KEY_RESULT][static::KEY_CLEAN] = $dataParser
            ->cleanUnwantedWhoIsResult(
                $this->getOriginalResultString()
            );

        if ($this->networkRecord instanceof RecordDomainNetworkInterface) {
            return $this->dataDetail = $this->parseDetailDataForDomain(
                $this->getOriginalResultString(),
                $this->dataDetail
            );
        }
        if ($this->networkRecord instanceof RecordASNNetworkInterface) {
            return $this->dataDetail = $this->parseDetailDataForASN(
                $this->getOriginalResultString(),
                $this->dataDetail
            );
        }

        return $this->dataDetail;
    }

    /* --------------------------------------------------------------------------------*
     |                                  INSTANCE                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @param RecordNetworkInterface $network
     * @param string $originalData
     *
     * @return WhoIsResult
     */
    public static function create(RecordNetworkInterface $network, string $originalData) : WhoIsResult
    {
        return new static($network, $originalData);
    }

    /* --------------------------------------------------------------------------------*
     |                                   GETTERS                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @final for callback parser
     * @return string
     */
    final public function getCleanData() : string
    {
        return $this->getDataDetail()[static::KEY_DATA][static::KEY_RESULT][static::KEY_CLEAN];
    }

    /**
     * Get if Registered
     *
     * @return bool|null null is unknown otherwise boolean (Reserved, Banned, Registered)
     */
    public function isRegistered()
    {
        return $this->getDataDetail()[static::KEY_DOMAIN][static::KEY_REGISTERED];
    }
}
