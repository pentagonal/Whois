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

namespace Pentagonal\WhoIs\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

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
        return $this->getClient()->request($method, $uri, $options);
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
    public function requestSocket(string $domainName, string $server, int $port = null)
    {
        /**
         * Make Domain Name To Custom Request
         */
        $domainName = trim(strtolower($domainName)) ."\r\n";
        // create uri
        $args = func_get_args();
        array_shift($args);
        $uri = call_user_func_array([$this, 'createUri'], $args);
        return $this->request($domainName, $uri);
    }

    /**
     * @return HandlerStack
     */
    public static function createSocketHandler() : HandlerStack
    {
        return HandlerStack::create(new StreamHandler());
    }

    /**
     * Create guzzle client for socket that returning response has no body
     *
     * @param array $options
     *
     * @return TransportSocketClient
     */
    public static function createForSocket(array $options = []) : TransportSocketClient
    {
        $defaultOptions = [
            'handler' => static::createSocketHandler(),
            'on_headers' => function (ResponseInterface $response) {
                $headers = $response->getHeaders();
                if (!empty($headers)) {
                    $header = '';
                    foreach ($headers as $name => $value) {
                        // remove header
                        $response->withoutHeader($name);
                        foreach ($value as $val) {
                            if ($val) {
                                $sep = '';
                                if (strpos($val, '/') !== 0) {
                                    $sep = ' ';
                                }
                                $val = ":{$sep}{$val}";
                            }
                            $header .= "{$name}{$val}\r\n";
                        }
                    }

                    $body = $response->getBody();
                    $body->write($header);
                    $response = $response->withBody($body);
                }

                return $response;
            }
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
        $defaultOptions = [
            'on_headers' => null
        ];

        $options =  array_merge($defaultOptions, $options);
        return static::createForSocket($options);
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
}
