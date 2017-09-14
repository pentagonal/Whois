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

namespace Pentagonal\WhoIs;

use Pentagonal\WhoIs\Util\DataGetter;
use Pentagonal\WhoIs\Util\StreamSocketTransport;
use Exception;

/**
 * Class WhoIs
 * @package Pentagonal\Whois
 *
 * For nic check
 * @uses WhoIs::getIPWithArrayDetail()
 * @uses WhoIs::getASNWithArrayDetail()
 *
 * That contain '::' it must be explode as array to better reading on result get API
 */
class WhoIs
{
    /**
     * Stored object cached whois server on property from result
     *
     * @var array
     */
    protected $cachedWhoIsServers = [];

    /**
     * Stored cached result
     *
     * @var array
     */
    protected $cachedWhoIsDomain = [];

    /**
     * Stored Verifier Object
     *
     * @var Verifier
     */
    protected $verifier;

    /**
     * WhoIs constructor.
     *
     * @param DataGetter $getter
     */
    public function __construct(DataGetter $getter)
    {
        $this->verifier = new Verifier($getter);
    }

    /**
     * @return Verifier
     */
    public function getVerifier()
    {
        return $this->verifier;
    }

    /**
     * Run process stream connection
     *
     * @param string $domain    domain name
     * @param string $server    server url host
     * @return string
     */
    protected function runStreamConnection($domain, $server)
    {
        try {
            $stream = new StreamSocketTransport($server);
        } catch (Exception $exception) {
            $stream = new StreamSocketTransport($server);
        }

        if (!$stream->write("{$domain}\r\n")) {
            $stream->close();
            throw new \UnexpectedValueException(
                'Can not put data into whois server',
                E_ERROR
            );
        }
        $data = '';
        while (!$stream->eof()) {
            $data .= $stream->read(4096);
        }
        $stream->close();
        unset($stream);

        return $data;
    }

    /**
     * Clean result data for unwanted
     *
     * @param string $data
     * @return string
     */
    private function cleanData($data)
    {
        $data = trim($data);
        $data = preg_replace(
            '/(\>\>\>|URL\s+of\s+the\s+ICANN\s+WHOIS).*/is',
            '',
            $data
        );
        if (strpos($data, '#') !== false || strpos($data, '%') !== false) {
            $data = implode(
                "\n",
                array_filter(
                    explode("\n", $data),
                    function ($data) {
                        return !(
                            strpos(trim($data), '#') === 0
                            || strpos(trim($data), '%') === 0
                        );
                    }
                )
            );
        }

        return trim($data);
    }

    /**
     * Get Alternative result from additional server host if exists
     *
     * @param string $domainName
     * @param string $data
     * @param string $oldServer
     * @return array
     */
    protected function getForWhoIsServerAlternative(
        $domainName,
        $data,
        $oldServer
    ) {
        try {
            $data = $this->cleanData($data);
            preg_match('/Whois\s+Server:\s*(?P<server>[^\s]+)/i', $data, $match);
            if (empty($match['server'])) {
                return [
                    $oldServer => $data
                ];
            }

            $data2 = $this->runStreamConnection($domainName, "{$match['server']}:43");
            if (!empty($data2)) {
                $array = explode('.', $domainName);
                $this->cachedWhoIsServers[end($array)] = $match['server'];
                $data2 = $this->cleanData($data2);
                return [
                    $oldServer => $data,
                    $match['server'] => $data2
                ];
            }
        } catch (\Exception $e) {
        }

        return [
            $oldServer => $data
        ];
    }

    /**
     * Internal Use only
     *
     * @var bool
     */
    protected $allowNonDomain = false;

    /**
     * Get whois server from given domain name
     *
     * @param string $domain
     * @return mixed|string
     * @throws \UnexpectedValueException
     * @throws \DomainException
     */
    public function getWhoIsServer($domain)
    {
        if (!$this->verifier->isTopDomain($domain) && !$this->allowNonDomain) {
            throw new \DomainException(
                "Domain is not valid!",
                E_ERROR
            );
        }

        $this->allowNonDomain = false;
        $array = explode('.', $domain);
        if (! isset($this->cachedWhoIsServers[end($array)])) {
            $this->cachedWhoIsServers[end($array)] = false;
            $body = $this->runStreamConnection($domain, DataGetter::BASE_ORG_URL . ":43");
            preg_match('/whois:\s*(?P<server>[^\n]+)/i', $body, $match);
            if (!empty($match['server']) && ($server = trim($match['server']) != '')) {
                $this->cachedWhoIsServers[end($array)] = $match['server'];
                return $match['server'];
            }
        }

        if ($this->cachedWhoIsServers[end($array)]) {
            return $this->cachedWhoIsServers[end($array)];
        }

        throw new \UnexpectedValueException(
            'Whois check failed ! Whois server not found.',
            E_ERROR
        );
    }

