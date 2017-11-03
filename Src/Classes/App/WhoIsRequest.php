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

use Pentagonal\WhoIs\Abstracts\WhoIsRequestAbstract;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Util\DataParser;
use Pentagonal\WhoIs\Util\TransportClient;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class WhoIsRequest
 * @package Pentagonal\WhoIs\App
 * @final
 * @access protected
 */
final class WhoIsRequest extends WhoIsRequestAbstract
{
    /**
     * @var string
     */
    protected $targetName;

    /**
     * @var string
     */
    protected $server;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $method = "GET";

    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var bool
     */
    protected $isUseSocket;

    /**
     * @var ResponseInterface|\Throwable
     */
    protected $response;

    /**
     * @var int
     */
    protected $countRequest = 0;

    /**
     * @var string
     */
    protected $bodyString;

    /**
     * Original Body String that use First Response From Request
     *
     * @var string
     */
    protected $originalBodyString;

    /**
     * @var string
     */
    protected $socketMethod;

    /**
     * @var null|string
     */
    protected $responseProxyConnection;

    /**
     * @var PromiseInterface
     */
    protected $promiseRequest;

    /**
     * WhoIsRequest constructor.
     *
     * @param string $domainName
     * @param string $server
     * @param array $options
     * @throws \InvalidArgumentException
     */
    final public function __construct(
        string $domainName,
        string $server,
        array $options = []
    ) {
        if (trim($domainName) === '') {
            throw new \InvalidArgumentException(
                'Argument 1 could not be empty or white space only',
                E_WARNING
            );
        }

        $this->targetName = $domainName;
        $this->server     = $server;
        $this->options    = $options;
        $this->firstInit();
        if (isset($this->options['method'])) {
            if (!is_string($this->options['method'])
                || trim($this->options['method']) == ''
            ) {
                unset($this->options['method']);
            } else {
                $this->setMethod($this->options['method']);
            }
        }
    }

    /**
     * Initialize
     */
    protected function firstInit()
    {
        if (preg_match('~https?:\/\/~i', $this->server)) {
            $this->server = str_replace('{{domain}}', $this->targetName, $this->server);
            $this->prepareUri();
            $this->server = (string) $this->uri;
            /** @noinspection PhpUndefinedFieldInspection */
            if (!empty($this->uri->postMethod)) {
                $this->setMethod('POST');
                /** @noinspection PhpUndefinedFieldInspection */
                $params = $this->uri->postMethod;
                $options = isset($this->options['form_params'])
                    && is_array($this->options['form_params'])
                    ? $this->options['form_params']
                    : [];
                $this->options['form_params'] = array_merge($params, $options);
            }
        }
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getTargetName() : string
    {
        return $this->targetName;
    }

    /**
     * @return string
     */
    public function getServer() : string
    {
        return $this->server;
    }

    /**
     * @return array
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        if (!isset($this->uri)) {
            $this->prepareUri();
        }
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Set Response
     *
     * @param ResponseInterface|\Throwable $response
     * @return WhoIsRequest
     * @access internal use for set From multi Request Only
     */
    public function setResponseFromMultiRequest($response) : WhoIsRequest
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $backtrace =  $backtrace? next($backtrace) : [];
        if (empty($backtrace)
            || $backtrace['class'] !== WhoIsMultiRequest::class
        ) {
            throw new \RuntimeException(
                sprintf(
                    '%1$s::%2$s only allow set by object class %3$s',
                    __CLASS__,
                    __FUNCTION__,
                    WhoIsMultiRequest::class
                ),
                E_WARNING
            );
        }

        $this->response = $response;
        return $this;
    }

    /**
     * @return ResponseInterface|\Throwable
     */
    public function getResponse()
    {
        return $this->send()->response;
    }

    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * Get response body to string
     *
     * @return string
     */
    public function getBodyString() : string
    {
        if (!is_string($this->bodyString) || ! is_string($this->originalBodyString)) {
            $this->send();
            $string = '';
            if (!$this->isError()) {
                $string = DataParser::convertResponseBodyToString($this->getResponse());
                // maybe behind the proxy
                if (strpos(ltrim($string), 'HTTP/1.') !== false || strpos(ltrim($string), 'HTTP/2') !== false) {
                    $string = preg_replace(
                        '~^\s*HTTP/(1\.|2)[^\n]+(\r?\n)?~',
                        '',
                        $string
                    );
                }
            }

            // just set if body string has not been set
            if (!is_string($this->bodyString)) {
                $this->bodyString = $string;
            }

            $this->originalBodyString = $string;
        }

        return $this->bodyString;
    }

    /**
     * Get Original Body String, this maybe important to get Real Body String Response
     *
     * @return string
     */
    public function getOriginalBodyString() : string
    {
        $this->getBodyString();
        return $this->originalBodyString;
    }

    /**
     * Set Body String
     *
     * @param string $body
     */
    public function setBodyString(string $body)
    {
        $this->bodyString = $body;
    }

    /**
     * @return bool
     */
    public function isUseSocket() : bool
    {
        $this->prepareUri();
        return $this->isUseSocket;
    }

