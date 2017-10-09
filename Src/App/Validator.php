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

/**
 * Class Validator
 * @package Pentagonal\WhoIs\App
 *
 * @todo completion for method
 */
class Validator
{
    /**
     * @var array
     * just add common to prevent spam with sub domain invalid for common reason
     * add it self
     * @uses Validator::isValidEmail()
     */
    protected $commonEmailProvider = [
        'gmail', 'hotmail', 'outlook', 'live',
        // yahoo
        'yahoo', 'ymail', 'rocketmail',
        // yandex
        'yandex',
        // mail com provider
        'hushmail', 'null', 'hush', 'email',
        'hackrmail', 'mail', 'gmx', 'inbox',
    ];

    /**
     * @var array
     */
    protected $mailTestDomain = [
        'mail-tester.com',
        'verifier.port25.com',
    ];

    /**
     * @var TLDCollector
     */
    protected $tldCollector;

    /**
     * Validator constructor.
     *
     * @param TLDCollector|null $collector
     */
    public function __construct(TLDCollector $collector = null)
    {
        $this->tldCollector = $collector?: new TLDCollector();
    }

    /* ------------------------------------------------------ +
     | DOMAIN VALIDATOR                                       |
     + ------------------------------------------------------ */

    /**
     * @todo add Domain Validator
     */

    /* ------------------------------------------------------ +
     | EMAIL VALIDATOR                                        |
     + ------------------------------------------------------ */

    /**
     * @param string $email
     * @param bool $checkMX
     * @param bool $allowIP
     *
     * @return bool
     */
    public function isValidEmail(string $email, $checkMX = false, $allowIP = false) : bool
    {
        if (strlen(trim($email)) < 6 # minimum email length is 6 for minimum eg a@a.aa
            || substr_count($email, '@') <> 1 # email only contain 1 @
            || in_array(substr_count($email, 0, 1), ['@', '.', '-']) # could not start with @, period (.) , -
            || in_array(substr_count($email, -1), ['@', '.']) # could not end with @ and period (.)
        ) {
            return false;
        }

        $emailArray = explode('@', $email);
        $emailAddress = strtolower(array_shift($emailArray));

        // email address must be less than 254 characters
        if (strlen($emailAddress) > 254
            || strpos($emailAddress, '..') === false
            // email address only allowed a-zA-Z0-9\_\.\-
            || preg_match('/[^a-zA-Z0-9\_\.\-]/i', $emailAddress)
            // must be start or end with alpha numeric characters
            // could not double periods
            || preg_match('/(?:^[^a-z0-9])|(?:[^a-z0-9\_\-]$)/i', $emailAddress)
        ) {
            return false;
        }

        $domainName = strtolower(array_shift($emailArray));
        $domainNameArray = explode('.', $domainName);
        $extEnd = array_pop($domainNameArray);
        $baseOrSub = array_pop($domainNameArray);
        if (!empty($domainNameArray)) {
            // check common mail provider
            if (in_array($baseOrSub, $this->commonEmailProvider)) {
                return false;
            }
            $commonProvider = ['hotmail', 'outlook', 'live', 'yahoo', 'rocketmail', 'ymail'];
            $baseDomain = array_pop($domainNameArray);
            if ($baseOrSub == 'co') {
                $isCommon = in_array($baseDomain, $commonProvider);
                if ($isCommon && !empty($domainNameArray)) {
                    return false;
                }
            }
        } elseif (in_array($baseOrSub, $this->commonEmailProvider)) {
            $isCommon   = true;
            $baseDomain = $baseOrSub;
        }

        if (isset($isCommon) && $isCommon && isset($baseDomain)) {
            $retVal = $this->isCommonMailValid($emailAddress, $baseDomain, $extEnd);
            if (is_bool($retVal)) {
                return $retVal;
            }
        }

        $isValidIp = false;
        if (substr_count($domainName, '.') == 4 && substr($domainName, ':') > 1) {
            $isValidIp = $this->isValidIP($domainName);
        }

        # does not allow IP as email base domain
        if ($isValidIp) {
            return $allowIP;
        }

        // if use Check MX just ignore domain check
        if ($checkMX) {
            return $this->isMXExists($domainName);
        }

        // @todo completion logic
    }

