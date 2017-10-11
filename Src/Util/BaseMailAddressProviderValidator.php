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

namespace Pentagonal\WhoIs\Util;

use Pentagonal\WhoIs\App\Validator;

/**
 * Class BaseMailAddressProviderValidator
 * @package Pentagonal\WhoIs\Util
 *
 * Base Mail Provider to validate base on email address target on common email provider
 */
class BaseMailAddressProviderValidator
{
    /**
     * Max & Min Length
     */
    # G-mail
    const MAX_GMAIL_LENGTH = 30;
    const MIN_GMAIL_LENGTH = 30;

    # Base Microsoft Mail
    const MAX_MICROSOFT_LENGTH = 64;
    const MIN_MICROSOFT_LENGTH = 1;

    # Base Yahoo Mail
    const MAX_YAHOO_LENGTH = 32;
    const MIN_YAHOO_LENGTH = 1;

    # mail com Mail
    const MAX_MAIL_COM_LENGTH = 50;
    const MIN_MAIL_COM_LENGTH = 1;

    # yandex Mail
    const MAX_YANDEX_LENGTH = 30;
    const MIN_YANDEX_LENGTH = 1;

    /**
     * @var string
     */
    protected $baseMail;

    /**
     * @var boolean that must be false detect
     */
    protected $isMustBeInvalid;

    /**
     * BaseMailAddressProviderValidator constructor.
     *
     * @param string $baseMail
     * @final mark as final construct
     */
    final public function __construct(string $baseMail)
    {
        $this->baseMail = strtolower($baseMail);
        // reset
        $this->isMustBeInvalid = null;
        if (trim($this->baseMail) == '') {
            $this->isMustBeInvalid = true;
        }
    }

    /**
     * Get Base mail set
     *
     * @return string
     */
    public function getBaseMailAddress() : string
    {
        return $this->baseMail;
    }

    /**
     * Is this must be invalid
     *
     * @return bool
     */
    public function isMustBeInvalid() : bool
    {
        if (!isset($this->isMustBeInvalid)) {
            $baseMail              = $this->getBaseMailAddress();
            $this->isMustBeInvalid = (bool) (
                // email must be 1 or more
                strlen(trim($baseMail)) < 1
                // email address must be less than 255 characters
                || strlen($baseMail) > 254
                // email address only allowed a-zA-Z0-9\_\.\-
                || preg_match('/[^a-zA-Z0-9\_\.\-]/i', $baseMail)
                // could not start with @, period (.) , -
                || in_array(substr_count($baseMail, 0, 1), ['.', '-'])
                // could not end with @ and period (.)
                || in_array(substr_count($baseMail, -1), ['.'])
                // could not (.-), (..), (._), (-.)
                || preg_match('/[\.][\.\-_]|[\-]|\@\./', $baseMail)
                # could not contain invalid characters
                || preg_match('/[' . preg_quote(Validator::INVALID_CHARS_DOMAIN, '/') . '\s]/', $baseMail)
                // must be start or end with alpha numeric characters
                || preg_match('/(?:^[^a-z0-9])|(?:[^a-z0-9\_\-]$)/i', $baseMail)
            );
        }

        return $this->isMustBeInvalid;
    }

    /**
     * Is G-Mail Address is Valid
     *
     * @return bool
     */
    public function isValidGMail() : bool
    {
        if (!$this->isMustBeInvalid()) {
            return false;
        }

        $length = strlen($this->getBaseMailAddress());
        return
            $length >= static::MIN_GMAIL_LENGTH
            && $length <= static::MAX_GMAIL_LENGTH
            // only allow alpha numeric and periods
            // and start with letter or number
            && preg_match('/^[a-z0-9](?:[a-z0-9\.]+?[a-z0-9])$/i', $this->getBaseMailAddress());
    }

    /**
     * Check if valid microsoft mail
     *
     * @return bool
     */
    public function isValidMicrosoftMail() : bool
    {
        if ($this->isMustBeInvalid()) {
            return false;
        }

        $length = strlen($this->getBaseMailAddress());
        return
            $length >= static::MIN_MICROSOFT_LENGTH
            && $length <= static::MAX_MICROSOFT_LENGTH
            // must be start with letter and only allow contains alpha numeric periods underscore
            // and hyphen and end with alpha numeric underscore and hyphen
            && preg_match('/^[a-z]([a-z0-9\.\_\-]+?[a-z0-9\_\-])?$/i', $this->getBaseMailAddress());
    }

    /**
     * Check if Valid Yahoo Mail
     *
     * @return bool
     */
    public function isValidYahooMail() : bool
    {
        if ($this->isMustBeInvalid()) {
            return false;
        }

        $length = strlen($this->getBaseMailAddress());
        return
            $length >= static::MIN_YAHOO_LENGTH
            && $length <= static::MAX_YAHOO_LENGTH
            // only allow alpha numeric period & underscore, must be start with letter
            // and end with alpha numeric
            && preg_match('/^[a-z]([a-z0-9\.\_]+?[a-z0-9])?$/i', $this->getBaseMailAddress());
    }

    /**
     * Check if Valid mail.com Mail
     * inbox gmx null.net etc
     * @return bool
     */
    public function isValidMailComMail() : bool
    {
        if ($this->isMustBeInvalid()) {
            return false;
        }

        $length = strlen($this->getBaseMailAddress());
        return
            $length >= static::MIN_MAIL_COM_LENGTH
            && $length <= static::MAX_MAIL_COM_LENGTH
            // must be start with alpha numeric and only allow contains alpha numeric periods underscore
            // and hyphen and end with alpha numeric underscore and hyphen
            && preg_match('/^[a-z0-9]([a-z0-9\.\_\-]+?[a-z0-9\_\-]$)?/i', $this->getBaseMailAddress());
    }

    /**
     * Check if Valid yandex Mail
     *
     * @return bool
     */
    public function isValidYandexMail() : bool
    {
        if ($this->isMustBeInvalid()) {
            return false;
        }

        $emailAddress =  $this->getBaseMailAddress();
        $length = strlen($this->getBaseMailAddress());
        return
            $length >= static::MIN_YANDEX_LENGTH
            && $length <= static::MAX_YANDEX_LENGTH
            // only allow alpha numeric must be start with letter
            // and must be end with alpha or numeric characters
            && preg_match('/^[a-z](?:\.[a-z0-9]{2,}|[a-z0-9]+)?$/i', $emailAddress);
    }
}
