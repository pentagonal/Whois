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

use Guzzle\Http\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
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
use Pentagonal\WhoIs\Util\DataGenerator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Client Creator To fix Guzzle Client Socket Enable
 *
 * Class TransportClient
 * @package Pentagonal\WhoIs\Handler
 *
 * @method static ResponseInterface get($uri, array $options = [])
 * @method static ResponseInterface post($uri, array $options = [])
 * @method static ResponseInterface put($uri, array $options = [])
 * @method static ResponseInterface delete($uri, array $options = [])
 * @method static ResponseInterface trace($uri, array $options = [])
 * @method static ResponseInterface view($uri, array $options = [])
 * @method static ResponseInterface patch($uri, array $options = [])
 * @method static ResponseInterface head($uri, array $options = [])
 */
class TransportClient
{
    const DEFAULT_PORT = DataGenerator::PORT_WHOIS;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * TransportClient constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->defaultOptions = [
            'handler' => $this->createStackHandler(),
            'ssl'    => [
                'certificate_authority' => DataGenerator::PATH_CACERT,
            ]
        ];
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
     * @throws \Throwable
     * @throws ConnectException
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
        try {
            return $this->getClient()->request($method, $uri, $options);
        } catch (ConnectException $e) {
            /**
             * @var ResponseInterface $response
             * @var RequestInterface $request
             */
            $request = $e->getRequest();
            $response = $e->getResponse();
            if (!$response instanceof ResponseInterface
                || !$request instanceof RequestInterface
            ) {
                throw $e;
            }
            throw self::thrownExceptionResource($request, $e, $response, false);
        }
    }

    /**
     * @param string $userAgent
     *
     * @return TransportClient
     */
    public function withUserAgent(string $userAgent) : TransportClient
    {
        $object = clone $this;
        $configs = $object->client->getConfig();
        if (!isset($configs['headers']) || !is_array($configs['headers'])) {
            $configs['headers'] = [];
        }

        foreach ($configs['headers'] as $key => $v) {
            if (is_string($key) && strtolower($key) === 'user-agent') {
                unset($configs['headers'][$key]);
            }
        }

        $configs['headers']['User-Agent'] = $userAgent;
        $object->client = new Client($configs);
        return $object;
    }


    public function withoutUserAgent() : TransportClient
    {
        $object = clone $this;
        $configs = $object->client->getConfig();
        if (!isset($configs['headers']) || !is_array($configs['headers'])) {
            $configs['headers'] = [];
        }
        foreach ($configs['headers'] as $key => $v) {
            if (is_string($key) && strtolower($key) === 'user-agent') {
                unset($configs['headers'][$key]);
            }
        }

        $configs['headers']['User-Agent'] = null;
        $object->client = new Client($configs);
        return $object;
    }

    /**
     * @return TransportClient
     */
    public function withNoSSLVerify() : TransportClient
    {
        $object = clone $this;
        $config = $object->getClient()->getConfig();
        $config['verify'] = false;
        if (isset($config['curl'])) {
            if (!is_array($config['curl'])) {
                unset($config['curl']);
            }
            if (isset($config['curl'])) {
                $config['curl'][CURLOPT_SSL_VERIFYHOST] = false;
                $config['curl'][CURLOPT_SSL_VERIFYPEER] = false;
            }
        }
        $object->client = new Client($config);
        return $object;
    }

    /**
     * @return TransportClient
     */
    public function withSSLVerify() : TransportClient
    {
        $object = clone $this;
        $config = $object->getClient()->getConfig();
        $config['verify'] = true;
        if (isset($config['curl'])) {
            if (!is_array($config['curl'])) {
                unset($config['curl']);
            }
            if (isset($config['curl'])) {
                $config['curl'][CURLOPT_SSL_VERIFYHOST] = true;
                $config['curl'][CURLOPT_SSL_VERIFYPEER] = true;
            }
        }

        $object->client = new Client($config);
        return $object;
    }

    /**
     * Magic Method to instance stream
     *
     * @param string $name
     * @param array $arguments
     *
     * @return ResponseInterface
     */
    public static function __callStatic(string $name, array $arguments) : ResponseInterface
    {
        array_unshift($arguments, $name);
        return call_user_func_array(
            [
                static::class,
                'requestConnection'
            ],
            $arguments
        );
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        array_unshift($arguments, $name);
        return call_user_func_array([$this, 'request'], $arguments);
    }

    /**
     * Request Socket for domain
     *
     * @param string $dataToWrite
     * @param string|UriInterface $server
     *
     * @return ResponseInterface
     * @throws \Throwable
     */
    public static function requestSocketConnectionWrite(string $dataToWrite, $server) : ResponseInterface
    {
        /**
         * Make Domain Name To Custom Request
         */
        // create uri
        $uri         = self::createUri($server);
        $dataToWrite = $uri->getPort() === 43 ? trim($dataToWrite) . "\r\n" : $dataToWrite;
        $args        = func_get_args();
        array_shift($args);
        $request = new Request($dataToWrite, $uri);
        $stream = @fsockopen(
            $request->getUri()->getHost(),
            $request->getUri()->getPort(),
            $errCode,
            $errMessage
        );

        if (!$stream) {
            throw self::thrownExceptionResource(
                $request,
                new ResourceException($errMessage, $errCode),
                false
            );
        }

        $stream = new Stream($stream);
        $stream->write($dataToWrite);

        return new Response(200, [], $stream);
    }

    /**
     * Aliases
     *
     * @uses requestSocketConnectionWrite()
     *
     * @param string $domainName
     * @param string|UriInterface $server
     *
     * @return ResponseInterface
     */
    public static function whoIsRequest(string $domainName, $server) : ResponseInterface
    {
        return static::requestSocketConnectionWrite($domainName, $server);
    }

    /**
     * Get Request Socket
     *
     * @param string $domainName
     * @param string $server
     * @param int    $port        determine port request
     *
     * @return ResponseInterface
     */
    public static function requestDomainConnection(
        string $domainName,
        string $server,
        int $port = null
    ) : ResponseInterface {
        /**
         * Make Domain Name To Custom Request
         */
        $domainName = trim($domainName) ."\r\n";
        $args = func_get_args();
        $args[0] = $domainName;
        return call_user_func_array(
            [static::class, 'requestStreamConnection'],
            $args
        );
    }

    /**
     * Request With Stream Connection
     *
     * @param string $method
     * @param string|UriInterface $uri
     * @param array $options
     *
     * @return ResponseInterface
     */
    public static function requestConnection(string $method, $uri, array $options = []) : ResponseInterface
    {
        // create uri
        $args = func_get_args();
        array_shift($args);
        $clone = self::createForStreamSocket();
        if (! $uri instanceof UriInterface) {
            if (!is_string($uri)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Parameter Uri must be string or instance of %s',
                        UriInterface::class
                    )
                );
            }
            $uri = call_user_func_array([$clone, 'createUri'], $args);
        }

        return $clone
            ->request(
                $method,
                $uri,
                $options
            );
    }

    /**
     * @return HandlerStack
     */
    public static function createStackHandler() : HandlerStack
    {
        return HandlerStack::create(static::chooseHandler());
    }

    /**
     * @return callable
     */
    public static function chooseHandler()
    {
        $handler = null;
        if (function_exists('curl_multi_exec') && function_exists('curl_exec')) {
            $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
        } elseif (function_exists('curl_exec')) {
            $handler = new CurlHandler();
        } elseif (function_exists('curl_multi_exec')) {
            $handler = new CurlMultiHandler();
        }

        if (ini_get('allow_url_fopen')) {
            $handler = $handler
                ? Proxy::wrapStreaming($handler, new StreamHandler())
                : new StreamHandler();
        } elseif (!$handler) {
            throw new \RuntimeException(
                'GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler.'
            );
        }

        return $handler;
    }

    /**
     * Create guzzle client for socket that returning response has no body
     *
     * @param array $options
     *
     * @return TransportClient
     */
    public static function createForStreamSocket(array $options = []) : TransportClient
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
     * @return TransportClient
     */
    public static function createClient(array $options = []) : TransportClient
    {
        // with no headers
        $defaultOptions = ['handler' => static::createStackHandler()];
        $options =  array_merge($defaultOptions, $options);
        return static::createForStreamSocket($options);
    }

    /**
     * Get create URI
     *
     * @param string|UriInterface $server
     * @param int    $port
     *
     * @return Uri
     */
    public static function createUri($server, int $port = null) : Uri
    {
        $uri = $server instanceof UriInterface ? $server : new Uri($server);
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
     * @param bool|ResponseInterface $response
     * @param bool $throw
     * @param \Throwable $e
     * @return \Throwable
     * @throws \Throwable
     */
    public static function thrownExceptionResource(
        RequestInterface $request,
        \Throwable $e,
        $response = true,
        $throw = true
    ) {
        $responseCopy = $response;
        $response = $response instanceof ResponseInterface
            ? $response
            : ($throw instanceof ResponseInterface ? $throw : null);
        $throw = $responseCopy instanceof ResponseInterface
            ? (bool) $throw
            : (bool) $responseCopy;

        if ($e->getCode() !== 0 && (
                $e instanceof ResourceException
                || $e instanceof RequestException
                || $e instanceof \GuzzleHttp\Exception\RequestException
            )
        ) {
            switch ($e->getCode()) {
                case AF_INET:
                case CURLE_COULDNT_CONNECT:
                    $e = new ConnectionFailException($e->getMessage(), $request, $e);
                    break;
                case SOCKET_ETIMEDOUT:
                case CURLE_OPERATION_TIMEDOUT:
                case CURLE_OPERATION_TIMEOUTED:
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
                case CURLE_COULDNT_RESOLVE_HOST:
                case CURLE_COULDNT_RESOLVE_PROXY:
                    $e = new ConnectionException($e->getMessage(), $request, $e);
                    break;
            }
        }

        if ($response instanceof ResponseInterface && method_exists($e, 'setResponse')) {
            $e->setResponse($response);
        }

        if ($throw) {
            throw $e;
        }

        return $e;
    }
}
