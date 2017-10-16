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
use Pentagonal\WhoIs\Exceptions\InvalidDomainException;
use Pentagonal\WhoIs\Exceptions\RequestLimitException;
use Pentagonal\WhoIs\Exceptions\ResultException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Exceptions\WhoIsServerNotFoundException;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\Util\DataParser;
use Pentagonal\WhoIs\Util\Sanitizer;

/**
 * Class Checker
 * @package Pentagonal\WhoIs\App
 */
class Checker
{
    /**
     * Version
     */
    const VERSION = '2.0.0';

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
     *          3 (4 or more) to disable it
     */
    protected $minLength = 2;

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
     * Checker constructor.
     *
     * @param Validator      $validator Validator instance
     * @param CacheInterface $cache     Cache object
     */
    public function __construct(Validator $validator, CacheInterface $cache = null)
    {
        $this->validatorInstance = $validator;
        $this->cacheInstance = $cache?: new ArrayCacheCollector();
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

    /**
     * Normalize cache key
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
        return $this->cacheInstance->put(
            $this->normalizeCacheKey($key),
            Sanitizer::maybeSerialize($value)
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
     * Get From Server
     *
     * @param string $domain
     * @param string $server
     * @param array $options
     *
     * @return WhoIsRequest
     */
    public function getRequest(string $domain, string $server, array $options = []) : WhoIsRequest
    {
        $request = new WhoIsRequest($domain, $server, $options);
        return $request->send();
    }

    /**
     * @param string $domainName
     *
     * @return array
     * @throws \Throwable
     */
    public function getWhoIsServerFor(string $domainName) : array
    {
        $domainName = trim($domainName);
        if ($domainName === '') {
            throw new EmptyDomainException(
                'Domain name could not be empty or white space only'
            );
        }

        $validator  = $this->getValidator();
        // if invalid thrown error
        $domainRecord = $validator->splitDomainName($domainName);
        $extension  = $domainRecord->getBaseExtension();
        $servers = $validator->getTldCollector()->getServersFromExtension($extension);
        if (empty($servers)) {
            $domainKey = strtolower($domainName);
            $keyWhoIs = "{$domainKey}_whs";
            $whoIsServer = $this->getCache($keyWhoIs);
            if (empty($whoIsServer) || !is_array($whoIsServer)) {
                $iAnaRequest = $this->getRequest($domainName, DataParser::URI_IANA_WHOIS);
                if ($iAnaRequest->isTimeOut()) {
                    $iAnaRequest = $this->getRequest($domainName, DataParser::URI_IANA_WHOIS);
                }
                if ($iAnaRequest->isError()) {
                    throw $iAnaRequest->getResponse();
                }

                $whoIsServer = DataParser::getWhoIsServerFromResultData($iAnaRequest->getBodyString());
                if ($whoIsServer) {
                    $servers = [$whoIsServer];
                    $this->putCache($keyWhoIs, $servers);
                } else {
                    throw new WhoIsServerNotFoundException(
                        sprintf(
                            'Could not get whois server for: %s',
                            $domainName
                        ),
                        E_NOTICE
                    );
                }
            }
        }

        return (array) $servers;
    }

    /**
     * Get From Domain With Server
     *
     * @param string $domainName
     * @param string $server
     * @param int $retry
     *
     * @return WhoIsResult
     * @throws \Throwable
     */
    public function getFromDomainWithServer(
        string $domainName,
        string $server,
        int $retry = 2
    ) : WhoIsResult {
        if ($retry > static::MAX_RETRY) {
            $retry = static::MAX_RETRY;
        }

        $domainName = trim($domainName);
        if ($domainName === '') {
            throw new EmptyDomainException(
                'Domain name could not be empty or white space only'
            );
        }

        $keyCache = "{$domainName}_{$server}";
        $result = $this->getCache($keyCache);
        if ($result instanceof WhoIsResult) {
            return $result;
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

        $request = $this->getRequest(
            $record->getDomainName(),
            $server
        );

        while ($request->isTimeOut() && $retry > 0) {
            $retry -= 1;
            $request = $this->getRequest($domainName, $server);
        }

        if ($request->isError()) {
            throw $request->getResponse();
        }

        $whoIsResultClass = static::WHOIS_RESULT_CLASS;
        $result = new $whoIsResultClass($record, $request->getBodyString(), $request->getServer());
        $this->putCache($keyCache, $result);
        return $result;
    }

    /**
     * Get From Domain
     *
     * @param string $domainName
     * @param bool $followServer follow server if whois server exists
     *
     * @return ArrayCollector|WhoIsResult[]
     * @throws \Throwable
     */
    public function getFromDomain(string $domainName, bool $followServer = false) : ArrayCollector
    {
        $servers = $this->getWhoIsServerFor($domainName);
        $isLimit = false;
        $usedServer = null;
        foreach ($servers as $server) {
            try {
                $usedServer = $server;
                $whoIsResult = $this->getFromDomainWithServer($domainName, $server);
                break;
            } catch (TimeOutException $e) {
                continue;
            } catch (RequestLimitException $e) {
                $isLimit = $e;
            } catch (\Throwable $e) {
                throw $e;
            }
            unset($whoIsResult);
        }

        if (!isset($whoIsResult)) {
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

        // result
        $result = new ArrayCollector([$usedServer => $whoIsResult]);
        if ($followServer) {
            $alternatedServer = $whoIsResult->getWhoIsServerFromResult();
            if ($alternatedServer
                && ! in_array(
                    strtolower($alternatedServer),
                    // whois.iana.org is not allowed here
                    [strtolower($usedServer), DataParser::URI_IANA_WHOIS]
                )
            ) {
                try {
                    $whoIsResult = $this->getFromDomainWithServer($domainName, $alternatedServer);
                    $result[$alternatedServer] = $whoIsResult;
                } catch (\Throwable $e) {
                    // pass
                }
            }
        }

        return $result;
    }

    /**
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

        $result = $this->getFromDomain($domainName);
        /**
         * @var WhoIsResult $result
         */
        $result = $result->last();
        $parser = $result->getDataParser();
        $status = $parser->hasRegisteredDomain($result->getOriginalResultString());
        return $status === $parser::REGISTERED
            || $status === $parser::RESERVED
            ? true
            : (
                $status === $parser::UNREGISTERED
                    ? false
                    : null
            );
    }
}
