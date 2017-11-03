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

use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlFactoryInterface;
use GuzzleHttp\Handler\EasyHandle;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\RequestInterface;

/**
 * Returns an asynchronous response using curl_multi_* functions.
 *
 * When using the CurlMultiHandler, custom curl options can be specified as an
 * associative array of curl option constants mapping to values in the
 * **curl** key of the provided request options.
 *
 * @property resource $_mh Internal use only. Lazy loaded multi-handle.
 */
class CurlMultiHandler
{
    /**
     * @var CurlFactoryInterface
     */
    private $factory;
    private $selectTimeout;
    private $active;
    private $handles = [];
    private $delays = [];

    /**
     * CurlMultiHandler constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->factory = isset($options['handle_factory'])
            ? $options['handle_factory'] : new CurlFactory(50);
        $this->selectTimeout = isset($options['select_timeout'])
            ? $options['select_timeout'] : 1;
    }

    /**
     * @param string $name
     *
     * @return resource
     */
    public function __get(string $name)
    {
        if ($name === '_mh') {
            return $this->_mh = curl_multi_init();
        }

        throw new \BadMethodCallException();
    }

    /**
     * Magic Method Destruct
     */
    public function __destruct()
    {
        if (isset($this->_mh)) {
            curl_multi_close($this->_mh);
            unset($this->_mh);
        }
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     *
     * @return Promise
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $easy = $this->factory->create($request, $options);
        $id = (int) $easy->handle;
        $promise = new Promise(
            [$this, 'execute'],
            function () use ($id) {
                return $this->cancel($id);
            }
        );

        $this->addRequest(['easy' => $easy, 'deferred' => $promise]);

        return $promise;
    }

    /**
     * Ticks the curl event loop.
     */
    public function tick()
    {
        // Add any delayed handles if needed.
        if ($this->delays) {
            $currentTime = microtime(true);
            foreach ($this->delays as $id => $delay) {
                if ($currentTime >= $delay) {
                    unset($this->delays[$id]);
                    curl_multi_add_handle(
                        $this->_mh,
                        $this->handles[$id]['easy']->handle
                    );
                }
            }
        }

        // Step through the task queue which may add additional requests.
        P\queue()->run();

        if ($this->active &&
            curl_multi_select($this->_mh, $this->selectTimeout) === -1
        ) {
            // Perform a usleep if a select returns -1.
            // See: https://bugs.php.net/bug.php?id=61141
            usleep(250);
        }

        do {
            $process = curl_multi_exec($this->_mh, $this->active);
        } while (!isset($process) || $process === CURLM_CALL_MULTI_PERFORM);

        $this->processMessages();
    }

    /**
     * Runs until all outstanding connections have completed.
     */
    public function execute()
    {
        $queue = P\queue();

        while ($this->handles || !$queue->isEmpty()) {
            // If there are no transfers, then sleep for the next delay
            if (!$this->active && $this->delays) {
                usleep($this->timeToNext());
            }
            $this->tick();
        }
    }

    /**
     * @param array $entry
     */
    private function addRequest(array $entry)
    {
        /**
         * @var EasyHandle $easy
         */
        $easy = $entry['easy'];

        $id = (int) $easy->handle;
        $this->handles[$id] = $entry;

        // invoke Request
        CurlHandlerInvoker::invokeRequest($easy);
        if (empty($easy->options['delay'])) {
            curl_multi_add_handle($this->_mh, $easy->handle);
        } else {
            $this->delays[$id] = microtime(true) + ($easy->options['delay'] / 1000);
        }
    }

    /**
     * Cancels a handle from sending and removes references to it.
     *
     * @param int $id Handle ID to cancel and remove.
     *
     * @return bool True on success, false on failure.
     */
    private function cancel($id)
    {
        // Cannot cancel if it has been processed.
        if (!isset($this->handles[$id])) {
            return false;
        }

        $handle = $this->handles[$id]['easy']->handle;
        unset($this->delays[$id], $this->handles[$id]);
        curl_multi_remove_handle($this->_mh, $handle);
        curl_close($handle);

        return true;
    }

    /**
     * Process Message
     */
    private function processMessages()
    {
        while ($done = curl_multi_info_read($this->_mh)) {
            $id = (int) $done['handle'];
            curl_multi_remove_handle($this->_mh, $done['handle']);

            if (!isset($this->handles[$id])) {
                // Probably was cancelled.
                continue;
            }

            $entry = $this->handles[$id];
            unset($this->handles[$id], $this->delays[$id]);
            /**
             * @var Promise[]|EasyHandle[] $entry
             */
            $easy = $entry['easy'];
            $easy->errno = $done['result'];

            // invoke process
            if (CurlHandlerInvoker::invokeProcess($easy) === true) {
                // create response if succeed
                $easy->createResponse();
            }
            $entry['deferred']->resolve(
                CurlFactory::finish(
                    $this,
                    $easy,
                    $this->factory
                )
            );
        }
    }

    /**
     * @return int|mixed
     */
    private function timeToNext()
    {
        $currentTime = microtime(true);
        $nextTime = PHP_INT_MAX;
        foreach ($this->delays as $time) {
            if ($time < $nextTime) {
                $nextTime = $time;
            }
        }

        return max(0, $nextTime - $currentTime) * 1000000;
    }
}
