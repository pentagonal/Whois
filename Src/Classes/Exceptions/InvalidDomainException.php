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

/**
 * Class InvalidDomainException
 * @package Pentagonal\WhoIs\Exceptions
 */
class InvalidDomainException extends \DomainException
{
    /**
     * @var string
     */
    protected $domainName;

    /**
     * InvalidDomainException constructor.
     *
     * @param string $message
     * @param int $code
     * @param string|mixed $domainName
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, $domainName = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if (is_string($domainName) || is_numeric($domainName)) {
            $this->domainName = (string) $domainName;
        } else {
            $this->domainName = gettype($domainName);
        }
    }

    /**
     * @return string
     */
    public function getDomainName() : string
    {
        return $this->domainName;
    }
}