    /**
     * Preparing Uri
     */
    private function prepareUri() : WhoIsRequest
    {
        if ($this->uri instanceof UriInterface) {
            return $this;
        }

        $this->uri = TransportClient::createUri($this->server);
        $this->socketMethod = $this->method;
        $this->isUseSocket = $this->uri->getPort() === TransportClient::DEFAULT_PORT;
        if ($this->isUseSocket) {
            // using last instance
            $validator = Validator::validatorDefaultInstance();
            if ($validator->isValidIP(trim($this->targetName))) {
                $this->socketMethod = DataParser::buildNetworkAddressCommandServer(
                    trim($this->targetName) . "\r\n",
                    $this->uri->getHost()
                );
            } // resolve asn
            elseif (preg_match(DataParser::ASN_REGEX, trim($this->targetName), $match)
                && ! empty($match[2])
            ) {
                $prefix = $this->uri->getHost() != DataParser::ARIN_SERVER
                    ? 'AS'
                    : '';
                $domainName = "{$prefix}{$match[2]}";
                $this->socketMethod = DataParser::buildASNCommandServer(
                    $domainName . "\r\n",
                    $this->uri->getHost()
                );
            } else {
                $target        = rtrim($this->targetName);
                $pathExtension = pathinfo($target, PATHINFO_EXTENSION);

                // add command to .JP domain
                if ($target && $pathExtension && strtolower($pathExtension) === 'jp') {
                    $target .= '/e';
                }
                $this->socketMethod = rtrim($target) . "\r\n";
            }

            if (empty($this->options['method'])) {
                $this->setMethod($this->socketMethod);
            }
        } elseif ($this->uri->getQuery() !== '') {
            $query = $this->getUri()->getQuery();
            if (strpos(rawurldecode($query), '{{domain}}')) {
                $query = str_replace('{{domain}}', $this->targetName, rawurldecode($query));
            } else {
                $query = $this->getUri()->getQuery() . $this->targetName;
            }
            $this->uri  = $this->uri->withQuery($query);
            $this->query = $this->uri->getQuery();
        }

        return $this;
    }

    /**
     * @return WhoIsRequest
     */
    protected function prepareRequest() : WhoIsRequest
    {
        if ($this->promiseRequest instanceof PromiseInterface) {
            return $this;
        }

        $this->status = self::PROGRESS;
        if ($this->prepareUri()->isUseSocket()) {
            $this->promiseRequest = TransportClient::requestSocketConnectionWrite(
                $this->socketMethod,
                $this->uri,
                $this->options
            )->getCurrentPromiseRequest();
        } else {
            $options = $this->options;
            if (!isset($options['headers'])) {
                $options['headers'] = [];
            }
            // auto referer
            if (!isset($options['headers']['referer'])) {
                $options['headers']['referer'] = (string) $this->uri->withQuery('');
            }

            $this->promiseRequest = TransportClient::requestConnection(
                $this->uri,
                $this->method,
                $options
            )->getCurrentPromiseRequest();
        }

        return $this;
    }

    /**
     * @return PromiseInterface
     */
    public function getPromiseRequest()
    {
        return $this->prepareRequest()->promiseRequest;
    }

    /**
     * @return \Exception|ResponseInterface|\Throwable
     */
    private function sendRequest()
    {
        $this->countRequest += 1;
        try {
            $this->response = $this->getPromiseRequest()->wait();
            if (is_array($this->response)) {
                if ($this->response['state'] === PromiseInterface::REJECTED) {
                    throw $this->response['reason'];
                }
                $this->response = $this->response['value'];
            } elseif ($this->response instanceof \Throwable) {
                throw $this->response;
            }
            $this->status = self::SUCCESS;
        } catch (\Throwable $e) {
            $this->status = self::FAILED;
            $this->response = $e;
        }

        return $this->response;
    }

    /**
     * @return int
     */
    public function getCountRequest() : int
    {
        return $this->countRequest;
    }

    /**
     * @return WhoIsRequestAbstract|WhoIsRequest
     */
    public function send() : WhoIsRequestAbstract
    {
        if ($this->isPendingRequest()) {
            $this->hasSend = true;
            $this->status  = self::PROGRESS;
            $this->bodyString = null;
            $this->sendRequest();
        }

        return $this;
    }

    /**
     * @return WhoIsRequestAbstract
     */
    public function retry() : WhoIsRequestAbstract
    {
        if (!isset($this->response) || $this->isError()) {
            $this->status = self::PENDING;
            $this->send();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isError() : bool
    {
        return $this->getResponse() instanceof \Throwable;
    }

    /**
     * @return bool
     */
    public function isTimeOut() : bool
    {
        return $this->getResponse() instanceof TimeOutException;
    }

    /**
     * Check if response Returning Proxy
     *
     * @return bool|string
     */
    public function getProxyConnection()
    {
        if (isset($this->responseProxyConnection)) {
            return $this->responseProxyConnection;
        }

        $response = $this->getResponse();
        if (!$response instanceof ResponseInterface) {
            return ($this->responseProxyConnection = false);
        }

        return $this->responseProxyConnection = (
            isset($response->proxyConnection)
            && is_string($response->proxyConnection)
            ? $response->proxyConnection
            : false
        );
    }

    /* --------------------------------------------------------------------------------*
     |                                MAGIC METHOD                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->getBodyString();
    }
}
