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

namespace Pentagonal\WhoIs\Util;

use Guzzle\Http\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use function GuzzleHttp\Psr7\str;
use GuzzleHttp\Psr7\Uri;
use Pentagonal\WhoIs\Exceptions\ConnectionException;
use Pentagonal\WhoIs\Exceptions\ConnectionFailException;
use Pentagonal\WhoIs\Exceptions\ConnectionRefuseException;
use Pentagonal\WhoIs\Exceptions\HttpBadAddressException;
use Pentagonal\WhoIs\Exceptions\HttpExpiredException;
use Pentagonal\WhoIs\Exceptions\HttpPermissionException;
use Pentagonal\WhoIs\Exceptions\ResourceException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Handler\CurlHandler;
use Pentagonal\WhoIs\Handler\CurlMultiHandler;
use Pentagonal\WhoIs\Handler\StreamSocketHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Client Creator To fix Guzzle Client Socket Enable
 *
 * Class TransportClient
 * @package Pentagonal\WhoIs\Util
 *
 * @method static TransportClient get($uri, array $options = [])
 * @method static TransportClient post($uri, array $options = [])
 * @method static TransportClient put($uri, array $options = [])
 * @method static TransportClient delete($uri, array $options = [])
 * @method static TransportClient trace($uri, array $options = [])
 * @method static TransportClient view($uri, array $options = [])
 * @method static TransportClient patch($uri, array $options = [])
 * @method static TransportClient head($uri, array $options = [])
 */
class TransportClient
{
    const DEFAULT_PORT = DataParser::PORT_WHOIS;

