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

use GuzzleHttp\Promise\PromiseInterface;
use function GuzzleHttp\Promise\settle;
use Pentagonal\WhoIs\Abstracts\WhoIsRequestAbstract;

/**
 * Class WhoIsMultiRequest
 * @package Pentagonal\WhoIs\App
 * @final
 */
final class WhoIsMultiRequest extends WhoIsRequestAbstract
{
    /**
     * @var WhoIsRequest[]|ArrayCollector
     */
    protected $whoIsRequests;

    /**
     * @var \Throwable
     */
    protected $exceptions;

    /**
     * WhoIsMultiRequest constructor.
     *
     * @param array $whoIsRequests
     */
    public function __construct(array $whoIsRequests)
    {
        foreach ($whoIsRequests as $key => $whoIsRequest) {
            if (!$whoIsRequest instanceof WhoIsRequest) {
                throw new \InvalidArgumentException(
                    'Argument array contain invalid values',
                    E_NOTICE
                );
            }
        }

        $this->whoIsRequests = new ArrayCollector($whoIsRequests);
    }

    /**
     * Get Requests
     *
     * @return WhoIsRequest[]
     */
    public function getRequests() : array
    {
        return $this->whoIsRequests->toArray();
    }

    /**
     * @return WhoIsRequest[]
     */
    public function getSendRequests() : array
    {
        return $this->send()->whoIsRequests->toArray();
    }

    /**
     * Get List of Promise Request
     *
     * @return array
     */
    public function getPromisesRequests() : array
    {
        $result = [];
        foreach ($this->whoIsRequests as $key => $request) {
            $result[$key] = $request->getPromiseRequest();
        }
        return $result;
    }

    /**
     * Send Request
     *
     * @return WhoIsMultiRequest|WhoIsRequestAbstract
     * @throws \Throwable
     */
    public function send() : WhoIsRequestAbstract
    {
        if ($this->isPendingRequest()) {
            $this->status = self::PROGRESS;
            try {
                $arrayPromise = settle($this->getPromisesRequests())->wait(true);
                // call wait settle
                foreach ($arrayPromise as $key => $request) {
                    $request = $request['state'] === PromiseInterface::FULFILLED
                        ? $request['value']
                        : $request['reason'];
                    $this->whoIsRequests[$key]->setResponseFromMultiRequest($request);
                }
                $this->status = self::SUCCESS;
            } catch (\Throwable $e) {
                $this->status = self::FAILED;
                // for record state if failed
                $this->exceptions = $e;
                throw $e;
            }
        }

        return $this;
    }
}
