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

namespace Pentagonal\WhoIs\Abstracts;

/**
 * Class WhoIsRequestAbstract
 * @package Pentagonal\WhoIs\Abstracts
 */
abstract class WhoIsRequestAbstract
{
    const PENDING  = 'pending';
    const PROGRESS = 'progress';
    const SUCCESS  = 'success';
    const FAILED   = 'failed';

    /**
     * @var bool
     */
    protected $hasSend = false;

    /**
     * @var string
     */
    protected $status = self::PENDING;

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
    public function isProgressRequest() : bool
    {
        return $this->getStatus() === self::PROGRESS;
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
    public function isHasSend() : bool
    {
        return $this->hasSend;
    }

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Send all request
     *
     * @return WhoIsRequestAbstract
     */
    abstract public function send() : WhoIsRequestAbstract;
}