    /**
     * User Agent
     */
    public $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) '
            . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $defaultOptions = [
        'ssl'    => [
            'certificate_authority' => DataParser::PATH_CACERT,
        ]
    ];

    /**
     * @var Promise\PromiseInterface[]
     */
    protected $promiseRequests;

    /**
     * @var int|string|null
     */
    protected $currentPromiseKey;

    /**
     * @var array
     */
    protected $configs = [];

    /**
     * TransportClient constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->configs = $client->getConfig();
        $this->defaultOptions['handler'] = $this->createStackHandler();
        if (is_string($this->userAgent)) {
            $this->defaultOptions['headers'] = [
                'User-Agent' => $this->userAgent
            ];
        }
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
     * Get Current set Request Promise
     *
     * @return Promise\PromiseInterface|mixed
     */
    public function getCurrentRequest()
    {
        $this->currentPromiseKey = key($this->promiseRequests);
        return current($this->promiseRequests);
    }

    /**
     * @return int|null|string
     */
    public function getCurrentRequestKey()
    {
        return $this->currentPromiseKey;
    }

    /**
     * Is End of Request Parallel
     *
     * @return bool
     */
    public function isEndOfRequest() : bool
    {
        return ! $this->getCurrentRequest();
    }

    /**
     * Send Parallel, send for queue
     * consider & must be @uses isEndOfRequest() when using iteration loop
     *
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function sendSerial()
    {
        if (empty($this->promiseRequests)) {
            throw new \RuntimeException(
                'Requests is empty',
                E_USER_NOTICE
            );
        }

        if ($this->isEndOfRequest()) {
            throw new \RuntimeException(
                'Current Request is empty',
                E_NOTICE
            );
        }

        $currentRequest = $this->getCurrentRequest();
        next($this->promiseRequests);

        $response = $currentRequest->wait();
        if ($response instanceof \Throwable) {
            /**
             * @var BadResponseException $e
             */
            $e = $response;
            $request = $e->getRequest();
            $response = $e->getResponse();
            throw self::thrownExceptionResource($request, $e, $response, false);
        }

        return $response;
    }

    /**
     * Sending Async request and convert it into Array Result
     * @see sendAsync()
     *
     * @return array|ResponseInterface[]|\Throwable[]
     */
    public function send() : array
    {
        $result = [];
        foreach ($this->sendAsync() as $key => $arrayPromise) {
            if ($arrayPromise['state'] === Promise\Promise::REJECTED) {
                /**
                 * @var BadResponseException $e
                 */
                $e = $arrayPromise['reason'];
                $result[$key] = $e;
                $request = $e->getRequest();
                $response = $e->getResponse();
                if ($response instanceof ResponseInterface && $request instanceof RequestInterface) {
                    $result[$key] = self::thrownExceptionResource($request, $e, $response, false);
                }

                continue;
            }

            $result[$key] = $arrayPromise['value'];
        }

        return $result;
    }

    /**
     * Send all request and returning original promise wait response
     *
     * @return array|ResponseInterface[]|\Throwable[]
     */
    public function sendAsync() : array
    {
        if (empty($this->promiseRequests)) {
            throw new \RuntimeException(
                'Requests is empty',
                E_USER_NOTICE
            );
        }

        return Promise\settle($this->promiseRequests)->wait();
    }

    /**
     * Get All promise Requests
     *
     * @return array
     */
    public function getPromiseRequests() : array
    {
        return $this->promiseRequests;
    }

    /**
     * @param string $name
     *
     * @return TransportClient
     */
    public function setCurrentRequestName(string $name) : TransportClient
    {
        $keys = array_keys($this->promiseRequests);
        if (empty($keys)) {
            return $this;
        }
        $key  = end($keys);
        $keys[array_search($key, $keys)] = $name;
        $this->promiseRequests = array_combine($keys, array_values($this->promiseRequests));

        return $this;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function mergeOptions(array $options) : array
    {
        if (empty($options)) {
            return $this->configs;
        }

        $original = $this->configs;
        foreach ($options as $key => $value) {
            $original[$key] = $value;
        }

        return $original;
    }

    /**
     * Make a Request
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return TransportClient
     * @throws \Throwable
     * @throws ConnectException
     */
    public function addRequest($uri, string $method = 'GET', array $options = [])
    {
        if (!$uri instanceof UriInterface) {
            $uri = $this->createUri($uri);
        }

        $stringUri = (string) $uri;
        $customRequest = null;
        $scheme = $uri->getScheme();
        if ($uri->getPort() === self::DEFAULT_PORT && $scheme == '') {
            if (!isset($options['stream']) && ! empty($options['handler'])) {
                $options['stream'] = $options['handler'] instanceof StreamSocketHandler
                     || $options['handler'] instanceof HandlerStack;
            }

            $customRequest = $options['curl'][CURLOPT_CUSTOMREQUEST] = $method;
            $options['curl'][CURLOPT_PROTOCOLS] = CURLPROTO_ALL;
            // if has custom request REQUEST METHOD GET
            $stringUri = ($scheme?: 'socket') . '://'.ltrim($stringUri, '/');
        }

        if ($uri->getPort() === 43) {
            if (empty($options['headers']) || !is_array($options['headers'])) {
                $options['headers'] = [];
            }

            $options['headers']['User-Agent'] = '';
            $options['headers']['Host'] = $uri->getHost();
        }

        if (!empty($uri->postMethod) && is_array($uri->postMethod)) {
            $params = isset($options['form_params'])
                ? (array) $options['form_params']
                : [];
            $options['form_params'] = array_merge($uri->postMethod, $params);
        }

        $key = [
            'uri'    => $stringUri,
            'port'   => $uri->getPort(),
            'method' => $method,
            'custom_request' => $customRequest,
            'microtime'   => microtime(true)
        ];

        $this->promiseRequests[json_encode($key)] = $this->getClient()->requestAsync($method, $uri, $options);
        return $this;
    }

    /**
     * @param string $userAgent
     *
     * @return TransportClient
     */
    public function withUserAgent(string $userAgent) : TransportClient
    {
        $object = clone $this;
        if (!isset($object->configs['headers']) || !is_array($object->configs['headers'])) {
            $object->configs['headers'] = [];
        }

        foreach ($object->configs['headers'] as $key => $v) {
            if (is_string($key) && strtolower($key) === 'user-agent') {
                unset($object->configs['headers'][$key]);
            }
        }

        $object->configs['headers']['User-Agent'] = $userAgent;
        $object->client = new Client($object->configs);
        return $object;
    }

    /**
     * @return TransportClient
     */
    public function withoutUserAgent() : TransportClient
    {
        $object = clone $this;
        if (!isset($object->configs['headers']) || !is_array($object->configs['headers'])) {
            $object->configs['headers'] = [];
        }
        foreach ($object->configs['headers'] as $key => $v) {
            if (is_string($key) && strtolower($key) === 'user-agent') {
                unset($object->configs['headers'][$key]);
            }
        }

        $object->configs['headers']['User-Agent'] = null;
        $object->client = new Client($object->configs);
        return $object;
    }

    /**
     * @return TransportClient
     */
    public function withNoSSLVerify() : TransportClient
    {
        $object = clone $this;
        $object->configs['verify'] = false;
        if (isset($object->configs['curl'])) {
            if (!is_array($object->configs['curl'])) {
                unset($object->configs['curl']);
            }
            if (isset($object->configs['curl'])) {
                $object->configs['curl'][CURLOPT_SSL_VERIFYHOST] = false;
                $object->configs['curl'][CURLOPT_SSL_VERIFYPEER] = false;
            }
        }
        $object->client = new Client($object->configs);
        return $object;
    }

    /**
     * @return TransportClient
     */
    public function withSSLVerify() : TransportClient
    {
        $object = clone $this;
        $object->configs['verify'] = true;
        if (isset($object->configs['curl'])) {
            if (!is_array($object->configs['curl'])) {
                unset($object->configs['curl']);
            }
            if (isset($object->configs['curl'])) {
                $object->configs['curl'][CURLOPT_SSL_VERIFYHOST] = true;
                $object->configs['curl'][CURLOPT_SSL_VERIFYPEER] = true;
            }
        }

        $object->client = new Client($object->configs);
        return $object;
    }

    /**
     * Request Socket for domain
     *
     * @param string $dataToWrite
     * @param string|UriInterface $uri
     * @param array $options
     *
     * @return TransportClient
     */
    public static function requestSocketConnectionWrite(
        string $dataToWrite,
        $uri,
        array $options = []
    ) : TransportClient {
        /**
         * Make Domain Name To Custom Request
         */
        // create uri
        $uri         = $uri instanceof UriInterface ? $uri : self::createUri($uri);
        if ($uri->getPort() === 43) {
            $dataToWrite = trim($dataToWrite) . "\r\n";
            if (in_array($uri->getHost(), [
                DataParser::ARIN_SERVER,
                DataParser::RIPE_SERVER,
            ])) {
                // set options
                $options['handler'] = HandlerStack::create(new StreamSocketHandler());
            }
        }

        return self::createForStreamSocket($options)->addRequest($uri, $dataToWrite, $options);
    }

    /**
     * Aliases
     *
     * @uses requestSocketConnectionWrite()
     *
     * @param string $domainName
     * @param string|UriInterface $server
     * @param array $options
     *
     * @return TransportClient
     */
    public static function whoIsRequest(string $domainName, $server, array $options = []) : TransportClient
    {
        return static::requestSocketConnectionWrite($domainName, $server, $options);
    }

    /**
     * Get Request Socket
     *
     * @param string $domainName
     * @param string $server
     * @param int    $port        determine port request
     *
     * @return TransportClient
     */
    public static function requestDomainConnection(
        string $domainName,
        string $server,
        int $port = null
    ) : TransportClient {
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
     * @return TransportClient
     */
    public static function requestConnection($uri, string $method = 'GET', array $options = []) : TransportClient
    {
        // create uri
        $clone = self::createForStreamSocket();
        $headers = $clone->getClient()->getConfig('headers');
        if (strpos($method, "\n") === false) {
            $method = strtoupper($method);
            if ((empty($headers['User-Agent'])
                    || is_string($headers['User-Agent'])
                    && strpos($headers['User-Agent'], 'GuzzleHttp') === 0
                ) && is_string($clone->userAgent)
            ) {
                $clone = $clone->withUserAgent($clone->userAgent);
            }
        }

        if (! $uri instanceof UriInterface) {
            if (!is_string($uri)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Parameter Uri must be string or instance of %s',
                        UriInterface::class
                    )
                );
            }
            $uri = static::createUri($uri);
        }

        return $clone
            ->addRequest(
                $uri,
                $method,
                $options
            );
    }

    /**
     * @var HandlerStack
     */
    protected static $stackHandler;

    /**
     * @var callable
     */
    protected static $chosenHandler;

    /**
     * @return HandlerStack
     */
    public static function createStackHandler() : HandlerStack
    {
        if (self::$stackHandler && self::$stackHandler instanceof HandlerStack) {
            return clone self::$stackHandler;
        }

        return self::$stackHandler = HandlerStack::create(static::chooseHandler());
    }

    /**
     * @return callable
     */
    public static function chooseHandler()
    {
        if (self::$chosenHandler && is_callable(self::$chosenHandler)) {
            return clone self::$chosenHandler;
        }

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
                ? Proxy::wrapStreaming($handler, new StreamSocketHandler())
                : HandlerStack::create(new StreamSocketHandler());
        } elseif (!$handler) {
            throw new \RuntimeException(
                'GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler.'
            );
        }

        return self::$chosenHandler = $handler;
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
        $options =  array_merge([
            'handler' => static::createStackHandler()
        ], $options);
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
        if (is_string($server)
            && preg_match('~^POST\[([^\]]+)\]\|(.+)~', $server, $match)
            && !empty($match[2])
        ) {
            $server = new Uri($match[2]);
            $postData = [];
            $postExplode = array_filter(explode('&', $match[1]));
            array_map(function ($query) use (&$postData) {
                preg_match('/^([^\=]+)\=(.+)?/', ltrim($query), $match);
                if (!empty($match[1])) {
                    $postData[$match[1]] = $match[2];
                }
            }, $postExplode);
            /** @noinspection PhpUndefinedFieldInspection */
            $server->postMethod = $postData;
        }

        if (is_string($server) && strpos($server, '//') !== 0) {
            $serverArray = parse_url($server);
            if (empty($serverArray['scheme'])) {
                $server = "//{$server}";
            }
        }

        $uri = $server instanceof UriInterface ? $server : new Uri($server);
        $uriScheme = $uri->getScheme();
        $uriPort   = $uri->getPort();
        if ($uriScheme === null) {
            $uriScheme = '';
            $uri = $uri->withScheme($uriScheme);
        }
        if ($port !== null) {
            $uri = $uri->withPort($port);
        } elseif ($uriPort === null && $uri->getScheme() === '') {
            $uri = $uri->withPort(static::DEFAULT_PORT);
        }

        if ($uri->getHost() == '' && $uri->getPath()) {
            $path = '/';
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

    /* --------------------------------------------------------------------------------*
     |                                MAGIC METHOD                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments) : TransportClient
    {
        array_unshift($arguments, array_shift($arguments), strtoupper($name));
        return call_user_func_array([$this, 'addRequest'], $arguments);
    }

    /**
     * Magic Method to instance stream
     *
     * @param string $name
     * @param array $arguments
     *
     * @return TransportClient
     */
    public static function __callStatic(string $name, array $arguments) : TransportClient
    {
        array_unshift($arguments, array_shift($arguments), strtoupper($name));
        return call_user_func_array(
            [
                static::class,
                'requestConnection'
            ],
            $arguments
        );
    }
}
