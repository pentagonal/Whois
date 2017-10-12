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

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use Pentagonal\WhoIs\Exceptions\ResourceException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Handler\TransportClient;
use Pentagonal\WhoIs\Interfaces\CacheInterface;

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
}