    /**
     * Get whois result detail from given domain name
     *
     * @param string $domainName
     * @return array[]
     */
    public function getWhoIsWithArrayDetail($domainName)
    {
        $whoIs = $this->getWhoIs($domainName);
        foreach ($whoIs as $key => $value) {
            $whoIs[$key] = $this->parseDataDetail($value);
        }

        return $whoIs;
    }

    /**
     * Parse data from Result
     * @internal
     * @param string $string
     * @return array
     */
    private function parseDataDetail($string)
    {
        $string = explode("\n", $string);
        $data = [];
        foreach ($string as $value) {
            if (strpos($value, ':') !== false) {
                $value = explode(':', $value);
                $key = $this->convertNameToUpperCaseTrimmed((string) array_shift($value));
                $data[$key][] = trim(implode(':', $value));
            }
        }

        return $data;
    }

    /**
     * Convert to uppercase on first name for data detail
     *
     * @internal
     * @param string $name
     * @return string
     */
    private function convertNameToUpperCaseTrimmed($name)
    {
        $string = ucwords(
            trim(trim($name), '.')
        );
        return preg_replace('/(\s)+/', '$1', $string);
    }

    /**
     * Get Whois Detail
     *
     * @param string $domainName
     * @return array
     */
    public function getWhoIs($domainName)
    {
        $whoIsServer = $this->getWhoIsServer($domainName);
        return $this->getForWhoIsServerAlternative(
            $domainName,
            $this->runStreamConnection($domainName, "{$whoIsServer}:43"),
            $whoIsServer
        );
    }

    /**
     * Clean result from ASN data
     *
     * @internal
     * @param string $asnResult
     * #param string $asn , $asn
     * @return string
     */
    private function cleanASN($asnResult)
    {
        /*
        preg_match("/(?P<data>\n(\#|\%)[^\#|\%]+{$asn}[^\n]+\n.*)/ism", $asnResult, $match);
        if (!empty($match['data'])) {
            $asnResult = trim($match['data']);
        }*/
        $asnResult = $this->cleanData($asnResult);
        return $asnResult;
    }

    /**
     * @param string $asn
     * @return string
     */
    public function getASNData($asn)
    {
        $asn = $this->verifier->validateASN($asn);
        if (!$asn) {
            throw new \InvalidArgumentException(
                'Invalid asn number.',
                E_USER_ERROR
            );
        }
        $this->allowNonDomain = true;
        $whoIsServer = $this->getWhoIsServer($asn);
        return $this->cleanASN(
            $this->runStreamConnection($asn, "{$whoIsServer}:43")
        );
    }

    /**
     * @param string $asn
     * @return array
     */
    public function getASNWithArrayDetail($asn)
    {
        return $this->parseForNicData($this->getASNData($asn));
    }

    /**
     * @param string $data
     * @return string
     */
    public function getIpData($data)
    {
        $ipData = @gethostbyaddr(@gethostbyname($data));
        if (!$ipData) {
            throw new \InvalidArgumentException(
                'Invalid address.',
                E_USER_ERROR
            );
        }

        $this->allowNonDomain = true;
        $whoIsServer = $this->getWhoIsServer($ipData);
        return $this->cleanASN(
            $this->runStreamConnection($ipData, "{$whoIsServer}:43")
        );
    }

    /**
     * Get IP detail
     *
     * @param string $address
     * @return array
     */
    public function getIPWithArrayDetail($address)
    {
        return $this->parseForNicData($this->getIpData($address));
    }

    /**
     * Helper Parser
     * @internal
     * @param string $dataResult
     * @return array
     */
    private function parseForNicData($dataResult)
    {
        $data = str_replace("\r\n", "\n", $dataResult);
        $data = preg_replace('/[\n]{2,}/', "\n\n", $data);

        $data = explode("\n\n", $data);
        $data = array_filter($data);
        $data2 = [];
        foreach ($data as $key => $value) {
            $explode = explode("\n", $value);
            $lastName = null;
            $c = 0;
            $countKey = 0;
            foreach ($explode as $v) {
                $c++;
                preg_match('/(?:^(?P<name>[^\:]+)\:)?(?P<value>.*)/', $v, $m);
                $theName = !empty($m['name']) ? trim($m['name']) : null;
                if ($c === 1) {
                    if (!$theName) {
                        $theName = $key;
                    }
                    $key = $theName;
                    if (isset($data2[$key][$countKey])) {
                        $countKey +=1;
                    }
                    $data2[$key][$countKey] = [];
                }
                $theValue = trim($m['value']);
                if (isset($theName)) {
                    if (isset($data2[$key][$countKey][$theName])) {
                        $data2[$key][$countKey][$theName] .= "::{$theValue}";
                        $lastName = $theName;
                        continue;
                    }
                    $data2[$key][$countKey][$theName] = $theValue;
                    $lastName = $theName;
                    continue;
                }
                if (isset($data2[$key][$countKey][$lastName]) && $theValue != '') {
                    $data2[$key][$countKey][$lastName] .= "::{$theValue}";
                }
            }
        }

        return $data2;
    }
}
