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

use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Util\DataParser;
use Pentagonal\WhoIs\Util\TransportClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class WhoIsRequest
 * @package Pentagonal\WhoIs\App
 * @final
 * @access protected
 */
final class WhoIsRequest
{
    const PENDING  = 'pending';
    const PROGRESS = 'progress';
    const SUCCESS  = 'success';
    const FAILED   = 'failed';

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
     * @var bool
     */
    protected $hasSend = false;

    /**
     * @var string
     */
    protected $status = self::PENDING;

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
     * @return bool
     */
    public function isHasSend(): bool
    {
        return $this->hasSend;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
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
     * @return ResponseInterface|\Throwable
     */
    public function getResponse()
    {
        $this->send();
        return $this->response;
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
    private function prepareUri()
    {
        if ($this->uri instanceof UriInterface) {
            return;
        }

        $this->uri = TransportClient::createUri($this->server);
        $this->socketMethod = $this->method;
        $this->isUseSocket = $this->uri->getPort() === TransportClient::DEFAULT_PORT;
        if ($this->isUseSocket) {
            $validator = new Validator();
            if ($validator->isValidIP(trim($this->targetName))) {
                $this->socketMethod = DataParser::buildNetworkAddressCommandServer(
                    trim($this->targetName) . "\r\n",
                    $this->uri->getHost()
                );
            } // resolve asn
            elseif (preg_match(DataParser::ASN_REGEX, trim($this->targetName), $match)
                && ! empty($match[2])
            ) {
                $prefix = $this->uri->getHost() != 'whois.arin.net'
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
                $this->server = str_replace('{{domain}}', $this->targetName, rawurldecode($this->server));
            } else {
                $query = $this->getUri()->getQuery() . $this->targetName;
            }
            $this->uri  = $this->uri->withQuery($query);
            $this->query = $this->uri->getQuery();
        }
    }

    /**
     * @return \Exception|ResponseInterface|\Throwable
     */
    private function sendRequest()
    {
        $this->countRequest += 1;
        try {
            if ($this->isUseSocket()) {
                $this->response = TransportClient::whoIsRequest(
                    $this->socketMethod,
                    $this->getUri(),
                    $this->options
                );
                $this->status = self::SUCCESS;
                return $this->response;
            }
            $options = $this->options;
            if (!isset($options['headers'])) {
                $options['headers'] = [];
            }
            // auto referer
            if (!isset($options['headers']['referer'])) {
                $options['headers']['referer'] = (string) $this->getUri()->withQuery('');
            }
            $this->response = TransportClient::requestConnection(
                $this->getMethod(),
                $this->getUri(),
                $options
            );

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
     * @return WhoIsRequest
     */
    public function send() : WhoIsRequest
    {
        if ($this->isPendingRequest()) {
            $this->hasSend = true;
            $this->status = self::PROGRESS;
            $this->bodyString = null;
            $this->sendRequest();
        }

        return $this;
    }

    /**
     * @return WhoIsRequest
     */
    public function retry() : WhoIsRequest
    {
        if (!isset($this->response) || $this->isError()) {
            $this->status = self::PENDING;
            $this->send();
        }

        return $this;
    }

    /* --------------------------------------------------------------------------------*
     |                                   STATUS                                        |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @return bool
     */
    public function isPendingRequest() : bool
    {
        return $this->getStatus() === self::PENDING;
    }

    /**
     * @return bool
     */
    public function isFail() : bool
    {
        return $this->getStatus() === self::FAILED;
    }

    /**
     * @return bool
     */
    public function isSuccess() : bool
    {
        return $this->getStatus() === self::SUCCESS;
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
     * @return bool
     */
    public function getProxyConnection()
    {
        $response = $this->getResponse();
        if (!$response instanceof ResponseInterface) {
            return false;
        }
        return isset($response->proxyConnection)
            && is_string($response->proxyConnection)
            ? $response->proxyConnection
            : false;
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
