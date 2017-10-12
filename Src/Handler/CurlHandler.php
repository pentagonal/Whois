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

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlFactoryInterface;
use Pentagonal\WhoIs\Exceptions\ConnectionException;
use Psr\Http\Message\RequestInterface;

/**
 * HTTP handler that uses cURL easy handles as a transport layer.
 *
 * When using the CurlHandler, custom curl options can be specified as an
 * associative array of curl option constants mapping to values in the
 * **curl** key of the "client" key of the request.
 */
class CurlHandler
{
    /**
     * @var CurlFactoryInterface
     */
    protected $factory;

    /**
     * Accepts an associative array of options:
     *
     * - factory: Optional curl factory used to create cURL handles.
     *
     * @param array $options Array of options to use with the handler
     */
    public function __construct(array $options = [])
    {
        $this->factory = isset($options['handle_factory'])
            ? $options['handle_factory']
            : new CurlFactory(3);
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Throwable
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        if (isset($options['delay'])) {
            usleep($options['delay'] * 1000);
        }

        if ($request->getUri()->getPort() !== 43) {
            $easy = $this->factory->create($request, $options);
            curl_exec($easy->handle);
            $easy->errno = curl_errno($easy->handle);
            return CurlFactory::finish($this, $easy, $this->factory);
        }

        if (!isset($options['curl'])) {
            $options['curl'] = [];
        }

        $easy = $this->factory->create($request, $options);

        // if use port 43 headers is not important anymore because it was use telnet
        // maybe?
        $options['curl'][CURLOPT_WRITEFUNCTION] = function ($ch, $h) use (&$easy) {
            return $easy->sink->write($h);
        };

        // set option curl
        curl_setopt_array($easy->handle, $options['curl']);
        curl_exec($easy->handle);
        $easy->errno = curl_errno($easy->handle);
        $info = curl_getinfo($easy->handle);

        // socket 43 does not require headers so make it headers 200
        if ($easy->errno === 0 || ($easy->sink->getSize() > 0 || empty($info['htp_code']))) {
            if (empty($easy->headers)) {
                $easy->headers[] = 'HTTP/1.1 200 OK';
            }

            $easy->createResponse();
        }
        try {
            return CurlFactory::finish($this, $easy, $this->factory);
        } catch (ConnectException $e) {
            if (!$easy->request instanceof RequestInterface) {
                throw $e;
            }

            $code = $e->getCode();
            if (!$code && $easy->errno) {
                $code = (int) $easy->errno;
            }
            $e = new ConnectionException($e->getMessage(), $code);
            $e->setLine($e->getLine());
            $e->setFile($e->getFile());
            throw TransportClient::thrownExceptionResource($easy->request, $e, false);
        }
    }
}
