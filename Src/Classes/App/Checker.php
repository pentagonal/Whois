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

use Pentagonal\WhoIs\Exceptions\EmptyDomainException;
use Pentagonal\WhoIs\Exceptions\HttpException;
use Pentagonal\WhoIs\Exceptions\InvalidDomainException;
use Pentagonal\WhoIs\Exceptions\RequestLimitException;
use Pentagonal\WhoIs\Exceptions\ResultException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Exceptions\WhoIsServerNotFoundException;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\Interfaces\RecordDomainNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordNetworkInterface;
use Pentagonal\WhoIs\Traits\ResultNormalizer;
use Pentagonal\WhoIs\Util\DataParser;
use Pentagonal\WhoIs\Util\Sanitizer;

/**
 * Class Checker
 * @package Pentagonal\WhoIs\App
 *
 * Main Application that contains main features of WhoIs Data Result Getter
 */
class Checker
{
    use ResultNormalizer;

    /**
     * Version
     */
    const VERSION = '2.0.0';

    /**
     * Fake Comment that Domain is Registered from
     */
    const COMMENT_FAKE_RECORD = <<<FAKE
% NOTE: The registry of domain does not provide or publish ownership information
%       of domain for public usage. This data is represent for public domain data,
%       data provide from Domain Name Server (DNS) existences.

FAKE;

    /**
     * cache Prefix
     */
    const CACHE_PREFIX = 'pentagonal_wc_';

    /**
     * whois result class to use a result
     */
    const WHOIS_RESULT_CLASS = WhoIsResult::class;

    /**
     * max retry if timeout
     */
    const MAX_RETRY = 5;

    /**
     * @var int
     */
    protected $cacheExpired = 3600;

    /**
     * Instance of validator
     *
     * @var Validator
     */
    protected $validatorInstance;

    /**
     * Cache instance object
     *
     * @var CacheInterface
     */
    protected $cacheInstance;

    /**
     * @var int minimum length when domain name always registered,
     *          maximum set is on between 0 to 3 to use it
     *          and set to non numeric , null or les than 0 or more than
     *          3 (4 or more) to disable it, default is 1
     */
    protected $minLength = 1;

    /**
     * Declare base on domain name
     * that must be always registered
     * maybe this is branded domain that use trademark use on TLD
     * Domain. or has 1 stld
     * This is fast check for domain availability checker
     * That means no check from whois server
     * but on less than 3 characters it means domain always registered!
     *
     * @var array
     */
    protected $reservedNameTLDDomain = [
        // brand domain that easy to understand and always registered
        'google', 'youtube', 'blogspot', 'android', 'blogger',
        'example', 'icann', 'iana', 'pir', 'ripe', 'arin',
        'amazon', 'ebay', 'adobe', 'hp',
        'fb', 'facebook', 'twitter', 'instagram', 'path',
        'wikipedia', 'wordpress', 'wp', 'time', 'php',
        'office', 'microsoft', 'windows', 'apple', 'mac',
        'disney', 'marvel', 'dc',

        // common registered domain for network
        'dot', 'nic', 'whois', 'web', 'dig', 'blog', 'ping',
        'ip', 'linux', 'unix', 'com', 'net', 'co',
    ];

    /**
     * @var array
     */
    protected $disAllowMainDomainExtension = [
        'zw',
        'bd',
    ];

    /**
     * @var string|array
     */
    protected $proxy;

    /**
     * @var array
     */
    protected $defaultOptions = [
        // default force IPv4
        'force_ip_resolve' => 'v4'
    ];

    /**
     * Checker constructor.
     *
     * @param Validator      $validator Validator instance
     * @param CacheInterface $cache     Cache object
     * @param array          $options   array as options request
     */
    public function __construct(
        Validator $validator = null,
        CacheInterface $cache = null,
        array $options = []
    ) {
        /**
         * Set Options
         */
        $this->cacheExpired = isset($options['cacheExpired']) && is_numeric($options['cacheExpired'])
            && $options['cacheExpired'] >= 0
            ? abs($options['cacheExpired'])
            : $this->cacheExpired;
        unset($options['cacheExpired']);

        $this->defaultOptions = $options;
        $this->validatorInstance = $validator?: Validator::createInstance();
        $this->cacheInstance = $cache?: ArrayCacheCollector::createInstance();
    }

