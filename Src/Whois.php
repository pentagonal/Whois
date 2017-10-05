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

use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\Util\Collection;
use Pentagonal\WhoIs\Util\DataGetter;
use Pentagonal\WhoIs\Util\ExtensionStorage;
use Pentagonal\WhoIs\Util\StreamSocketTransport;
use Exception;
use InvalidArgumentException;

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
    const REGEX_GET_SERVER  = '/(Whois(\s+Server)?):\s*(?P<server>[^\s]+)/i';
    const SERVER_PORT = 43;

    /**
     * Cache Prefix Name
     */
    const CACHE_PREFIX_SERVER = 'Pentagonal_WhoIs_Server_';
    const CACHE_PREFIX_DOMAIN = 'Pentagonal_WhoIs_Domain_';

    /**
     * Stored object cached whois server on property from result
     *
     * @var array|ExtensionStorage[]
     */
    protected $temporaryCachedWhoIsServers = [];

    /**
     * @var int
     */
    protected $cacheTimeOut = 3600;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Stored Verifier Object
     *
     * @var Verifier
     */
    protected $verifier;

    /**
     * Internal Use only
     *
     * @var bool
     */
    private $allowNonDomain = false;

    /**
     * WhoIs constructor.
     *
     * @param DataGetter $getter
     * @param CacheInterface $cache
     */
    public function __construct(DataGetter $getter, CacheInterface $cache = null)
    {
        $this->verifier = new Verifier($getter);
        $this->cache = $cache;
    }

    /**
     * @return Verifier
     */
    public function getVerifier()
    {
        return $this->verifier;
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param int $timeout
     */
    public function setCacheTimeOut($timeout)
    {
        if (!is_numeric($timeout)) {
            throw new InvalidArgumentException(
                'Argument must be as an integer'
            );
        }
        $this->cacheTimeOut = (int) $timeout;
    }

    /**
     * @param string $identifier
     * @param mixed  $value
     * @return bool
     */
    protected function putCache($identifier, $value)
    {
        if ($this->cache) {
            $this->cache->put($identifier, $value, $this->cacheTimeOut);
            return true;
        }

        return false;
    }

    /**
     * @param string $identifier
     *
     * @return mixed|null
     */
    protected function getFromCache($identifier)
    {
        if ($this->cache && $this->cache->exist($identifier)) {
            return $this->cache->get($identifier);
        }

        return null;
    }

    /**
     * @param string $identifier
     */
    protected function deleteCache($identifier)
    {
        if ($this->cache) {
            $this->cache->delete($identifier);
        }
    }

    /**
     * @return array
     */
    public function getTemporaryCachedWhoIsServers()
    {
        return $this->temporaryCachedWhoIsServers;
    }

    /**
     * Run process stream connection
     *
     * @param string $domain    domain name
     * @param string $server    server url host
     * @return string
     * @throws Exception
     */
    protected function getResultFromStream($domain, $server)
    {
        if (!is_string($domain)) {
            throw new InvalidArgumentException(
                'Domain name or IP must be as a string'
            );
        }

        $server = $this->parseWhoIsServer($server);
        $identifier = self::CACHE_PREFIX_DOMAIN . md5($domain . $server);
        $cache = $this->getFromCache($identifier);
        if (is_string($cache)) {
            return $cache;
        }

        try {
            $stream = new StreamSocketTransport($server);
        } catch (TimeOutException $exception) {
            $stream = new StreamSocketTransport($server);
        } catch (Exception $exception) {
            throw $exception;
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

        // close stream
        $stream->close();
        unset($stream);
        // clean for whitespace on first
        $data = $this->cleanWhiteSpaceFirst($data);
        $this->putCache($identifier, $data);
        return $data;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    protected function cleanWhiteSpaceFirst($data)
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Data must be as as string %s given.',
                    gettype($data)
                ),
                E_WARNING
            );
        }

        $data = preg_replace(
            [
                '/^(\s)+/sm', # clean each line start with whitespace
                '/(\s)+/sm'   # clean multiple whitespace
            ],
            [
                '',
                '$1'
            ],
            $data
        );

        return trim($data);
    }

    /**
     * @param string $domainName IP - ASN - Domain name
     * @param string $server
     *
     * @return string
     */
    public function getFromServer($domainName, $server)
    {
        if ($this->verifier->isIPv4($domainName) || $this->verifier->validateASN($domainName)) {
            $this->allowNonDomain = true;
        }

        if (! $this->verifier->isTopDomain($domainName) && ! $this->allowNonDomain) {
            throw new \DomainException(
                "Domain is not valid!",
                E_ERROR
            );
        }

        // reset
        $this->allowNonDomain = false;
        return $this->getResultFromStream($domainName, $server);
    }

    /**
     * Clean result data for unwanted
     *
     * @param string $data
     * @return string
     */
    public static function cleanResultData($data)
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Data must be as a string %s given.',
                    gettype($data)
                ),
                E_WARNING
            );
        }

        $data = trim($data);
        $data = preg_replace(
            ['/(\>\>\>?|URL\s+of\s+the\s+ICANN\s+WHOIS).*/is', '/([\n])+/s'],
            ['', '$1'],
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
     * @param string $server
     *
     * @return string
     */
    public function parseWhoIsServer($server)
    {
        if (!is_string($server)) {
            throw new InvalidArgumentException(
                'Server must be as a string'
            );
        }

        $server = !preg_match('/^[a-z]+\:\/\//', $server)
            ? 'whois://'. $server
            : $server;
        $server = parse_url($server, PHP_URL_HOST);
        return $server . ':' . self::SERVER_PORT;
    }

    /**
     * @param string $stringDomain
     *
     * @return string
     * @throws InvalidArgumentException
     */
    protected function getParsedExtension($stringDomain)
    {
        if (!is_string($stringDomain)) {
            throw new InvalidArgumentException(
                'Domain name must be as a string'
            );
        }

        $arr = explode('.', $stringDomain);
        $ext = end($arr);
        $ext = $ext ? $ext : null;
        if (!$ext || trim($ext) == '') {
            throw new \DomainException(
                sprintf(
                    'Invalid domain name for %s',
                    $stringDomain
                ),
                E_WARNING
            );
        }

        // fix
        return strtolower(trim($ext));
    }

    /**
     * @param string $extension
     *
     * @return mixed|null|ExtensionStorage
     */
    private function getExtensionCache($extension)
    {
        if (isset($this->temporaryCachedWhoIsServers[$extension])) {
            return $this->temporaryCachedWhoIsServers[$extension];
        }

        $identifier = self::CACHE_PREFIX_SERVER .  $extension;
        $extensionCache = $this->getFromCache($identifier);
        if (!is_string($extensionCache)) {
            return null;
        }
        $data = @unserialize($extensionCache);
        if ($data instanceof ExtensionStorage) {
            $this->temporaryCachedWhoIsServers[$extension] = $data;
        }

        $this->deleteCache($identifier);
        return null;
    }

    /**
     * @param string $extension
     * @param ExtensionStorage $data
     */
    private function putExtensionCache($extension, ExtensionStorage $data)
    {
        $identifier = self::CACHE_PREFIX_SERVER .  $extension;
        $this->putCache($identifier, $data);
    }

    /**
     * @param string $data
     *
     * @return null|string
     * @throws InvalidArgumentException
     */
    public function parseWhoIsServerFromData($data)
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Data must be as a string %s given',
                    gettype($data)
                ),
                E_WARNING
            );
        }

        preg_match(self::REGEX_GET_SERVER, $data, $match);
        return isset($match['server']) ? $match['server'] : null;
    }

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
        $extension = $this->getParsedExtension($domain);
        $extensions = $this->getExtensionCache($extension);
        if ($extension && count($extensions) > 0) {
            return $extensions->reset();
        }

        $this->temporaryCachedWhoIsServers[$extension] = new ExtensionStorage();
        $body = $this->getFromServer($domain, DataGetter::BASE_ORG_URL);
        if (is_string($body) && trim($body) != '') {
            $server = $this->parseWhoIsServerFromData($body);
            if ($server) {
                $this->temporaryCachedWhoIsServers[$extension]->clear();
                $this->temporaryCachedWhoIsServers[$extension]->add($server);
                $this->putExtensionCache($extension, $this->temporaryCachedWhoIsServers[$extension]);

                return $server;
            }
        }

        throw new \UnexpectedValueException(
            'Whois check failed ! Whois server not found.',
            E_ERROR
        );
    }

    /**
     * Get Alternative result from additional server host if exists
     *
     * @param string $domainName
     * @param string $data
     * @param bool $clean
     * @return string[]|Collection
     */
    private function getFromAlternativeServer($domainName, $data, $clean = false)
    {
        $extension = $this->getParsedExtension($domainName);
        $extensions = $this->temporaryCachedWhoIsServers[$extension];
        $reset     = $extensions->reset();
        if (count($extensions) > 1) {
            $newServer = $extensions->next();
            $extensions->clear();
            $extensions->merge([$reset, $newServer]);
        } else {
            preg_match(self::REGEX_GET_SERVER, $data, $match);
            if (empty($match['server'])) {
                return new Collection([$reset => $data]);
            }

            $newServer = $match['server'];
            $extensions->clear();
            $extensions->merge([$reset, $newServer]);
            $this->putExtensionCache($extension, $extensions);
        }
        $this->temporaryCachedWhoIsServers[$extension] = $extensions;
        try {
            $data2 = $this->getFromServer($domainName, $newServer);
            if ($clean) {
                $data2 = $this->cleanResultData($data2);
            }

            if (!empty($data2)) {
                return new Collection([
                    $reset     => $data,
                    $newServer => $data2
                ]);
            }
        } catch (\Exception $e) {
        }

        return new Collection([$reset => $data]);
    }

    /**
     * Get whois result detail from given domain name
     *
     * @param string $domainName
     * @param bool $followWhoIs
     *
     * @return Collection|string[]
     */
    public function getWhoIsWithArrayDetail($domainName, $followWhoIs = false)
    {
        $whoIs = $this->getWhoIs($domainName, true, $followWhoIs);
        foreach ($whoIs as $key => $value) {
            $whoIs[$key] = $this->parseDataDetail($value);
        }

        return $whoIs;
    }

    /**
     * Parse data from Result
     * @internal
     * @param string $string
     * @return Collection
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

        return new Collection($data);
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
     * @param string $domainName
     * @param bool $clean
     * @param bool $followWhoIs
     *
     * @return string[]|Collection
     */
    public function getWhoIs($domainName, $clean = false, $followWhoIs = false)
    {
        $whoIsServer = $this->getWhoIsServer($domainName);
        $result = $this->getResultFromStream($domainName, $whoIsServer);
        if ($clean) {
            $result = $this->cleanResultData($result);
        }
        if (!$followWhoIs) {
            return new Collection([$whoIsServer => $result]);
        }

        return $this->getFromAlternativeServer(
            $domainName,
            $result,
            $clean
        );
    }

    /**
     * @param string $domainName
     *
     * @return bool|null null if unknown result or maybe empty result or bool if registered or not
     */
    public function isDomainRegistered($domainName)
    {
        if (!is_string($domainName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid domain name. Domain name must be as a string %s given',
                    gettype($domainName)
                ),
                E_WARNING
            );
        }
        if (!$this->getVerifier()->isTopDomain($domainName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The %s is not valid domain name',
                    $domainName
                ),
                E_WARNING
            );
        }

        $data = $this->getWhoIs($domainName, false)->getArrayCopy();
        $data = reset($data);
        if (!is_string($data)) {
            return null;
        }

        return $this->isDomainRegisteredParser($data);
    }

    /**
     * Domain Parser Registered or Not Callback
     *
     * @param string $data
     *
     * @return bool|null null if unknown result or maybe empty result / limit exceeded or bool if registered or not
     * @throws InvalidArgumentException
     */
    protected function isDomainRegisteredParser($data)
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid data. Data must be as a string %s given',
                    gettype($data)
                ),
                E_WARNING
            );
        }

        $cleanData = trim($this->cleanResultData($data));
        // check if empty result
        if ($cleanData === '') {
            // if cleanData is empty & data is not empty check entries
            if ($data && preg_match('/No\s+entries(?:\s+found)?|Not(?:hing)?\s+found/i', $data)) {
                return false;
            }

            return null;
        }

        // array check for detailed content only that below is not registered
        $matchUnRegistered = [
            'domain not found',
            'not found',
            'no data found',
            'no match',
            'this domain name has not been registered',
            'the queried object does not exist'
        ];
        // clean dot on both side
        $cleanData = trim($cleanData, '.');
        if (in_array(strtolower($cleanData), $matchUnRegistered)) {
            return false;
        }

        /**
         * First word that match of given domain
         */
        $firstTagRegex = '/^(?:
            No\s+match\s+for
            | No\s+Match
            | Not\s+found\s*\:?
            | No\s*Data\s+Found
            | Domain\s+not\s+found
            | Invalid\s+query\s+or\s+domain
            | The\s+queried\s+object\s+does\s+not\s+exist
            | (?:Th(?:is|e))\s+domain(?:\s*name)?\s+has\s*not\s+been\s+register
        )/ix';
        $lastRegex = '/^(?:.+)\s*(?:No\s+match|not\s+exist\s+[io]n\s+database(?:[\!]+)?)$/';
        // check
        if (preg_match($firstTagRegex, $cleanData) # regex not match or not found
            || preg_match(
                '/Domain\s+Status\s*\:\s*(available|No\s+Object\s+Found)/im',
                $cleanData
            )
            # match for queried object
            || preg_match($lastRegex, $cleanData)
        ) {
            return false;
        }
        # match domain with name and with status available extension for eg: .be
        if (preg_match('/Domain\s*\:(?:[^\n]+)/i', $cleanData)) {
            if (preg_match('/(?:Domain\s+)?Status\s*\:\s*(?:AVAILABLE|(?:No\s+Object|Not)\s+Found)/i', $cleanData)) {
                return false;
            }
            if (preg_match('/(?:Domain\s+)?Status\s*\:\s*NOT\s*AVAILABLE/i', $cleanData)) {
                return true;
            }
        }

        /**
         * Regex Collection
         */
        # Contact , tech or billing detail
        $regexContactAndStatus = '/
            (
                Registr(?:ar|y|nt)\s[^\:]+
                | Whois\s+Server
                | (?:Phone|Registrar|Contact|(?:admin|tech)-c|Organisations?)
            )\s*\:\s*(?P<data>[^\n]+)
        /ix';

        # Name Server
        $regexNameServer = '/(?:(?:Name\s+Servers?|n(?:ame)?servers?)\s*\:\s*)(?P<server>[^\n]+)/i';
        # Reserved Domain
        $regexReserved = '/^\s*Reserved\s*By/i';
        if (preg_match($regexReserved, $cleanData) # -> reserved domain so it will be registered
            # else check contact or status billing, tech or contact
            || preg_match($regexContactAndStatus, $cleanData, $matchData)
            && !empty($matchData['data'])
            && (
                # match for name server
                preg_match($regexNameServer, $cleanData, $matchServer)
                && !empty($matchServer['server'])
                # match for billing
                || preg_match(
                    '/(?:Billing|Tech)\s+Email\s*:(?P<data>[^\n]+)/i',
                    $cleanData,
                    $matchDataResult
                ) && !empty($matchDataResult['data'])
           )
        ) {
            return true;
        }
        # check if on limit whois check
        if (preg_match('/exceeded\s(.+)?limit|limit\s+exceed/i', $cleanData)) {
            return null;
        }

        return false;
    }

    /**
     * Clean result from ASN data
     *
     * @internal
     * @param string $asnResult
     * @return string
     */
    private function cleanASN($asnResult)
    {
        /*
        preg_match("/(?P<data>\n(\#|\%)[^\#|\%]+{$asn}[^\n]+\n.*)/ism", $asnResult, $match);
        if (!empty($match['data'])) {
            $asnResult = trim($match['data']);
        }*/
        $asnResult = $this->cleanResultData($asnResult);
        return $asnResult;
    }

    /**
     * @param string $asn
     * @param bool $clean
     *
     * @return array|Collection
     */
    public function getASNData($asn, $clean = false)
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
        $result = $this->getFromServer($asn, $whoIsServer);
        if (!$clean) {
            return new Collection([
                $whoIsServer => $result
            ]);
        }

        return new Collection([$whoIsServer => $this->cleanASN($result)]);
    }

    /**
     * @param string $asn
     * @param bool $clean
     *
     * @return Collection|Collection[]
     */
    public function getASNWithArrayDetail($asn, $clean = false)
    {
        $result = $this->getASNData($asn, $clean);
        $key = key($result);
        return new Collection([$key => $this->parseForNicData($result[$key])]);
    }

    /**
     * @param string $data
     * @param bool   $clean
     * @return Collection|Collection[]
     */
    public function getIpData($data, $clean = false)
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
        $result = $this->getFromServer($ipData, $whoIsServer);

        if (!$clean) {
            return new Collection([$whoIsServer => $result]);
        }

        return new Collection([$whoIsServer => $this->cleanASN($result)]);
    }

    /**
     * Get IP detail
     *
     * @param string $address
     * @param bool $clean
     * @return Collection|Collection[]
     */
    public function getIPWithArrayDetail($address, $clean = false)
    {
        $result = $this->getIpData($address, $clean);
        $key = key($result);
        return new Collection([
            $key => $this->parseForNicData($result[$key])
        ]);
    }

    /**
     * Helper Parser
     * @internal
     * @param string $dataResult
     * @return Collection
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

        return new Collection($data2);
    }
}
