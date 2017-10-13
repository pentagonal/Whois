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

use GuzzleHttp\Handler\EasyHandle;

/**
 * Class CurlHandlerInvoker
 * @package Pentagonal\WhoIs\Handler
 */
class CurlHandlerInvoker
{

    /**
     * @param EasyHandle $easy
     * @return mixed
     */
    public static function invokeProcess(EasyHandle &$easy)
    {
        if (!$easy instanceof EasyHandle
            || $easy->request->getUri()->getPort() !== 43
        ) {
            return false;
        }

        $info = is_resource($easy->handle) ? curl_getinfo($easy->handle) : [];
        // socket 43 does not require headers so make it headers 200
        if ($easy->errno === 0 || ($easy->sink->getSize() > 0 || empty($info['http_code']))) {
            if (empty($easy->headers)) {
                $easy->headers[] = 'HTTP/1.1 200 OK';
            }

            return true;
        }

        return null;
    }

    /**
     * @param EasyHandle $easy
     */
    public static function invokeRequest(EasyHandle &$easy)
    {
        if ($easy->request->getUri()->getPort() !== 43) {
            return;
        }

        // if use port 43 headers is not important anymore because it was use telnet
        // maybe?
        curl_setopt(
            $easy->handle,
            CURLOPT_WRITEFUNCTION,
            function ($ch, $h) use (&$easy) {
                return $easy->sink->write($h);
            }
        );
    }
}