    /**
     * Create new instance checker
     *
     * @param Validator|null $validator
     * @param CacheInterface|null $cache
     * @param array $options
     *
     * @return Checker
     */
    public static function createInstance(
        Validator $validator = null,
        CacheInterface $cache = null,
        array $options = []
    ) : Checker {
        return new static(
            $validator,
            $cache,
            $options
        );
    }

    /**
     * Create new instance checker with proxy
     *
     * @param string $proxy
     * @param Validator|null $validator
     * @param CacheInterface|null $cache
     * @param array $options
     *
     * @return Checker
     */
    public static function createProxy(
        $proxy,
        Validator $validator = null,
        CacheInterface $cache = null,
        array $options = []
    )  : Checker {
        if (!is_string($proxy) && is_array($proxy)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Proxy must be as a string (host:port) or array [host => hostname, port => int] %s given',
                    gettype($proxy)
                )
            );
        }
        $object = new static($validator, $cache, $options);
        return $object->withProxy($proxy);
    }

    /**
     * Get Default Options Request
     *
     * @return array
     */
    public function getDefaultOptions() : array
    {
        return $this->defaultOptions;
    }

    /**
     * Use Proxy
     *
     * @param string|array $proxy
     * @return  Checker
     */
    public function withProxy($proxy) : Checker
    {
        if (!is_string($proxy) && is_array($proxy)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Proxy must be as a string (host:port) or array [host => hostname, port => int] %s given',
                    gettype($proxy)
                )
            );
        }

        $clone = clone $this;
        $clone->proxy = $proxy;
        return $clone;
    }

    /**
     * @return Checker
     */
    public function withoutProxy() : Checker
    {
        $clone = clone $this;
        $clone->proxy = null;
        unset($clone->defaultOptions['proxy']);
        return $clone;
    }

    /**
     * Get Proxy Host
     *
     * @return string|array
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Get instance of validator
     *
     * @return Validator
     */
    public function getValidator() : Validator
    {
        return $this->validatorInstance;
    }

    /* --------------------------------------------------------------------------------*
     |                                  UTILITY                                        |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Normalize cache key to put on Cache storage
     *
     * @param string $key
     *
     * @return string
     */
    protected function normalizeCacheKey($key)
    {
        if (!is_string($key) && !is_numeric($key) && ! is_bool($key) && ! is_null($key)) {
            $key = Sanitizer::maybeSerialize($key);
        }

        return static::CACHE_PREFIX . sha1($key);
    }

    /**
     * Get cache
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    protected function putCache(string $key, $value)
    {
        if (!$this->cacheInstance instanceof ArrayCacheCollector) {
            $value = Sanitizer::maybeSerialize($value);
        }
        return $this->cacheInstance->put(
            $this->normalizeCacheKey($key),
            $value,
            $this->cacheExpired
        );
    }

    /**
     * Get cache data
     *
     * @param string $cache
     *
     * @return mixed|null
     */
    protected function getCache(string $cache)
    {
        $cacheData = $this->cacheInstance->get(
            $this->normalizeCacheKey($cache),
            null
        );

        if ($cacheData !== null && is_string($cacheData)) {
            $cacheData = Sanitizer::maybeUnSerialize($cacheData);
        }

        return $cacheData;
    }
    /**
     * Create Request
     *
     * @param string $target
     * @param string $server
     * @param array $options
     *
     * @return WhoIsRequest
     */
    protected function prepareForRequest(string $target, string $server, array $options = []) : WhoIsRequest
    {
        return new WhoIsRequest($target, $server, $options);
    }

    /**
     * Get From Server
     *
     * @param string $target
     * @param string $server
     * @param array $options
     *
     * @return WhoIsRequest
     */
    public function getRequest(string $target, string $server, array $options = []) : WhoIsRequest
    {
        return $this->normalizeAfterRequestSend(
            $this->getRequestPending($target, $server, $options),
            $this->getValidator()
        );
    }

    /**
     * Get From Request async not yet send
     *
     * @param string $target
     * @param string $server
     * @param array $options
     *
     * @return WhoIsRequest
     */
    public function getRequestPending(string $target, string $server, array $options = []) : WhoIsRequest
    {
        if (!isset($options['proxy'])) {
            $Proxy = $this->getProxy();
            if ($Proxy) {
                $options['proxy'] = $Proxy;
            }
        }

        $options = array_merge($this->getDefaultOptions(), $options);
        return $this->prepareForRequest($target, $server, $options);
    }

    /* --------------------------------------------------------------------------------*
     |                               SERVER GETTER                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Get Whois Servers
     *
     * @param string $selector       the domain Name
     * @param bool $requestFromIAna  try to get whois server from IANA
     *
     * @return array
     * @throws \Throwable
     */
    public function getWhoIsServerFor(string $selector, bool $requestFromIAna = false) : array
    {
        if (trim($selector) === '') {
            throw new \InvalidArgumentException(
                'Argument selector could not be empty',
                E_USER_WARNING
            );
        }

        $validator = $this->getValidator();
        if ($validator->isValidASN($selector)) {
            // ASN
            $servers = $validator->splitASN($selector)->getWhoIsServers();
        } elseif ($validator->isValidIP($selector)) {
            // IP
            $servers = $validator->splitIP($selector)->getWhoIsServers();
        } elseif ($validator->isValidDomain($selector)) {
            // Domain
            $servers = $validator->splitDomainName($selector)->getWhoIsServers();
        } else {
            throw new \RuntimeException(
                sprintf(
                    'Selector %1$s is not valid IP, ASN or Domain',
                    $selector
                ),
                E_WARNING
            );
        }
        if (!empty($servers)) {
            return $servers;
        }

        if (empty($servers)) {
            $domainKey = strtolower($selector);
            $keyWhoIs = "{$domainKey}_whs";
            $whoIsServer = $this->getCache($keyWhoIs);
            if ($requestFromIAna && (empty($whoIsServer) || !is_array($whoIsServer))) {
                $iAnaRequest = $this->getRequest($selector, DataParser::URI_IANA_WHOIS);
                if ($iAnaRequest->isTimeOut()) {
                    $iAnaRequest = $this->getRequest($selector, DataParser::URI_IANA_WHOIS);
                }
                if ($iAnaRequest->isError()) {
                    throw $iAnaRequest->getResponse();
                }

                $whoIsServer = DataParser::getWhoIsServerFromResultData($iAnaRequest->getBodyString());
                if ($whoIsServer) {
                    $servers = [$whoIsServer];
                    $this->putCache($keyWhoIs, $servers);
                    return $servers;
                }
            }
        }

        throw new WhoIsServerNotFoundException(
            sprintf(
                'Could not get whois server for: %s',
                $selector
            ),
            E_NOTICE
        );
    }

    /**
     * Create fake result for empty domain server
     *
     * @param string $domainName
     *
     * @return string
     * @throws \Throwable
     */
    protected function createFakeResultFromEmptyServerDomain(string $domainName) : string
    {
        $record = trim(static::COMMENT_FAKE_RECORD);
        $record .= "\n\nDomain Name: {$domainName}\n";
        try {
            $err = null;
            // handle error
            set_error_handler(function ($code, $message, $file, $line) use (&$err) {
                $err = new HttpException(
                    $message,
                    $code
                );

                $err->setFile($file);
                $err->setLine($line);
            });
            // get dns
            $dns = (array)dns_get_record($domainName, DNS_NS);

            restore_error_handler();
            if ($err instanceof HttpException) {
                throw $err;
            }
        } catch (\Throwable $e) {
            throw $e;
        }

        if (!empty($dns)) {
            $record .= "Domain Status: Taken\n\n";
            foreach ($dns as $array) {
                if (!empty($array['target'])) {
                    $record .= "Name Server: {$array['target']}\n";
                }
            }
        } else {
            $record .= "Domain Status: Available\n";
        }

        try {
            $string = $this
                ->getFromDomain($domainName, DataParser::URI_IANA_WHOIS)
                ->getOriginalResultString();
            preg_match(
                '~
                    Registra(?:tion|ant|ar)s?\s+information(?:[^\:]+)?\:(?:\s*remarks:[\s]*)?
                    ((?>https?\:\/\/)[^\n\r]+)
                ~xiu',
                $string,
                $match
            );
            if (!empty($match[1])) {
                $match[1] = trim($match[1]);
                $record .= "\n% For more information please visit : {$match[1]}";
            }
        } catch (\Throwable $e) {
            //
        }

        return $record;
    }

    /**
     * Prepare fallback for result
     *
     * @param RecordNetworkInterface $network
     * @param WhoIsRequest $request
     *
     * @return WhoIsResult
     * @throws \Throwable
     */
    protected function prepareForWhoIsResult(
        RecordNetworkInterface $network,
        WhoIsRequest $request
    ) : WhoIsResult {

        $whoIsResultClass = static::WHOIS_RESULT_CLASS;
        $keyCache = md5($whoIsResultClass)."_{$request->getTargetName()}_{$request->getServer()}";
        $result = $this->getCache($keyCache);
        if ($result instanceof WhoIsResult) {
            $result->useCache = true;
            return $result;
        }

        if ($request->isError()) {
            throw $request->getResponse();
        }

        $body = $request->getBodyString();
        if (!$body) {
            throw new ResultException(
                'Result data is empty',
                E_NOTICE
            );
        }

        /**
         * @var WhoIsResult $result
         */
        $result =  new $whoIsResultClass(
            $network,
            $body,
            $request->getServer()
        );

        if ($request->getProxyConnection()) {
            /** @noinspection PhpUndefinedFieldInspection */
            $result->proxyConnection = $request->getProxyConnection();
        }

        if ($result->isLimited()) {
            $limit = new RequestLimitException(
                sprintf(
                    'Request for %1$s on %2$s has limit exceeded',
                    $result->getPointer(),
                    $request->getServer()
                ),
                E_WARNING,
                $result
            );
            throw $limit;
        }

        $this->putCache($keyCache, $result);
        return $result;
    }

    /* --------------------------------------------------------------------------------*
     |                              DOMAIN CHECKER                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Try Request Result
     *
     * @param string $target string domain name, IP or ASN
     * @param string $server
     * @param int $retry
     *
     * @return WhoIsRequest
     * @throws \Throwable
     */
    protected function tryRequestResult(
        string $target,
        string $server,
        int $retry = 2
    ) : WhoIsRequest {

        if (trim($target) === '') {
            throw new \InvalidArgumentException(
                'Argument selector could not be empty',
                E_USER_WARNING
            );
        }

        if (trim($target) === '') {
            throw new \InvalidArgumentException(
                'Target could not be empty',
                E_USER_WARNING
            );
        }

        if (trim($server) === '') {
            throw new \InvalidArgumentException(
                'Server could not be empty',
                E_USER_WARNING
            );
        }

        if ($retry > static::MAX_RETRY) {
            $retry = static::MAX_RETRY;
        }

        $request = $this->getRequest($target, $server);
        while ($request->isTimeOut() && $retry > 0) {
            $retry -= 1;
            $request = $this->getRequest($target, $server);
        }

        if ($request->isError()) {
            throw $request->getResponse();
        }

        return $request;
    }

    /**
     * @param string $domainName
     *
     * @return RecordDomainNetworkInterface
     * @throws \RuntimeException
     * @throws InvalidDomainException
     */
    protected function getRecordValidateFromDomain(string $domainName) : RecordDomainNetworkInterface
    {
        $domainName = trim($domainName);
        if ($domainName === '') {
            throw new EmptyDomainException(
                'Domain name could not be empty or white space only'
            );
        }

        $record = $this->getValidator()->splitDomainName($domainName);
        if (! $record->isTopLevelDomain()) {
            throw new InvalidDomainException(
                sprintf(
                    'Domain name %s is not a valid top domain',
                    $record->getFullDomainName()
                ),
                E_NOTICE
            );
        }

        if ($record->isGTLD()
            && in_array($record->getBaseExtension(), $this->disAllowMainDomainExtension)
        ) {
            throw new InvalidDomainException(
                sprintf(
                    'Domain name Extension: (.%s) GTLD is not for public registration',
                    $record->getBaseExtension()
                ),
                E_NOTICE
            );
        }
        if ($record->isSTLD() && $record->isMaybeDisAllowToRegistered()) {
            throw new InvalidDomainException(
                sprintf(
                    'Domain name : %s has invalid as top level domain as STLD',
                    $domainName
                ),
                E_NOTICE
            );
        }

        return $record;
    }

    /**
     * Get From Domain Name
     *
     * @param string $domainName The domain Name to check
     * @param string|null $server
     *
     * @return WhoIsResult
     * @throws \Throwable
     */
    public function getFromDomain(string $domainName, string $server = null) : WhoIsResult
    {
        $record = $this->getRecordValidateFromDomain($domainName);
        $servers = $server && trim($server)
            ? [$server]
            : (
                $record->getWhoIsServers()?:
                // request from iana
                $this->getWhoIsServerFor($record->getPointer(), true)
            );
        $isLimit = false;
        $usedServer = null;
        foreach ($servers as $server) {
            try {
                $request = $this->tryRequestResult(
                    $record->getPointer(),
                    $server,
                    2
                );
                break;
            } catch (TimeOutException $e) {
                continue;
            } catch (RequestLimitException $e) {
                $isLimit = $e;
            } catch (\Throwable $e) {
                throw $e;
            }
            unset($request);
        }

        if (!isset($request)) {
            if ($isLimit) {
                throw $isLimit;
            }

            throw new ResultException(
                sprintf(
                    'Could not get data for : %s',
                    $domainName
                )
            );
        }

        return $this->prepareForWhoIsResult(
            $record,
            $request
        );
    }

    /**
     * Check if Domain is registered
     *
     * @param string $domainName the domain name to check
     *
     * @return bool|null  boolean true if registered otherwise false,
     *                    or null if can not check domain
     */
    public function isDomainRegistered(string $domainName)
    {
        $record = $this->getValidator()->splitDomainName($domainName);
        if (! $record->isTopLevelDomain()) {
            throw new InvalidDomainException(
                sprintf(
                    'Domain name %s is not a valid top domain',
                    $record->getFullDomainName()
                ),
                E_NOTICE
            );
        }

        if (in_array($record->getMainDomainName(), $this->reservedNameTLDDomain)) {
            return true;
        }

        // check length
        if (is_numeric($this->minLength)
            && $this->minLength > 0
            && $this->minLength < 4
            && strlen($record->getMainDomainName()) <= $this->minLength
        ) {
            return true;
        }

        // if has not whois servers
        if (!$record->getWhoIsServers()) {
            $keyCache = $record->getDomainName() .'_dns_availability';
            if (($result = $this->getCache($keyCache)) instanceof \stdClass
                && !empty($result->registered)
                && is_array($result->registered)
            ) {
                return !empty($result->registered);
            }

            $dns = dns_get_record($record->getDomainName(), DNS_NS);
            $result = new \stdClass();
            $result->registered = $dns;
            $this->putCache($keyCache, $result);
            return ! empty($result->registered);
        }

        $status = $this
            ->getFromDomain($record->getFullDomainName())
            ->getRegisteredStatus();

        return
            $status === DataParser::STATUS_REGISTERED
            || $status === DataParser::STATUS_RESERVED
            ?: (
                $status === DataParser::STATUS_UNREGISTERED ? false: null
            );
    }

    /* --------------------------------------------------------------------------------*
     |                                 IP CHECKER                                      |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Get IP Detail
     *
     * @param string $ip
     * @param string $server
     *
     * @return WhoIsResult
     * @throws \Throwable
     */
    public function getFromIP(string $ip, string $server = null) : WhoIsResult
    {
        $ipDetail =  $this->getValidator()->splitIP($ip);
        $server   = $server && trim($server)
            ? $server
            : $ipDetail->getWhoIsServers()[0];
        $request = $this->tryRequestResult(
            $ipDetail->getPointer(),
            $server,
            2
        );

        // if there was and error throw it
        if ($request->isError()) {
            throw $request->getResponse();
        }
        return $this->prepareForWhoIsResult(
            $ipDetail,
            $request
        );
    }

    /* --------------------------------------------------------------------------------*
     |                                 ASN CHECKER                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Get ASN Result Detail
     *
     * @param string $asn
     * @param string $server
     *
     * @return WhoIsResult
     * @throws \Throwable
     */
    public function getFromASN(string $asn, string $server = null) : WhoIsResult
    {
        $asnDetail = $this->getValidator()->splitASN($asn);
        $server    = $server && trim($server)
            ? $server
            : $asnDetail->getWhoIsServers()[0];
        $request = $this->tryRequestResult(
            $asnDetail->getPointer(),
            $server,
            2
        );

        // if there was and error throw it
        if ($request->isError()) {
            throw $request->getResponse();
        }

        return $this->prepareForWhoIsResult(
            $asnDetail,
            $request
        );
    }

    public function getFromHandler(string $id, string $server = null)
    {
        $handler = $this->getValidator()->splitHandler($id);
        $server    = $server && trim($server)
            ? $server
            : $handler->getWhoIsServers()[0];
        $request = $this->tryRequestResult(
            $handler->getPointer(),
            $server,
            2
        );
        // if there was and error throw it
        if ($request->isError()) {
            throw $request->getResponse();
        }

        return $this->prepareForWhoIsResult(
            $handler,
            $request
        );
    }

    /* --------------------------------------------------------------------------------*
     |                           AUTOMATION CHECKER                                    |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Get Multi Result if possible follow given whois server from result
     *
     * @param string $selector selector target IP, Domain or AS Number
     *
     * @return WhoIsMultiResult
     */
    public function getFollowServerResult(string $selector) : WhoIsMultiResult
    {
        $result = $this->getResult($selector);
        $server = $result->getServer();
        $multiResult = new WhoIsMultiResult([
            $server => $result
        ]);

        $alternatedServer = DataParser::getWhoIsServerFromResultData($result->getOriginalResultString());
        $alternatedServer = is_string($alternatedServer)
            ? strtolower(trim($alternatedServer))
            : null;
        $alternatedServer = $alternatedServer !== DataParser::URI_IANA_WHOIS
            ? $alternatedServer
            : null;

        if ($alternatedServer
            && is_string($server)
            && trim($server) != ''
            && $alternatedServer !== strtolower(trim($server))
        ) {
            // try to get Other Result
            try {
                $result = $this->getResult($selector, $alternatedServer);
                $multiResult[$alternatedServer] = $result;
            } catch (\Throwable $e) {
                // pass
            }
        }

        return $multiResult;
    }

    /**
     * Get Result automation
     *
     * @param string $selector    selector target IP, Domain or AS Number
     * @param string|null $server target whois server
     *
     * @return WhoIsResult
     */
    public function getResult(string $selector, string $server = null) : WhoIsResult
    {
        if (trim($selector) === '') {
            throw new \InvalidArgumentException(
                'Argument selector could not be empty',
                E_USER_WARNING
            );
        }

        $validator = $this->getValidator();
        // ASN
        if ($validator->isValidASN($selector)) {
            return $this->getFromASN($selector, $server);
        }

        // IP
        if ($validator->isValidIP($selector)) {
             return $this->getFromIP($selector, $server);
        }

        // Domain
        if ($validator->isValidDomain($selector)) {
            return $this->getFromDomain($selector, $server);
        }

        if ($validator->isValidHandler($selector)) {
            return $this->getFromHandler($selector);
        }

        throw new \RuntimeException(
            sprintf(
                'Selector %1$s is not valid IP, ASN, Domain or Handler',
                $selector
            ),
            E_WARNING
        );
    }

    /**
     * Get for record type
     *
     * @param string $domain
     *
     * @return RecordNetworkInterface
     * @throws \Throwable
     */
    public function getForType(string $domain) : RecordNetworkInterface
    {
        $validator = $this->getValidator();
        // ASN
        if ($validator->isValidASN($domain)) {
            $record = $validator->splitASN($domain);
        }
        // IP
        if ($validator->isValidIP($domain)) {
            $record = $validator->splitIP($domain);
        }
        // Domain
        if ($validator->isValidDomain($domain)) {
            $record = $this->getRecordValidateFromDomain($domain);
        }
        if ($validator->isValidHandler($domain)) {
            $record = $validator->splitHandler($domain);
        }
        if (empty($record)) {
            throw new \RuntimeException(
                sprintf(
                    'Selector %1$s is not valid IP, ASN, Domain or Handler',
                    $domain
                ),
                E_WARNING
            );
        }
        return $record;
    }

    /**
     * Get Multiple Domain, using async request result
     *
     * @param array $targets domain|network list as
     *          ['domain.name', 'domain.ext']
     *          or ['domain.ext' => 'whois.server.ext']
     *          or ['domain.ext' =>  null|bool|0 ... empty value]
     *          or mixed [
     *                  'domain.ext',
     *                  'ASN124' => 'whois.arin.net',
     *                  'domain.ext1' => 'whois.server.ext',
     *                  'domain.ext2' =>  null|bool|0 ... empty value
     *                  ]
     *
     * Note: this request will be not to try / check limit / timeout
     *
     * @return WhoIsMultiResult|WhoIsResult[]|\Throwable[]
     */
    public function getFromMultiple(array $targets) : WhoIsMultiResult
    {
        $targetLists = [];
        $netWorkRecords = [];
        foreach ($targets as $target => $server) {
            $key = $target;
            if (!is_string($target)) {
                if (! is_string($server)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Key for array must be as a string of target name, %s given',
                            gettype($server)
                        )
                    );
                }
                $target = $server;
                $server = null;
            } else {
                if (!$server || is_bool($server)) {
                    $server = null;
                } elseif ($server && $server !== null) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Server must be as a string, %s given',
                            gettype($server)
                        )
                    );
                }
            }

            if (trim($target) === '') {
                throw new \InvalidArgumentException(
                    'Invalid arguments for target lists'
                );
            }

            try {
                $record = $this->getForType($target);
                if (!$server) {
                    $servers = $record->getWhoIsServers();
                    if (empty($servers)) {
                        throw new WhoIsServerNotFoundException(
                            sprintf(
                                'Could not get whois server for: %s',
                                $target
                            ),
                            E_NOTICE
                        );
                    }
                    /**
                     * @var string $server
                     */
                    $server = reset($servers);
                }
                $netWorkRecords[$key] = $record;
                $targetLists[$key] = $this->getRequestPending($target, $server);
            } catch (\Throwable $e) {
                $targetLists[$key] = $e;
            }
        }

        $whoIsRequests = [];
        foreach ($targetLists as $key => $whoIsRequest) {
            if ($whoIsRequest instanceof WhoIsRequest) {
                $whoIsRequests[$key] = $whoIsRequest;
            }
        }

        $whoIsRequests = new WhoIsMultiRequest($whoIsRequests);
        foreach ($whoIsRequests->getSendRequests() as $targetName => $request) {
            try {
                $targetLists[$targetName] = $this->prepareForWhoIsResult(
                    $netWorkRecords[$targetName],
                    $request
                );
            } catch (\Throwable $e) {
                $targetLists[$targetName] = $e;
            }
        }

        unset($whoIsRequest, $netWorkRecords, $targets);
        return new WhoIsMultiResult($targetLists);
    }

    /**
     * Get Multi Result if possible follow given whois server from result
     *
     * @param array $targets selector target IP, Domain or AS Number
     *
     * @return WhoIsMultiResult
     */
    public function getFromMultipleFollowResultServer(array $targets) : WhoIsMultiResult
    {
        /**
         * @var WhoIsMultiResult[] $multiResult
         */
        $multiResult = $this->getFromMultiple($targets);
        $serversToFollow = [];
        $networkRecords = [];
        foreach ($multiResult as $key => $result) {
            if (!$result instanceof WhoIsResult) {
                $multiResult[$key] = new WhoIsMultiResult([
                    $result
                ]);
                continue;
            }

            $server = $result->getServer();
            $multiResult[$key] = new WhoIsMultiResult([
                $server => $result
            ]);
            $alternatedServer = DataParser::getWhoIsServerFromResultData($result->getOriginalResultString());
            if (is_string($alternatedServer)
                && is_string($server)
                && trim($server) != ''
                && strtolower(trim($alternatedServer)) !== strtolower(trim($server))
            ) {
                try {
                    $request = $this->getRequestPending($result->getPointer(), $alternatedServer);
                    $serversToFollow[$key] = $request;
                    $networkRecords[$key] = $result->getNetworkRecord();
                } catch (\Throwable $e) {
                    // pas
                }
            }
        }

        if (empty($serversToFollow)) {
            return $multiResult;
        }

        try {
            $serversToFollow = new WhoIsMultiRequest($serversToFollow);
            foreach ($serversToFollow->getSendRequests() as $targetName => $request) {
                if (!isset($multiResult[$targetName]) || !isset($networkRecords[$targetName])) {
                    continue;
                }
                try {
                    $result = $this->prepareForWhoIsResult(
                        $networkRecords[$targetName],
                        $request
                    );
                    $multiResult[$targetName]->set($result->getServer(), $result);
                } catch (\Throwable $e) {
                    // pass
                }
            }
        } catch (\Throwable $e) {
            // pass
        }

        unset($serversToFollow, $networkRecords);
        return $multiResult;
    }
}
