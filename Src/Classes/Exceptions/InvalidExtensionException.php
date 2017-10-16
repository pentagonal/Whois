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
 * Class InvalidExtensionException
 * @package Pentagonal\WhoIs\Exceptions
 */
class InvalidExtensionException extends InvalidDomainException
{
    /**
     * @var string
     */
    protected $extension;

    /**
     * InvalidExtensionException constructor.
     *
     * @param string $message
     * @param int $code
     * @param string|mixed $extension
     * @param string|mixed $domainName
     */
    public function __construct($message = "", $code = 0, $domainName = null, $extension = null)
    {
        parent::__construct($message, $code, $domainName);
        if (is_string($extension) || is_numeric($extension)) {
            $this->extension = (string) $extension;
        } else {
            $this->extension = gettype($extension);
        }
    }

    /**
     * Get Extension
     *
     * @return string
     */
    public function getExtension() : string
    {
        return $this->extension;
    }
}
