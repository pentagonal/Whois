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

namespace Pentagonal\WhoIs\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Uri;
use Pentagonal\WhoIs\Exceptions\ConnectionException;
use Pentagonal\WhoIs\Exceptions\ConnectionFailException;
use Pentagonal\WhoIs\Exceptions\ConnectionRefuseException;
use Pentagonal\WhoIs\Exceptions\HttpBadAddressException;
use Pentagonal\WhoIs\Exceptions\HttpExpiredException;
use Pentagonal\WhoIs\Exceptions\HttpPermissionException;
use Pentagonal\WhoIs\Exceptions\ResourceException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Client Creator To fix Guzzle Client Socket Enable
 *
 * Class TransportSocketClient
 * @package Pentagonal\WhoIs\Handler
 */
class TransportSocketClient
{
    const DEFAULT_PORT = 43;

    /**
     * @var Client
     */
    protected $client;

    /**
     * TransportSocketClient constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get Client
     *
     * @return Client
     */
    public function getClient() : Client
    {
        return $this->client;
    }

    /**
     * Make a Request
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return mixed|ResponseInterface
     */
    public function request(string $method = 'GET', $uri = '', array $options = [])
    {
        if (!$uri instanceof UriInterface) {
            $uri = $this->createUri($uri);
        }
        if ($uri->getPort() === self::DEFAULT_PORT && $uri->getScheme() == '') {
            $options['stream'] = false;
            $options['curl'][CURLOPT_CUSTOMREQUEST] = $method;
        }

        return $this->getClient()->request($method, $uri, $options);
    }

    /**
     * @param $domainName
     * @param string $server
     *
     * @return ResponseInterface
     */
    public static function requestSocketConnection($domainName, string $server) : ResponseInterface
    {
        /**
         * Make Domain Name To Custom Request
         */
        $domainName = trim($domainName) ."\r\n";
        // create uri
        $args = func_get_args();
        array_shift($args);
        $uri = self::createUri($server);
        $request = new Request($domainName, $uri);
        $stream = @fsockopen($uri->getHost(), self::DEFAULT_PORT, $errCode, $errMessage);
        if (!$stream) {
            self::thrownExceptionResource($request, new ResourceException($errMessage, $errCode));
        }

        $stream = new Stream($stream);
        $stream->write($domainName);

        return new Response(200, [], $stream);
    }

    /**
     * Get Request Socket
     *
     * @param string $domainName
     * @param string $server
     * @param int    $port        determine port request
     *
     * @return mixed|ResponseInterface
     */
    public static function requestStreamConnection(string $domainName, string $server, int $port = null)
    {
        /**
         * Make Domain Name To Custom Request
         */
        $domainName = trim($domainName) ."\r\n";
        // create uri
        $args = func_get_args();
        array_shift($args);
        $clone = self::createForStreamSocket();
        return $clone
            ->request(
                $domainName,
                call_user_func_array([$clone, 'createUri'], $args)
            );
    }

    /**
     * @return HandlerStack
     */
    public static function createStackHandler() : HandlerStack
    {
        return HandlerStack::create(new CurlHandler());
    }

    /**
     * Create guzzle client for socket that returning response has no body
     *
     * @param array $options
     *
     * @return TransportSocketClient
     */
    public static function createForStreamSocket(array $options = []) : TransportSocketClient
    {
        $defaultOptions = [
            'handler' => static::createStackHandler(),
        ];

        $options =  array_merge($defaultOptions, $options);
        $transport = new static(new Client($options));
        return $transport;
    }

    /**
     * @param array $options
     *
     * @return TransportSocketClient
     */
    public static function createClient(array $options = []) : TransportSocketClient
    {
        // with no headers
        $defaultOptions = ['handler' => static::createStackHandler()];
        $options =  array_merge($defaultOptions, $options);
        return static::createForStreamSocket($options);
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent)
    {
        $configs = $this->client->getConfig();
        if (!isset($configs['headers']) || !is_array($configs['headers'])) {
            $configs['headers'] = [];
        }
        foreach ($configs['headers'] as $key => $v) {
            if (is_string($key) && strtolower($key) === 'user-agent') {
                unset($configs['headers'][$key]);
            }
        }

        $configs['headers']['User-Agent'] = $userAgent;
        $this->client = new Client($configs);
    }

    /**
     * Without User Agent
     */
    public function withoutUserAgent()
    {
        $configs = $this->client->getConfig();
        if (!isset($configs['headers']) || !is_array($configs['headers'])) {
            $configs['headers'] = [];
        }
        foreach ($configs['headers'] as $key => $v) {
            if (is_string($key) && strtolower($key) === 'user-agent') {
                unset($configs['headers'][$key]);
            }
        }

        $configs['headers']['User-Agent'] = null;
        $this->client = new Client($configs);
    }

    /**
     * Get create URI
     *
     * @param string $server
     * @param int    $port
     *
     * @return Uri
     */
    public static function createUri(string $server, int $port = null) : Uri
    {
        $uri = new Uri($server);
        if ($uri->getScheme() === null) {
            $uri = $uri->withScheme('');
        }

        if ($port !== null) {
            $uri = $uri->withPort($port);
        } elseif ($uri->getPort() === null && $uri->getScheme() == '') {
            $uri = $uri->withPort(static::DEFAULT_PORT);
        }

        if ($uri->getHost() == '' && $uri->getPath()) {
            $path = '';
            $host = $server;
            $parseUrlServer = parse_url($server);
            if (empty($parseUrlServer['host']) && !empty($parseUrlServer['path'])) {
                $parseUrlServer = parse_url('http://'.ltrim($server));
                $host = $parseUrlServer['host'];
                $path = !empty($parseUrlServer['path']) ? $parseUrlServer['path'] : '';
            }

            $uri = $uri->withHost($host)->withPath($path);
        }

        return $uri;
    }

    /**
     * Determine & Throws
     *
     * @param RequestInterface $request
     * @param \Throwable $e
     * @throws \Throwable
     */
    public static function thrownExceptionResource(RequestInterface $request, \Throwable $e)
    {
        switch ($e->getCode()) {
            case AF_INET:
                $e = new ConnectionFailException($e->getMessage(), $request, $e);
                break;
            case SOCKET_ETIMEDOUT:
                $e = new TimeOutException($e->getMessage(), $request, $e);
                break;
            case SOCKET_ETIME:
                $e = new HttpExpiredException($e->getMessage(), $request, $e);
                break;
            case SOCKET_ECONNREFUSED:
                $e = new ConnectionRefuseException($e->getMessage(), $request, $e);
                break;
            case SOCKET_EACCES:
                $e = new HttpPermissionException($e->getMessage(), $request, $e);
                break;
            case SOCKET_EFAULT:
                $e = new HttpBadAddressException($e->getMessage(), $request, $e);
                break;
            case SOCKET_EPROTONOSUPPORT:
            case SOCKET_EPROTO:
            case SOCKET_EPROTOTYPE:
                $e = new ConnectionException($e->getMessage(), $request, $e);
                break;
            // case SOCKET_EINVAL:
            // case SOCKET_EINTR:
        }
        throw $e;
    }
}
