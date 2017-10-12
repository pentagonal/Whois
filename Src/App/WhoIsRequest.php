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

namespace Pentagonal\WhoIs\App;

use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Handler\TransportClient;
use Pentagonal\WhoIs\Util\DataGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class WhoIsRequest
 * @package Pentagonal\WhoIs\App
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
    protected $domainName;

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
    public function __construct(
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

        $this->domainName = $domainName;
        $this->server     = $server;
        $this->options    = $options;
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
     * @param string $method
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getDomainName() : string
    {
        return $this->domainName;
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
            $this->socketMethod = rtrim($this->domainName) ."\r\n";
            if ($validator->isValidIP(trim($this->domainName))) {
                $this->socketMethod = DataGenerator::buildNetworkAddressCommandServer(
                    trim($this->domainName) . "\r\n",
                    $this->uri->getHost()
                );
            } // resolve asn
            elseif (preg_match('/^(ASN?)?([0-9]{1,20})$/i', trim($this->domainName), $match)
                    && ! empty($match[2])
            ) {
                $domainName = "{$match[1]}{$match[2]}";
                $this->socketMethod = DataGenerator::buildASNCommandServer(
                    $domainName . "\r\n",
                    $this->uri->getHost()
                );
            }
            if (empty($this->options['method'])) {
                $this->setMethod($this->socketMethod);
            }
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
                $this->response = TransportClient::whoIsRequest($this->socketMethod, $this->getUri());
                $this->status = self::SUCCESS;
                return $this->response;
            }
            if (! isset($this->query) && $this->getUri()->getQuery() == '') {
                $this->query = $this->getUri()->getQuery() . $this->domainName;
                $this->uri   = $this->getUri()->withQuery($this->query);
            }

            $this->response = TransportClient::requestConnection(
                $this->getMethod(),
                $this->getUri(),
                $this->options
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
     * @return string
     */
    public function getBodyString() : string
    {
        if (!isset($this->bodyString)) {
            $this->send();
            $this->bodyString = '';
            if (!$this->isError()) {
                $body = clone $this->getResponse()->getBody();
                while (!$body->eof()) {
                    $this->bodyString .= $body->read(4096);
                }
                $body->close();
            }
        }

        return $this->bodyString;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->getBodyString();
    }
}