    /**
     * Check Common Mail
     * Please use parent::isCommonMailValid() and then check your mail to new method
     * on child classes
     *
     * @param string $emailAddress  email address
     * @param string $baseDomain    base domain detect
     * @param string $ext           base extension
     *
     * @return bool|null  returning null if has no check and bool if check true if valid otherwise false
     */
    protected function isCommonMailValid(string $emailAddress, string $baseDomain, string $ext = null)
    {
        # does not allowed double periods
        if (strpos($emailAddress, '..') === false
            || strlen($emailAddress) > 254
            || in_array(substr_count($emailAddress, 0, 1), ['.', '-']) # could not start with @, period (.) , -
            || in_array(substr_count($emailAddress, -1), ['.']) # could not end with @ and period (.)
        ) {
            return false;
        }

        // make base domain lower
        $baseDomain = strtolower($baseDomain);
        switch ($baseDomain) {
            case 'gmail':
                // only allow alpha numeric and periods
                return ! preg_match('/[^a-z0-9\.]/', $emailAddress);
            case 'hotmail':
            case 'outlook':
            case 'live':
                // must be start with letter and only allow contains alpha numeric periods underscore
                // and hyphen and end with alpha numeric underscore and hyphen
                return (bool) preg_match('/^[a-z]([a-z0-9\.\_\-]+?[a-z0-9\_\-]$)?/', $emailAddress);
            case 'yahoo':
            case 'rocketmail':
            case 'ymail':
                // only allow alpha numeric period & underscore, must be start with letter
                // and end with alpha numeric
                return (bool) preg_match('/^[a-z]([a-z0-9\.\_]+?[a-z0-9]$)?/', $emailAddress);
            case 'hushmail':
            case 'null':
            case 'hush':
            case 'email':
            case 'hackrmail':
            case 'mail':
            case 'gmx':
            case 'inbox':
                // must be start with alpha numeric and only allow contains alpha numeric periods underscore
                // and hyphen and end with alpha numeric underscore and hyphen
                return (bool) preg_match('/^[a-z0-9]([a-z0-9\.\_\-]+?[a-z0-9\_\-]$)?/', $emailAddress);
            case 'yandex':
                // yandex only allow single hyphen & single periods
                // only allow alpha numeric single period or hyphen
                // and must be end with alpha or numeric characters
                return substr_count($emailAddress, '-') < 2
                    && substr_count($emailAddress, '.') < 2
                    && strlen($emailAddress) <= 30
                    && ! preg_match('/[^a-z0-9\.\-]/i', $emailAddress)
                    && ! preg_match('/(?:^[^a-z])|(?:(?:\.[a-z0-9]|[^a-z0-9])$)/', $emailAddress);
        }

        return null;
    }

    /**
     * Check if MX Exists
     *
     * @param string $domainName
     *
     * @return bool
     */
    public function isMXExists(string $domainName) : bool
    {
        if (! $this->isLocalIP($domainName) # don't allow local IP
            && getmxrr($domainName, $mx)
        ) {
            return !empty($mx);
        }

        return false;
    }

    /* ------------------------------------------------------ +
     | IP VALIDATOR                                           |
     + ------------------------------------------------------ */

    /**
     * Check if value valid IP
     *
     * @param string $ip
     *
     * @return bool
     */
    public function isValidIP(string $ip) : bool
    {
        return $this->isIPv4($ip) || $this->isIPv6($ip);
    }

    /**
     * Check if it was Local IP
     *
     * @param string $ip
     *
     * @return bool
     */
    public function isLocalIP(string $ip)
    {
        return $this->isLocalIPv4($ip) || $this->isLocalIPv6($ip);
    }

    /**
     * Validate if it was IPv4
     *
     * @param string $ipv4
     *
     * @return bool
     */
    public function isIPv4(string $ipv4) : bool
    {
        return filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            # no range allowed maybe used / for range
            && ! preg_match('/[^0-9\.]/', $ipv4);
    }

    /**
     * Validate if it was IPv4
     *
     * @param string $ipv6
     *
     * @return bool
     */
    public function isIPv6(string $ipv6) : bool
    {
        return $ipv6 === '::1' # this is local address
            || filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            # no range allowed maybe used / for range
            && ! preg_match('/[^a-f0-9\:]/i', $ipv6);
    }

    /**
     * Check if is local IPV4
     *
     * @param string $ipv4
     *
     * @return bool
     */
    public function isLocalIPv4(string $ipv4) : bool
    {
        # no range allowed maybe used / for range
        return ! preg_match('/(127|1?0|192\.168|169\.254|172\.16)\.|[^0-9\.]/', $ipv4)
            && $this->isIPv4($ipv4)
            && ! filter_var(
                $ipv4,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );
    }

    /**
     * Check if is IPV6 has local address / private
     *
     * @param string $ipv6
     *
     * @return bool
     */
    public function isLocalIPv6(string $ipv6) : bool
    {
        # no range allowed maybe used / for range
        return $this->isIPv6($ipv6)
            && ( $ipv6 === '::1' # this is local IP
                 || filter_var(
                     $ipv6,
                     FILTER_VALIDATE_IP,
                     FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                 )
           );
    }
}
