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

namespace Pentagonal\WhoIs\Exceptions;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpException
 * @package Pentagonal\WhoIs\Exceptions
 */
class HttpException extends RequestException
{
    /**
     * @var RequestInterface|null $request
     */
    protected $request;

    /**
     * @var ResponseInterface|null $response
     */
    protected $response;

    /**
     * HttpException constructor.
     *
     * @param string $message
     * @param RequestInterface $request
     * @param null $response
     * @param \Exception|null $previous
     * @param array $handlerContext
     */
    public function __construct(
        $message,
        $request,
        $response = null,
        $previous = null,
        $handlerContext = null
    ) {
        $code = is_int($request) ? $request : 0;
        $request = $request instanceof RequestInterface ? $request : null;
        $exception = $response instanceof \Exception ? $response : null;
        $exception = $exception === null && $previous instanceof \Exception
            ? $previous
            : null;
        $context = is_array($previous) ? $previous : null;
        if ($context === null) {
            $context = is_array($handlerContext) ? $handlerContext : null;
        }
        if (!is_array($context)) {
            $context = [];
        }
        $exception = $exception === null && $handlerContext instanceof \Exception
            ? $handlerContext
            : null;
        $response = $response instanceof ResponseInterface
            ? $response
            : null;
        $code = $code === 0 && $response ? $response->getStatusCode() : $code;
        $this->setCode($code);
        $this->message = $message;
        if ($request instanceof RequestException
            && ($response === null || $response instanceof ResponseInterface)
        ) {
            parent::__construct($message, $request, $response, $exception, $context);
        }
    }

    /**
     * @param int $code
     */
    public function setCode(int $code)
    {
        $this->code = $code;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @param string $file
     */
    public function setFile(string $file)
    {
        $this->file = $file;
    }

    /**
     * @param int $line
     */
    public function setLine(int $line)
    {
        $this->line = $line;
    }

    /**
     * Get the request that caused the exception
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the associated response
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
