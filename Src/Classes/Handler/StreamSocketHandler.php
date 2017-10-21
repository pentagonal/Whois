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

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\TransferStats;
use Pentagonal\WhoIs\Exceptions\ConnectionException;
use Pentagonal\WhoIs\Exceptions\ConnectionFailException;
use Pentagonal\WhoIs\Exceptions\ResourceException;
use Pentagonal\WhoIs\Util\TransportClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class StreamSocketHandler
 * @package Pentagonal\WhoIs\Handler
 */
class StreamSocketHandler
{
    /**
     * @var array
     */
    private $lastHeaders = [];

    /**
     * @var bool
     */
    private $proxyData = false;

    /**
     * Sends an HTTP request.
     *
     * @param RequestInterface $request Request to send.
     * @param array            $options Request transfer options.
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        // Sleep if there is a delay specified.
        if (isset($options['delay'])) {
            usleep($options['delay'] * 1000);
        }

        $startTime = isset($options['on_stats']) ? microtime(true) : null;

        try {
            // Does not support the expect header.
            $request = $request->withoutHeader('Expect');

            // Append a content-length header if body size is zero to match
            // cURL's behavior.
            if (0 === $request->getBody()->getSize()) {
                $request = $request->withHeader('Content-Length', 0);
            }

            return $this->createResponse(
                $request,
                $options,
                $this->createStream($request, $options),
                $startTime
            );
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (ResourceException $e) {
            $e = TransportClient::thrownExceptionResource($request, $e, false);
            $this->invokeStats($options, $request, $startTime, null, $e);

            return \GuzzleHttp\Promise\rejection_for($e);
        } catch (\Exception $e) {
            // Determine if the error was a networking error.
            $message = $e->getMessage();
            // This list can probably get more comprehensive.
            if (strpos($message, 'getaddrinfo') // DNS lookup failed
                || strpos($message, 'Connection refused')
                || strpos($message, "couldn't connect to host") // error on HHVM
            ) {
                $e = new ConnectionException($e->getMessage(), $request, $e);
                $e->setCode($e->getCode());
            }

            $e = RequestException::wrapException($request, $e);
            $this->invokeStats($options, $request, $startTime, null, $e);

            return \GuzzleHttp\Promise\rejection_for($e);
        }
    }

    /**
     * @param array $options
     * @param RequestInterface $request
     * @param $startTime
     * @param ResponseInterface|null $response
     * @param null $error
     */
    private function invokeStats(
        array $options,
        RequestInterface $request,
        $startTime,
        ResponseInterface $response = null,
        $error = null
    ) {
        if (isset($options['on_stats'])) {
            $stats = new TransferStats(
                $request,
                $response,
                microtime(true) - $startTime,
                $error,
                []
            );
            call_user_func($options['on_stats'], $stats);
        }
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param $stream
     * @param $startTime
     *
     * @return FulfilledPromise|PromiseInterface
     */
    private function createResponse(
        RequestInterface $request,
        array $options,
        $stream,
        $startTime
    ) {
        $HeadersArray = $this->lastHeaders;
        $this->lastHeaders = [];
        $parts = explode(' ', array_shift($HeadersArray), 3);
        $explode = isset($parts[0]) ? explode('/', $parts[0]) : [];
        $ver = isset($explode[1]) ? $explode[1] : '';
        $status = isset($parts[1]) ? $parts[1] : null;
        $reason = isset($parts[2]) ? $parts[2] : null;
        $headers = \GuzzleHttp\headers_from_lines($HeadersArray);
        list ($stream, $headers) = $this->checkDecode($options, $headers, $stream);
        $stream = Psr7\stream_for($stream);
        $sink = $stream;

        if (strcasecmp('HEAD', $request->getMethod())) {
            $sink = $this->createSink($stream, $options);
        }

        $response = new Psr7\Response($status, $headers, $sink, $ver, $reason);

        if (isset($options['on_headers'])) {
            try {
                $options['on_headers']($response);
            } catch (\Exception $e) {
                $ex = new RequestException(
                    'An error was encountered during the on_headers event',
                    $request,
                    $response,
                    $e
                );
                return \GuzzleHttp\Promise\rejection_for($ex);
            }
        }

        // Do not drain when the request is a HEAD request because they have
        // no body.
        if ($sink !== $stream) {
            $this->drain(
                $stream,
                $sink,
                $response->getHeaderLine('Content-Length')
            );
        }

        $this->invokeStats($options, $request, $startTime, $response, null);
        if ($this->proxyData) {
            /** @noinspection PhpUndefinedFieldInspection */
            $response->proxyConnection = $this->proxyData;
        }

        return new FulfilledPromise($response);
    }

    /**
     * @param StreamInterface $stream
     * @param array $options
     *
     * @return Psr7\LazyOpenStream|Psr7\Stream|StreamInterface
     */
    private function createSink(StreamInterface $stream, array $options) : StreamInterface
    {
        if (!empty($options['stream'])) {
            return $stream;
        }

        $sink = isset($options['sink'])
            ? $options['sink']
            : fopen('php://temp', 'r+');

        return is_string($sink)
            ? new Psr7\LazyOpenStream($sink, 'w+')
            : Psr7\stream_for($sink);
    }

    /**
     * @param array $options
     * @param array $headers
     * @param $stream
     *
     * @return array
     */
    private function checkDecode(array $options, array $headers, $stream)
    {
        // Automatically decode responses when instructed.
        if (!empty($options['decode_content'])) {
            $normalizedKeys = \GuzzleHttp\normalize_header_keys($headers);
            if (isset($normalizedKeys['content-encoding'])) {
                $encoding = $headers[$normalizedKeys['content-encoding']];
                if ($encoding[0] === 'gzip' || $encoding[0] === 'deflate') {
                    $stream = new Psr7\InflateStream(
                        Psr7\stream_for($stream)
                    );
                    $headers['x-encoded-content-encoding']
                            = $headers[$normalizedKeys['content-encoding']];
                    // Remove content-encoding header
                    unset($headers[$normalizedKeys['content-encoding']]);
                    // Fix content-length header
                    if (isset($normalizedKeys['content-length'])) {
                        $headers['x-encoded-content-length']
                            = $headers[$normalizedKeys['content-length']];

                        $length = (int) $stream->getSize();
                        if ($length === 0) {
                            unset($headers[$normalizedKeys['content-length']]);
                        } else {
                            $headers[$normalizedKeys['content-length']] = [$length];
                        }
                    }
                }
            }
        }

        return [$stream, $headers];
    }

    /**
     * Drains the source stream into the "sink" client option.
     *
     * @param StreamInterface $source
     * @param StreamInterface $sink
     * @param string          $contentLength Header specifying the amount of
     *                                       data to read.
     *
     * @return StreamInterface
     * @throws \RuntimeException when the sink option is invalid.
     */
    private function drain(
        StreamInterface $source,
        StreamInterface $sink,
        $contentLength
    ) {
        // If a content-length header is provided, then stop reading once
        // that number of bytes has been read. This can prevent infinitely
        // reading from a stream when dealing with servers that do not honor
        // Connection: Close headers.
        Psr7\copy_to_stream(
            $source,
            $sink,
            (strlen($contentLength) > 0 && (int) $contentLength > 0) ? (int) $contentLength : -1
        );

        $sink->seek(0);
        $source->close();

        return $sink;
    }

    /**
     * Create a resource and check to ensure it was created successfully
     *
     * @param callable $callback Callable that returns stream resource
     *
     * @return resource
     * @throws \RuntimeException on error
     */
    private function createResource(callable $callback)
    {
        $errors = null;
        $errCode = 0;
        set_error_handler(function ($code, $msg, $file, $line) use (&$errors, &$errCode) {
            $errors[] = [
                'message' => $msg,
                'file'    => $file,
                'line'    => $line
            ];
            $errCode = $code;
            return true;
        });

        $resource = $callback();
        restore_error_handler();

        if (!$resource) {
            $message = 'Error creating resource: ';
            foreach ($errors as $err) {
                foreach ($err as $key => $value) {
                    $message .= "[$key] $value" . PHP_EOL;
                }
            }
            throw new ResourceException(
                trim($message),
                $errCode
            );
        }

        return $resource;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     *
     * @return resource
     */
    private function createStream(RequestInterface $request, array $options)
    {
        static $methods;
        if (!$methods) {
            $methods = array_flip(get_class_methods(__CLASS__));
        }

        // HTTP/1.1 streams using the PHP stream wrapper require a
        // Connection: close header
        if ($request->getProtocolVersion() == '1.1'
            && !$request->hasHeader('Connection')
        ) {
            $request = $request->withHeader('Connection', 'close');
        }

        // Ensure SSL is verified by default
        if (!isset($options['verify'])) {
            $options['verify'] = true;
        }

        $params = [];
        $context = $this->getDefaultContext($request);

        if (isset($options['on_headers']) && !is_callable($options['on_headers'])) {
            throw new \InvalidArgumentException(
                'on_headers must be callable'
            );
        }

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $method  = ucfirst($key);
                if ($key != $method) {
                    $method = "addFor{$method}";
                    if (isset($methods[$method])) {
                        $this->{$method}($request, $context, $value, $params);
                    }
                }
            }
        }

        if (isset($options['stream_context'])) {
            if (!is_array($options['stream_context'])) {
                throw new \InvalidArgumentException('stream_context must be an array');
            }
            $context = array_replace_recursive(
                $context,
                $options['stream_context']
            );
        }

        // Microsoft NTLM authentication only supported with curl handler
        if (isset($options['auth'])
            && is_array($options['auth'])
            && isset($options['auth'][2])
            && 'ntlm' == $options['auth'][2]
        ) {
            throw new \InvalidArgumentException(
                'Microsoft NTLM authentication only supported with curl handler'
            );
        }

        $uri = $this->resolveHost($request, $options);
        $notification = isset($params['notification'])
            && is_callable($params['notification'])
            ? $params['notification']
            : function () {
            };
        $context = $this->createResource(
            function () use ($context, $params) {
                return stream_context_create($context, $params);
            }
        );

        return $this->createResource(
            function () use ($uri, &$http_response_header, $context, $options, $request, $notification) {
                if ($uri->getPort() === 43) {
                    $server = $uri->getHost();
                    $port = $uri->getPort();
                    $method = isset($options['curl'][CURLOPT_CUSTOMREQUEST])
                        ? rtrim($options['curl'][CURLOPT_CUSTOMREQUEST])."\r\n"
                        : rtrim($request->getMethod()) . "\r\n";

                    /**
                     * I WAS NOT RESOLVE TO MAKE PROXY WORK WITH
                     * whois.arin.net:43 via socket
                     */
                    $scheme = '';
                    if (stripos($server, 'arin.') === false && isset($options['proxy'])) {
                        if (is_string($options['proxy'])) {
                            $options['proxy'] = parse_url($options['proxy']);
                        }
                        if (!is_array($options['proxy'])) {
                            $options['proxy'] = [];
                        }
                        $proxy = $options['proxy'];
                        $scheme = isset($proxy['scheme'])
                            ? rtrim($proxy['scheme'], '/:') .'://'
                            : '';
                    }

                    $useProxy        = !empty($proxy['host']) && !empty($proxy['port']);
                    $proxy           = $useProxy ? "{$scheme}{$proxy['host']}:{$proxy['port']}" : null;
                    $this->proxyData = $proxy;
                    $server          = "{$server}:{$port}";
                    $connect         = $useProxy ? $proxy : $server;
                    $info = ($useProxy
                        ? "Connecting to proxy   : {$proxy}"
                        : "Connecting to socket  : {$server}"
                    );
                    $notification(
                        STREAM_NOTIFY_CONNECT,
                        null,
                        $info
                    );
                    $connectTimeOut = isset($options['connect_timeout'])
                        ? $options['connect_timeout']
                        : null;
                    $connectTimeOut = $connectTimeOut === null
                        ? (ini_get('default_socket_timeout')?:5)
                        : 5;
                    $resource = stream_socket_client(
                        $connect,
                        $errNumber,
                        $errMessage,
                        $connectTimeOut,
                        STREAM_CLIENT_CONNECT,
                        $context
                    );

                    if (!$resource && $errNumber) {
                        // write notification
                        $notification(
                            STREAM_NOTIFY_FAILURE,
                            $errMessage,
                            null,
                            $errNumber
                        );

                        throw new ResourceException(
                            $errMessage,
                            $errNumber
                        );
                    }
                    // write notification
                    $notification(
                        STREAM_NOTIFY_RESOLVE,
                        null,
                        ($useProxy
                            ? "Connected to proxy    : {$proxy}"
                            : "Connected to          : {$connect}"
                        )
                    );

                    if (isset($options['read_timeout'])) {
                        $readTimeout = $options['read_timeout'];
                        $second = (int) $readTimeout;
                        $uSecond = ($readTimeout - $second) * 100000;
                        stream_set_timeout($resource, $second, $uSecond);
                    }

                    if ($useProxy) {
                        $notification(
                            STREAM_NOTIFY_CONNECT,
                            null,
                            "Requesting connection : {$server}"
                        );
                        if (fwrite($resource, "CONNECT {$server}\r\n\r\n") === false) {
                            $err = "Request failed to : {$server} using proxy {$connect}";
                            $notification(
                                STREAM_NOTIFY_FAILURE,
                                CURLE_SEND_ERROR,
                                $err
                            );
                            throw new ResourceException(
                                $err,
                                CURLE_SEND_ERROR
                            );
                        }

                        // write notification resolve
                        $info = "Connected to          : {$server}";
                        $notification(
                            STREAM_NOTIFY_RESOLVE,
                            null,
                            $info
                        );
                        $method = rtrim($method)."\r\n\r\n";
                    }

                    // write notification progress
                    $info = "Sending command      : " . rtrim($method) . " to {$server}";
                    $notification(
                        STREAM_NOTIFY_PROGRESS,
                        null,
                        $info
                    );

                    if (fwrite($resource, $method) === false) {
                        // write notification failure
                        $err = "Failed to send command : {$method} into {$server}";
                        $notification(
                            STREAM_NOTIFY_FAILURE,
                            CURLE_SEND_ERROR,
                            $err
                        );
                        throw new ResourceException(
                            $err,
                            CURLE_SEND_ERROR
                        );
                    }

                    // call notification
                    $notification(
                        STREAM_NOTIFY_RESOLVE,
                        null,
                        "Command " . rtrim($method) . " successfully sent"
                    );
                    $notification(STREAM_NOTIFY_COMPLETED);
                    $this->lastHeaders = (array) $http_response_header;
                } else {
                    $resource          = fopen((string)$uri, 'r', null, $context);
                    $this->lastHeaders = $http_response_header;

                    if (isset($options['read_timeout'])) {
                        $readTimeout = $options['read_timeout'];
                        $second = (int) $readTimeout;
                        $uSecond = ($readTimeout - $second) * 100000;
                        stream_set_timeout($resource, $second, $uSecond);
                    }
                }

                return $resource;
            }
        );
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     *
     * @return \Psr\Http\Message\UriInterface|static
     */
    private function resolveHost(RequestInterface $request, array $options)
    {
        $uri = $request->getUri();

        if (isset($options['force_ip_resolve']) && !filter_var($uri->getHost(), FILTER_VALIDATE_IP)) {
            if ('v4' === $options['force_ip_resolve']) {
                $records = dns_get_record($uri->getHost(), DNS_A);
                if (!isset($records[0]['ip'])) {
                    throw new ConnectException(
                        sprintf("Could not resolve IPv4 address for host '%s'", $uri->getHost()),
                        $request
                    );
                }
                $uri = $uri->withHost($records[0]['ip']);
            } elseif ('v6' === $options['force_ip_resolve']) {
                $records = dns_get_record($uri->getHost(), DNS_AAAA);
                if (!isset($records[0]['ipv6'])) {
                    throw new ConnectException(
                        sprintf("Could not resolve IPv6 address for host '%s'", $uri->getHost()),
                        $request
                    );
                }
                $uri = $uri->withHost('[' . $records[0]['ipv6'] . ']');
            }
        }

        return $uri;
    }

    /**
     * @param RequestInterface $request
     *
     * @return array
     */
    private function getDefaultContext(RequestInterface $request)
    {
        $headers = '';
        foreach ($request->getHeaders() as $name => $value) {
            foreach ($value as $val) {
                $headers .= "$name: $val\r\n";
            }
        }
        $httpContext = [
            'method'           => $request->getMethod(),
            'header'           => $headers,
            'protocol_version' => $request->getProtocolVersion(),
            'ignore_errors'    => true,
            'follow_location'  => 0,
        ];
        if ($request->getUri()->getPort() === 43) {
            $httpContext['header'] = '';
            unset(
                $httpContext['protocol_version'],
                $httpContext['method']
            );
        }
        $context = [
            'http' => $httpContext,
        ];

        $body = (string) $request->getBody();

        if (!empty($body)) {
            $context['http']['content'] = $body;
            // Prevent the HTTP handler from adding a Content-Type header.
            if (!$request->hasHeader('Content-Type')) {
                $context['http']['header'] .= "Content-Type:\r\n";
            }
        }

        $context['http']['header'] = rtrim($context['http']['header']);

        return $context;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param mixed $value
     * @param mixed $params
     */
    private function addForProxy(RequestInterface $request, &$options, $value, &$params)
    {
        if (!is_array($value)) {
            $options['http']['proxy'] = $value;
        } else {
            $scheme = $request->getUri()->getScheme();
            if (isset($value[$scheme])) {
                if (!isset($value['no'])
                    || !\GuzzleHttp\is_host_in_noproxy(
                        $request->getUri()->getHost(),
                        $value['no']
                    )
                ) {
                    $options['http']['proxy'] = $value[$scheme];
                }
            }
        }
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param mixed $value
     * @param mixed $params
     */
    private function addForTimeout(RequestInterface $request, &$options, $value, &$params)
    {
        if ($value > 0) {
            $options['http']['timeout'] = $value;
        }
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param mixed $value
     * @param mixed $params
     */
    private function addForVerify(RequestInterface $request, &$options, $value, &$params)
    {
        if ($value === true) {
            // PHP 5.6 or greater will find the system cert by default. When
            // < 5.6, use the Guzzle bundled cacert.
            if (PHP_VERSION_ID < 50600) {
                $options['ssl']['cafile'] = \GuzzleHttp\default_ca_bundle();
            }
        } elseif (is_string($value)) {
            $options['ssl']['cafile'] = $value;
            if (!file_exists($value)) {
                throw new \RuntimeException("SSL CA bundle not found: $value");
            }
        } elseif ($value === false) {
            $options['ssl']['verify_peer'] = false;
            $options['ssl']['verify_peer_name'] = false;
            return;
        } else {
            throw new \InvalidArgumentException('Invalid verify request option');
        }

        $options['ssl']['verify_peer'] = true;
        $options['ssl']['verify_peer_name'] = true;
        $options['ssl']['allow_self_signed'] = false;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param mixed $value
     * @param mixed $params
     */
    private function addForCert(RequestInterface $request, &$options, $value, &$params)
    {
        if (is_array($value)) {
            $options['ssl']['passphrase'] = $value[1];
            $value = $value[0];
        }

        if (!file_exists($value)) {
            throw new \RuntimeException("SSL certificate not found: {$value}");
        }

        $options['ssl']['local_cert'] = $value;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param mixed $value
     * @param mixed $params
     */
    private function addForProgress(RequestInterface $request, &$options, $value, &$params)
    {
        $this->addNotification(
            $params,
            function ($code, $a, $b, $c, $transferred, $total) use ($value) {
                if ($code == STREAM_NOTIFY_PROGRESS) {
                    $value($total, $transferred, null, null);
                }
            }
        );
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @param mixed $value
     * @param array $params
     */
    private function addForDebug(RequestInterface $request, &$options, $value, &$params)
    {
        if ($value === false) {
            return;
        }

        static $map = [
            STREAM_NOTIFY_CONNECT       => 'CONNECT',
            STREAM_NOTIFY_AUTH_REQUIRED => 'AUTH_REQUIRED',
            STREAM_NOTIFY_AUTH_RESULT   => 'AUTH_RESULT',
            STREAM_NOTIFY_MIME_TYPE_IS  => 'MIME_TYPE_IS',
            STREAM_NOTIFY_FILE_SIZE_IS  => 'FILE_SIZE_IS',
            STREAM_NOTIFY_REDIRECTED    => 'REDIRECTED',
            STREAM_NOTIFY_PROGRESS      => 'PROGRESS',
            STREAM_NOTIFY_FAILURE       => 'FAILURE',
            STREAM_NOTIFY_COMPLETED     => 'COMPLETED',
            STREAM_NOTIFY_RESOLVE       => 'RESOLVE',
        ];
        static $args = ['severity', 'message', 'message_code',
            'bytes_transferred', 'bytes_max'];

        $value = \GuzzleHttp\debug_resource($value);
        $ident = $request->getMethod() . ' ' . $request->getUri()->withFragment('');
        $this->addNotification(
            $params,
            function () use ($ident, $value, $map, $args) {
                $passed = func_get_args();
                $code = array_shift($passed);
                fprintf($value, '<%s> [%s] ', $ident, $map[$code]);
                foreach (array_filter($passed) as $i => $v) {
                    fwrite($value, $args[$i] . ': "' . $v . '" ');
                }
                fwrite($value, "\n");
            }
        );
    }

    /**
     * @param array $params
     * @param callable $notify
     */
    private function addNotification(array &$params, callable $notify)
    {
        // Wrap the existing function if needed.
        if (!isset($params['notification'])) {
            $params['notification'] = $notify;
        } else {
            $params['notification'] = $this->callArray([
                $params['notification'],
                $notify
            ]);
        }
    }

    /**
     * @param array $functions
     *
     * @return \Closure
     */
    private function callArray(array $functions)
    {
        return function () use ($functions) {
            $args = func_get_args();
            foreach ($functions as $fn) {
                call_user_func_array($fn, $args);
            }
        };
    }
}
