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
use Pentagonal\WhoIs\Exceptions\RequestLimitException;
use Pentagonal\WhoIs\Exceptions\ResultException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Exceptions\WhoIsServerNotFoundException;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\Util\DataGenerator;
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
        $cacheData = $this->cacheInstance->get($cache, null);
        if (!is_string($cacheData)) {
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
        $extension  = $domainRecord->getExtension();
        $servers = $validator->getTldCollector()->getServersFromExtension($extension);
        if (empty($servers)) {
            $domainKey = strtolower($domainName);
            $keyWhoIs = "{$domainKey}_whs";
            $whoIsServer = $this->getCache($keyWhoIs);
            if (empty($whoIsServer) || !is_array($whoIsServer)) {
                $iAnaRequest = $this->getRequest($domainName, DataGenerator::URI_IANA_WHOIS);
                if ($iAnaRequest->isTimeOut()) {
                    $iAnaRequest = $this->getRequest($domainName, DataGenerator::URI_IANA_WHOIS);
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

        $request = $this->getRequest($domainName, $server);
        while ($request->isTimeOut() && $retry > 0) {
            $retry -= 1;
            $request = $this->getRequest($domainName, $server);
        }

        if ($request->isError()) {
            throw $request->getResponse();
        }

        $result = new WhoIsResult($domainName, $request->getBodyString());
        $this->putCache($keyCache, $result);
        return $result;
    }

    /**
     * Get From Domain
     *
     * @param string $domainName
     * @param bool $followGivenServer
     *
     * @return ArrayCollector
     * @throws \Throwable
     */
    public function getFromDomain(string $domainName, bool $followGivenServer = false) : ArrayCollector
    {
        $servers = $this->getWhoIsServerFor($domainName);
        $isLimit = false;
        $usedServer = null;
        foreach ($servers as $server) {
            try {
                $usedServer = $server;
                $request = $this->getFromDomainWithServer($domainName, $server);
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

        // result
        $result = new ArrayCollector([$usedServer => $request]);

        if ($followGivenServer) {
            $alternatedServer = DataParser::getWhoIsServerFromResultData($request->getResultString());
            if ($alternatedServer && strtolower($alternatedServer) !== strtolower($usedServer)) {
                try {
                    $request = $this->getFromDomainWithServer($domainName, $alternatedServer);
                    $result[$alternatedServer] = $request;
                } catch (\Throwable $e) {
                    // pass
                }
            }
        }

        return $result;
    }
}
