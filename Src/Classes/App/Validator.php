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

use Pentagonal\WhoIs\Exceptions\DomainNameTooLongException;
use Pentagonal\WhoIs\Exceptions\DomainSTLDException;
use Pentagonal\WhoIs\Exceptions\EmptyDomainException;
use Pentagonal\WhoIs\Exceptions\InvalidDomainException;
use Pentagonal\WhoIs\Exceptions\InvalidExtensionException;
use Pentagonal\WhoIs\Interfaces\RecordASNNetworkInterface as RAN;
use Pentagonal\WhoIs\Interfaces\RecordDomainNetworkInterface as DRI;
use Pentagonal\WhoIs\Interfaces\RecordIPNetworkInterface as RNI;
use Pentagonal\WhoIs\Record\ASNRecord;
use Pentagonal\WhoIs\Record\DomainRecord;
use Pentagonal\WhoIs\Record\IPRecord;
use Pentagonal\WhoIs\Util\BaseMailAddressProviderValidator;
use Pentagonal\WhoIs\Util\DataParser;

/**
 * Class Validator
 * @package Pentagonal\WhoIs\App
 *
 * Object class validator to validate network type & much of features.
 */
class Validator
{
    /**
     * Determine Length Of Domain
     */
    const MAX_LENGTH_BASE_DOMAIN_NAME = 63;
    const MAX_LENGTH_DOMAIN_NAME = 255;

    const NAME_HOST = 'HOST';
    const NAME_EMAIL = 'EMAIL';
    const NAME_IS_IP = 'IS_IP_ADDRESS';

    /**
     * Special characters is not allowed on domain for common keyboard US Layout
     */
    const INVALID_CHARS_DOMAIN = '!@#$%^&*()`={}[]\\|;\'"<>?,\:/';

    /**
     * @var array
     * just add common to prevent spam with sub domain invalid for common reason
     * add it self
     * @uses Validator::isValidEmail()
     */
    protected $commonEmailProvider = [
        'gmail',
        'hotmail',
        'outlook',
        'live',
        // yahoo
        'yahoo',
        'ymail',
        'rocketmail',
        'aol',
        // yandex
        'yandex',
        // mail com provider
        'hushmail',
        'null',
        'hush',
        'email',
        'programmer',
        'hackermail',
        'mail',
        'gmx',
        'inbox',
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
     * @var string
     */
    protected $baseMailProviderValidatorClass = BaseMailAddressProviderValidator::class;

    /**
     * Validator constructor.
     *
     * @param TLDCollector|null $collector
     * @param string|null $providerValidator
     */
    public function __construct(TLDCollector $collector = null, string $providerValidator = null)
    {
        $this->tldCollector = $collector ?: new TLDCollector();
        if ($providerValidator) {
            $this->baseMailProviderValidatorClass = $providerValidator;
            if (! class_exists($providerValidator)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Validator class of %s has not exists',
                        $providerValidator
                    ),
                    E_WARNING
                );
            }
            if (is_subclass_of($providerValidator, BaseMailAddressProviderValidator::class)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Validator class of %1$s must be instance of %2$s',
                        $providerValidator,
                        BaseMailAddressProviderValidator::class
                    ),
                    E_WARNING
                );
            }
        }

        // revert to default if object class invalid
        if (! is_string($this->baseMailProviderValidatorClass)
             || ! class_exists($this->baseMailProviderValidatorClass)
             || is_subclass_of($providerValidator, BaseMailAddressProviderValidator::class)
        ) {
            $this->baseMailProviderValidatorClass = BaseMailAddressProviderValidator::class;
        }
    }

    /**
     * Create new instance Validator
     *
     * @param TLDCollector|null $collector
     * @param string|null $providerValidator
     *
     * @return Validator
     */
    public static function createInstance(
        TLDCollector $collector = null,
        string $providerValidator = null
    ) : Validator {
        return new static($collector, $providerValidator);
    }

    /**
     * Get TLD Collector
     *
     * @return TLDCollector
     */
    public function getTldCollector(): TLDCollector
    {
        return $this->tldCollector;
    }

    /**
     * Get @class BaseMailAddressProviderValidator object instance
     *
     * @param string $baseAddress
     *
     * @return BaseMailAddressProviderValidator
     */
    public function getBaseMailProviderValidator(
        string $baseAddress
    ): BaseMailAddressProviderValidator {
        return new $this->baseMailProviderValidatorClass($baseAddress);
    }

    /* --------------------------------------------------------------------------------*
     |                              DOMAIN VALIDATOR                                   |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @param string $domainName
     *
     * @return DomainRecord
     * @throws InvalidDomainException
     */
    public function splitDomainName(string $domainName): DomainRecord
    {
        $domainName = strtolower(trim($domainName));
        if ($domainName == '') {
            throw new EmptyDomainException(
                'Domain name could not be empty or whitespace only.',
                E_WARNING
            );
        }

        if (strpos($domainName, '.') === false) {
            throw new InvalidDomainException(
                sprintf(
                    'Domain name %s is not valid',
                    $domainName
                ),
                E_WARNING,
                $domainName
            );
        }
        // check if domain too long
        if (strlen($domainName) > self::MAX_LENGTH_DOMAIN_NAME) {
            throw new DomainNameTooLongException(
                sprintf(
                    'Domain name %s is too long',
                    $domainName
                ),
                E_WARNING,
                $domainName
            );
        }
        if (preg_match(
            '/
                (?:^[\-])
                | [-\_]\.
                | [' . preg_quote(self::INVALID_CHARS_DOMAIN, '/') . '\s\+]
            /ix',
            $domainName
        )) {
            throw new InvalidDomainException(
                sprintf(
                    'Domain name %s contains invalid characters',
                    $domainName
                ),
                E_WARNING,
                $domainName
            );
        }

        $domainArray   = explode('.', $domainName);
        $extension     = array_pop($domainArray);
        $subDomainList = $this->tldCollector->getSubDomainFromExtension($extension);
        if (! $subDomainList) {
            throw new InvalidExtensionException(
                sprintf(
                    'Domain name %1$s is invalid extension with %2$s',
                    $domainName,
                    $extension
                ),
                E_WARNING,
                $domainName,
                $extension
            );
        }

        $utf8Domain      = $this->tldCollector->decode($domainName);
        $domainNameAscii = $this->tldCollector->encode($utf8Domain);

        $result = [
            DRI::NAME_IS_TOP_DOMAIN          => false,
            DRI::NAME_IS_GTLD_DOMAIN         => false,
            DRI::NAME_IS_STLD_DOMAIN         => false,
            DRI::NAME_IS_SUB_DOMAIN          => false,
            DRI::NAME_FULL_DOMAIN_NAME       => $utf8Domain,
            DRI::NAME_FULL_DOMAIN_NAME_ASCII => $domainNameAscii,
            DRI::NAME_BASE_EXTENSION         => $extension,
            DRI::NAME_BASE_EXTENSION_ASCII   => $extension,
            DRI::NAME_EXTENSION              => $extension,
            DRI::NAME_EXTENSION_ASCII        => $extension,
            DRI::NAME_SUB_EXTENSION          => '',
            DRI::NAME_SUB_EXTENSION_ASCII    => '',
            DRI::NAME_MAIN_DOMAIN_NAME       => '',
            DRI::NAME_MAIN_DOMAIN_NAME_ASCII => '',
            DRI::NAME_SUB_DOMAIN_NAME        => '',
            DRI::NAME_SUB_DOMAIN_NAME_ASCII  => '',
            DRI::NAME_BASE_DOMAIN_NAME       => '',
            DRI::NAME_BASE_DOMAIN_NAME_ASCII => '',
        ];

        $subNestedDomain = [];
        foreach ($subDomainList as $value) {
            if (strpos($value, '.')) {
                $subNestedDomain[] = $value;
            }
        }

        $count = count($subDomainList);
        if ($count === 0) {
            $result[DRI::NAME_IS_GTLD_DOMAIN] = true;
        }

        $domainNameSub    = implode('.', $domainArray);
        $countDomainArray = count($domainArray);
        $subExtension     = array_pop($domainArray);
        $mainDomain       = $subExtension;
        if ($countDomainArray < 2) {
            $result[DRI::NAME_IS_TOP_DOMAIN]  = true;
            $result[DRI::NAME_IS_GTLD_DOMAIN] = true;
            if ($countDomainArray === 1) {
                $topDomain = $this->tldCollector->decode($subExtension);
                // domain name too long
                if (strlen($topDomain) > static::MAX_LENGTH_BASE_DOMAIN_NAME) {
                    throw new DomainNameTooLongException(
                        sprintf(
                            'Domain name %s is too long',
                            $domainName
                        ),
                        E_WARNING,
                        $domainName
                    );
                }
                $result[DRI::NAME_MAIN_DOMAIN_NAME]       = $topDomain;
                $result[DRI::NAME_MAIN_DOMAIN_NAME_ASCII] = $this->tldCollector->encode($topDomain);
            }
        }

        $subExtensionAscii = $this->tldCollector->encode($subExtension);
        $extensionAscii    = $this->tldCollector->encode($extension);

        $extension                                = $this->tldCollector->decode($extensionAscii);
        $result[DRI::NAME_FULL_DOMAIN_NAME_ASCII] = $domainNameAscii;
        $result[DRI::NAME_BASE_EXTENSION]         = $extension;
        $result[DRI::NAME_BASE_EXTENSION_ASCII]   = $extensionAscii;
        if ($countDomainArray >= 2) {
            $domainNameSub                           = $this->tldCollector->decode($domainNameSub);
            $result[DRI::NAME_SUB_DOMAIN_NAME]       = $domainNameSub;
            $result[DRI::NAME_SUB_DOMAIN_NAME_ASCII] = $this->tldCollector->encode($domainNameSub);
        }

        if ($subDomainList->contain($subExtensionAscii)) {
            if (count($domainArray) === 0) {
                throw new DomainSTLDException(
                    sprintf(
                        'Domain name %s is an Second Top Level Domain',
                        $domainName
                    ),
                    E_WARNING,
                    $domainName
                );
            }

            $result[DRI::NAME_IS_STLD_DOMAIN]      = ! ($result[DRI::NAME_IS_TOP_DOMAIN]);
            $result[DRI::NAME_IS_TOP_DOMAIN]       = count($domainArray) === 1;
            $result[DRI::NAME_SUB_EXTENSION_ASCII] = $subExtensionAscii;
            $result[DRI::NAME_SUB_EXTENSION]       = $subExtension;
        }

        if (! empty($subNestedDomain)) {
            foreach ($subNestedDomain as $ext) {
                $extPeriod = ".{$ext}.{$extensionAscii}";
                if (substr($domainNameAscii, -strlen($extPeriod)) == $extPeriod) {
                    $extArray    = explode('.', "{$ext}.{$extensionAscii}");
                    $countExt    = count($extArray) - 2;
                    $domainArray = explode('.', $domainName);
                    // remove extension
                    array_pop($extArray);
                    array_pop($domainArray);
                    // remove both sub
                    array_pop($domainArray);
                    $subExtensionArray = [array_pop($extArray)];
                    while ($countExt > 0) {
                        $countExt--;
                        array_unshift($subExtensionArray, array_pop($domainArray));
                    }
                    $subExtension                          = implode('.', $subExtensionArray);
                    $result[DRI::NAME_IS_STLD_DOMAIN]      = true;
                    $result[DRI::NAME_SUB_EXTENSION_ASCII] = $subExtension;
                    $result[DRI::NAME_SUB_EXTENSION]       = $subExtension;
                    if (count($domainArray) == 1) {
                        $result[DRI::NAME_SUB_DOMAIN_NAME_ASCII] = '';
                        $result[DRI::NAME_SUB_DOMAIN_NAME]       = '';
                    }
                    if (empty($domainArray)) {
                        throw new DomainSTLDException(
                            sprintf(
                                'Domain name %s is an Second Top Level Domain',
                                $domainName
                            ),
                            E_WARNING,
                            $domainName
                        );
                    }
                    break;
                }
            }
        }

        $isTopDomain                        = isset($topDomain) || count($domainArray) < 2;
        $result[DRI::NAME_IS_TOP_DOMAIN]    = $isTopDomain;
        $result[DRI::NAME_IS_SUB_DOMAIN]    = ! $isTopDomain;
        $result[DRI::NAME_IS_GTLD_DOMAIN]   = ! $result[DRI::NAME_IS_STLD_DOMAIN];
        $topDomain                          = isset($topDomain)
            ? $topDomain
            : ($isTopDomain ? array_pop($domainArray) : $mainDomain);
        $result[DRI::NAME_MAIN_DOMAIN_NAME] = $topDomain;

        // check if domain name is too long
        if (strlen($topDomain) > static::MAX_LENGTH_BASE_DOMAIN_NAME) {
            throw new DomainNameTooLongException(
                sprintf(
                    'Domain name %s is too long',
                    $domainName
                ),
                E_WARNING,
                $domainName
            );
        }

        $result[DRI::NAME_MAIN_DOMAIN_NAME_ASCII] = $result[DRI::NAME_MAIN_DOMAIN_NAME_ASCII]
            ? $result[DRI::NAME_MAIN_DOMAIN_NAME_ASCII]
            : $this->tldCollector->encode($topDomain);

        if (! $isTopDomain) {
            $subDomain                               = implode('.', $domainArray);
            $result[DRI::NAME_SUB_DOMAIN_NAME_ASCII] = $this->tldCollector->encode($subDomain);
            $result[DRI::NAME_SUB_DOMAIN_NAME]       = $this->tldCollector->decode($subDomain);
        }

        $fullExtension = $result[DRI::NAME_SUB_EXTENSION];
        $fullExtension .= ($fullExtension ? '.' : '') . $result[DRI::NAME_BASE_EXTENSION];

        $fullExtensionASCII = $result[DRI::NAME_SUB_EXTENSION_ASCII];
        $fullExtensionASCII .= ($fullExtensionASCII ? '.' : '') . $result[DRI::NAME_BASE_EXTENSION_ASCII];

        $result[DRI::NAME_EXTENSION]       = $fullExtension;
        $result[DRI::NAME_EXTENSION_ASCII] = $fullExtensionASCII;

        $result[DRI::NAME_BASE_DOMAIN_NAME] = $result[DRI::NAME_SUB_DOMAIN_NAME];
        if (! empty($result[DRI::NAME_SUB_DOMAIN_NAME])) {
            $result[DRI::NAME_BASE_DOMAIN_NAME] .= ".";
        }
        $result[DRI::NAME_BASE_DOMAIN_NAME] .= $result[DRI::NAME_MAIN_DOMAIN_NAME];

        $result[DRI::NAME_BASE_DOMAIN_NAME_ASCII] = $result[DRI::NAME_SUB_DOMAIN_NAME_ASCII];
        if (! empty($result[DRI::NAME_SUB_DOMAIN_NAME_ASCII])) {
            $result[DRI::NAME_BASE_DOMAIN_NAME_ASCII] .= ".";
        }
        $result[DRI::NAME_BASE_DOMAIN_NAME_ASCII] .= $result[DRI::NAME_MAIN_DOMAIN_NAME_ASCII];
        $result[DRI::WHOIS_SERVER]                = (array)$this
            ->getTldCollector()
            ->getServersFromExtension(
                $result[DRI::NAME_BASE_EXTENSION]
            );
        $result[DRI::WHOIS_SERVER] =  array_map(function ($map) use ($result) {
            if (strpos($map, '{{domain}}')) {
                return str_replace('{{domain}}', $result[DRI::NAME_FULL_DOMAIN_NAME], $map);
            }
            return $map;
        }, $result[DRI::WHOIS_SERVER]);
        return $this->reValidateDomainName(new DomainRecord($result), $domainName);
    }

    /**
     * Throw Helper
     *
     * @param string $domainName
     *
     * @throws InvalidDomainException
     */
    protected function throwDomainNameHasInvalidCharacters(string $domainName)
    {
        throw new InvalidDomainException(
            sprintf(
                'Domain name %s is not valid that contains invalid characters',
                $domainName
            ),
            E_WARNING,
            $domainName
        );
    }

    /**
     * Re-validate domain name
     * override this method to make more advance domain validation
     *
     * @param DomainRecord $collector
     * @param string $domainName
     *
     * @return DomainRecord
     */
    protected function reValidateDomainName(DomainRecord $collector, string $domainName): DomainRecord
    {
        // remove dot and validate all domain name string
        $domainNameNoPeriods     = str_replace('.', '', $collector->getBaseDomainName());
        $mainDomainNameNoPeriods = str_replace('.', '', $collector->getMainDomainName());
        switch ($collector->getBaseExtension()) {
            case 'id':
                if (preg_match('/[^a-zA-Z0-9\-]/', $mainDomainNameNoPeriods)
                    || preg_match('/[^a-zA-Z0-9\-\_]/', $domainNameNoPeriods)
                ) {
                    $this->throwDomainNameHasInvalidCharacters($domainName);
                }
                break;
            default:
                if (preg_match(
                    '/
                            [^a-z0-9\-\P{Latin}\P{Hebrew}\P{Greek}\P{Cyrillic}
                            \P{Han}\P{Arabic}\P{Gujarati}\P{Armenian}\P{Hiragana}\P{Thai}]
                        /x',
                    $mainDomainNameNoPeriods
                ) || preg_match(
                    '/
                            [^a-z0-9\-\_\P{Latin}\P{Hebrew}\P{Greek}\P{Cyrillic}
                            \P{Han}\P{Arabic}\P{Gujarati}\P{Armenian}\P{Hiragana}\P{Thai}]
                        /x',
                    $domainNameNoPeriods
                )
                ) {
                    $this->throwDomainNameHasInvalidCharacters($domainName);
                }
                break;
        }

        // fallback result DomainRecord
        return $collector;
    }

    /**
     * Check if is valid Domain Name
     *
     * @param string $domainName
     *
     * @return bool
     */
    public function isValidDomain(string $domainName): bool
    {
        try {
            $this->splitDomainName($domainName);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if is Valid Top Level Domain
     *
     * @param string $domainName
     *
     * @return bool
     */
    public function isValidTopLevelDomain(string $domainName): bool
    {
        try {
            $domain = $this->splitDomainName($domainName);

            return $domain->isTopLevelDomain();
        } catch (\Exception $e) {
            return false;
        }
    }

    /* --------------------------------------------------------------------------------*
     |                              EMAIL VALIDATOR                                    |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Check if email is valid
     *
     * @param string $email email address
     * @param bool $allowIP allowed IP address as target
     * @param bool $checkMX by default check MX record from dns
     *
     * @return bool
     */
    public function isValidEmail(string $email, $allowIP = false, $checkMX = true): bool
    {
        $domain = $this->reValidateEmailAddress($email, $allowIP, $checkMX);
        if (is_bool($domain)) {
            return $domain;
        }

        return $this->isValidDomain($domain);
    }

    /**
     * Split Email into array collector if valid email
     *
     * @param string $email
     * @param bool $checkMX
     *
     * @return ArrayCollector
     */
    public function splitEmailDomain(string $email, $checkMX = true): ArrayCollector
    {
        $domain = $this->reValidateEmailAddress($email, true, $checkMX);
        if (! $domain) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is not a valid email',
                    $email
                ),
                E_WARNING
            );
        }

        $email   = strtolower($email);
        $explode = explode('@', $email);
        $host    = end($explode);
        if (is_string($domain)) {
            $host = $this->tldCollector->decode($host);
        }

        return new ArrayCollector([
            self::NAME_IS_IP => ! is_string($domain),
            self::NAME_EMAIL => reset($explode),
            self::NAME_HOST  => $host
        ]);
    }

    /**
     * Revalidate Email with returning domain Name
     *
     * @param string $email
     * @param bool $allowIP
     * @param bool $checkMX
     *
     * @return bool|string
     */
    protected function reValidateEmailAddress(string $email, $allowIP = false, $checkMX = true)
    {
        // minimum email length is 6 for minimum eg a@a.aa
        if (strlen(trim($email)) < 6
            // double periods is not a valid domain or IP or email address
            || strpos($email, '..') !== false
            // email only contain 1 @
            || substr_count($email, '@') <> 1
        ) {
            return false;
        }

        // invalid ipv6 email if ip does not allowed
        if (! $allowIP && (strpos($email, ':') || is_numeric(substr($email, -1)))) {
            return false;
        }

        // split into array
        $emailArray = explode('@', $email);
        // get email address
        $emailAddress = strtolower(array_shift($emailArray));
        // get domain
        $domain = implode('.', $emailArray);
        // it was must be invalid stop here
        if ($this->getBaseMailProviderValidator($emailAddress)->isMustBeInvalid()) {
            return false;
        }

        // local host & ip is not allowed
        if (preg_match(
            '/^(?:
                (?:127|192\.168|1?0|169\.254|172\.16)\.
                |(?:
                    (?:
                        localhost|\:\:[0-9a-f]+|.+\.(?:dev|local(?:domain))
                    )$
                )
            )/ix',
            $domain
        )) {
            return false;
        }

        // get domain Base Name only
        $domainName = strtolower(array_shift($emailArray));
        // split into array
        $domainNameArray = explode('.', $domainName);
        // get extension
        $extEnd = array_pop($domainNameArray);
        // base sub domain
        $baseOrSub = array_pop($domainNameArray);

        $isValidIp = false;
        // when extension is numeric so it was not domain name or maybe IP
        if (is_numeric($extEnd) ||
            // if contains dot with 4 count chars or contain : maybe is was IP
            substr_count($domainName, '.') == 4
            && substr($domainName, ':') > 1
        ) {
            if ($this->isLocalIP($domainName)) {
                return false;
            }

            $isValidIp = $this->isValidIP($domainName);
        }

        # does not allow IP as email base domain
        if ($isValidIp) {
            return $allowIP
                # if is allowed just check with validation domain placeholder
                ? filter_var("{$emailAddress}@example.com", FILTER_VALIDATE_EMAIL) != false
                : false;
        }

        if (! empty($domainNameArray)) {
            // check common mail provider
            if (in_array($baseOrSub, $this->commonEmailProvider)) {
                return false;
            }

            $commonProvider = ['hotmail', 'outlook', 'live', 'yahoo', 'rocketmail', 'ymail'];
            $baseDomain     = array_pop($domainNameArray);
            if ($baseOrSub == 'co') {
                $isCommon = in_array($baseDomain, $commonProvider);
                if ($isCommon && ! empty($domainNameArray)) {
                    return false;
                }
            }
        } elseif (in_array($baseOrSub, $this->commonEmailProvider)) {
            $isCommon   = true;
            $baseDomain = $baseOrSub;
        }

        if (is_numeric($extEnd) && isset($isCommon) && $isCommon && isset($baseDomain)) {
            $retVal = $this->isCommonMailValid($emailAddress, $baseDomain, $extEnd);
            if (is_bool($retVal)) {
                return $retVal;
            }
        }

        // validate email
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
             || ! $this->tldCollector->isExtensionExists($extEnd)
        ) {
            return false;
        }

        // if use Check MX just ignore domain check
        if ($checkMX) {
            return $this->isMXExists($domainName) ? $domain : false;
        }

        return $domain;
    }

    /**
     * Check Common Mail
     * Please use parent::isCommonMailValid() and then check your mail to child method
     * on child classes
     *
     * @param string $emailAddress email address
     * @param string $baseDomain base domain detect
     * @param string $ext base extension
     *
     * @return bool|null  returning null if has no check and bool if check true if valid otherwise false
     */
    protected function isCommonMailValid(string $emailAddress, string $baseDomain, string $ext = null)
    {
        $baseMailValidator = $this->getBaseMailProviderValidator($emailAddress);
        if ($baseMailValidator->isMustBeInvalid()) {
            return false;
        }

        // make base domain lower
        $baseDomain = strtolower($baseDomain);
        $ext        = $ext ? strtolower($ext) : '';
        switch ($baseDomain) {
            case 'gmail':
                return $baseMailValidator->isValidGMail();
            case 'hotmail':
            case 'outlook':
            case 'live':
                return $baseMailValidator->isValidMicrosoftMail();
            case 'yahoo':
            case 'rocketmail':
            case 'ymail':
            case 'aol':
                return $baseMailValidator->isValidYahooMail();
            case 'hushmail':
                // husmail support com & me only
                return $ext && in_array($ext, ['com', 'me'])
                    ? $baseMailValidator->isValidMailComMail()
                    : null;
            case 'hush':
                // husmail with hush domain support com & ai only
                return $ext && in_array($ext, ['com', 'ai'])
                    ? $baseMailValidator->isValidMailComMail()
                    : null;
            case 'null':
            case 'programmer':
                return $ext == 'net'
                    ? $baseMailValidator->isValidMailComMail()
                    : null;
            case 'email':
            case 'hackermail':
            case 'mail':
            case 'inbox':
                return $ext == 'com'
                    ? $baseMailValidator->isValidMailComMail()
                    : null;
            case 'gmx':
                return $baseMailValidator->isValidMailComMail();
            case 'yandex':
                return $baseMailValidator->isValidYanDexMail();
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
    public function isMXExists(string $domainName): bool
    {
        if (! $this->isLocalIP($domainName) # don't allow local IP
             && getmxrr($domainName, $mx)
        ) {
            return ! empty($mx);
        }

        return false;
    }

    /* --------------------------------------------------------------------------------*
     |                                IP VALIDATOR                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @param string $ip
     *
     * @return IPRecord
     */
    public function splitIP(string $ip): IPRecord
    {
        $ipAddress = strtolower(trim($ip));
        if ($ipAddress == '') {
            throw new \InvalidArgumentException(
                'IP Address could not be empty or whitespace only.',
                E_WARNING
            );
        }
        if (! $this->isValidIP($ip)) {
            if (strpos($ip, '.') === false) {
                throw new InvalidDomainException(
                    sprintf(
                        'IP address : %s is not valid',
                        $ip
                    ),
                    E_WARNING,
                    $ip
                );
            }
        }

        $isIpv6  = $this->isIPv6($ipAddress);
        $isLocal = $this->isLocalIP($ipAddress);
        $servers = [DataParser::URI_IANA_WHOIS];
        $path    = !$isIpv6
            ? DataParser::PATH_IP4_BLOCKS
            : DataParser::PATH_IP6_BLOCKS;
        $isReserved  = false;
        $server      = '';
        $found = '';
        foreach (file(
            $path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        ) as $value) {
            $value = trim($value);
            if (! $value || $value[0] == '#') {
                continue;
            }
            if ($value[0] === '[') {
                $server = trim(trim($value, '[]'));
                continue;
            }

            if (!$server) {
                continue;
            }
            if (!$isIpv6) {
                $value = explode('/', $value);
                $value = reset($value);
                if ($value[0] === '0') {
                    $value = substr($value, 1);
                }
                if (strpos($ip, "{$value}.") === 0) {
                    $found = $server;
                    break;
                }
                continue;
            }

            if ($this->isIPv6OnRange($ipAddress, $value)) {
                $found = $server;
                break;
            }
        }

        $server = $found;
        $isPrivate   = $server === DataParser::RESERVED_PRIVATE;
        $future      = $server === DataParser::RESERVED_FUTURE;
        $isReserved  = $isPrivate || $future || $isLocal ? true : $isReserved;
        if ($server && ! $isPrivate && strpos($server, '.') !== false) {
            $servers = array_values(array_unique(array_merge([$server], $servers)));
        }

        return new IPRecord([
            RNI::NAME_IP_ADDRESS  => $ipAddress,
            RNI::NAME_IS_LOCAL_IP => $isLocal || $isPrivate,
            RNI::NAME_IS_RESERVED => $isReserved,
            RNI::NAME_IS_RESERVED_PRIVATE => $isReserved && $isPrivate,
            RNI::NAME_IS_RESERVED_FUTURE => $isReserved && $future,
            RNI::NAME_IS_IPV4     => ! $isIpv6,
            RNI::NAME_IS_IPV6     => $isIpv6,
            RNI::WHOIS_SERVER     => $servers,
        ]);
    }

    /**
     * Check Whether IPv6 is On Range
     * @access protected
     *
     * @param string $ip
     * @param string $range
     *
     * @return bool
     */
    protected function isIPv6OnRange(string $ip, string $range) : bool
    {
        if (strpos($range, '/') === false) {
            $range .= '/32';
        }

        list($net, $maskBits) = explode('/', $range);

        $ip = inet_pton($ip);
        $maskBits    = (int) $maskBits;
        $binaryIP    = $this->iNet2Bits($ip);
        $binaryNet   = $this->iNet2Bits(inet_pton($net));
        $ip_net_bits = substr($binaryIP, 0, $maskBits);
        $net_bits    = substr($binaryNet, 0, $maskBits);

        return $ip_net_bits === $net_bits;
    }

    /**
     * Convert Inet pyton to bits
     * @param string $net
     *
     * @return string
     */
    protected function iNet2Bits(string $net) : string
    {
        $unpacked = unpack('A16', $net);
        $unpacked = str_split($unpacked[1]);
        $BinaryIp = '';
        foreach ($unpacked as $char) {
            $BinaryIp .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        return $BinaryIp;
    }

    /**
     * Check if value valid IP
     *
     * @param string $ip
     *
     * @return bool
     */
    public function isValidIP(string $ip): bool
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
    public function isIPv4(string $ipv4): bool
    {
        if (trim($ipv4) === '') {
            return false;
        }
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
    public function isIPv6(string $ipv6): bool
    {
        if (trim($ipv6) === '') {
            return false;
        }
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
    public function isLocalIPv4(string $ipv4): bool
    {
        if (trim($ipv4) === '') {
            return false;
        }

        # no range allowed maybe used / for range
        return preg_match('/(127|1?0|192\.168|169\.254|172\.16)\.|[^0-9\.]/', $ipv4)
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
    public function isLocalIPv6(string $ipv6): bool
    {
        # no range allowed maybe used / for range
        return $this->isIPv6($ipv6)
               && ($ipv6 === '::1' # this is local IP
                   || ! filter_var(
                       $ipv6,
                       FILTER_VALIDATE_IP,
                       FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                   )
               );
    }

    /* --------------------------------------------------------------------------------*
     |                               ASN VALIDATOR                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Check if is valid asn
     *
     * @param string $asn
     *
     * @return bool
     */
    public function isValidASN(string $asn): bool
    {
        $asn = trim($asn);
        if ($asn == '') {
            return false;
        }

        return preg_match(DataParser::ASN_REGEX, $asn, $match) && ! empty($match[2]);
    }

    /**
     * Split asn into Record
     *
     * @param string $asn
     *
     * @return ASNRecord
     */
    public function splitASN(string $asn) : ASNRecord
    {
        $asn = strtolower(trim($asn));
        if ($asn == '') {
            throw new \InvalidArgumentException(
                'ASN could not be empty or whitespace only.',
                E_WARNING
            );
        }

        if (!preg_match(DataParser::ASN_REGEX, $asn, $match) || empty($match[2])) {
            throw new InvalidDomainException(
                sprintf(
                    'Autonomous System Number %s is not valid',
                    $asn
                ),
                E_WARNING,
                $asn
            );
        }

        $match = abs($match[2]);
        $is32Bit = $match > DataParser::ASN16_MAX_RANGE;
        $isReserved  = false;
        $server      = '';
        $servers = [DataParser::URI_IANA_WHOIS];
        $found = '';
        if (in_array($match, [
            DataParser::ASN16_MAX_RANGE,
            DataParser::ASN16_MIN_RANGE,
            DataParser::ASN32_MIN_RANGE,
            DataParser::ASN32_MIN_RANGE,
        ])) {
            $isReserved = DataParser::RESERVED;
        } else {
            $path    = $is32Bit
                ? DataParser::PATH_AS32_DEL_BLOCKS
                : DataParser::PATH_AS16_DEL_BLOCKS;

            foreach (file(
                $path,
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            ) as $value) {
                $value = trim($value);
                if (! $value || $value[0] == '#') {
                    continue;
                }
                if ($value[0] === '[') {
                    $server = trim(trim($value, '[]'));
                    continue;
                }
                if (!$server) {
                    continue;
                }

                if (strpos($value, '-')) {
                    $explode = array_map('trim', explode('-', $value));
                    if (count($explode) > 2) {
                        continue;
                    }
                    $explode = array_values(array_filter($explode, 'is_numeric'));
                    if (count($explode) === 0) {
                        continue;
                    }
                    if (count($explode) === 2) {
                        $first = abs(reset($explode));
                        $last  = abs(next($explode));
                        if ($first > $last) {
                            continue;
                        }
                        if ($match >= $first && $match <= $last) {
                            $found = $server;
                            break;
                        }
                        continue;
                    } elseif (abs(reset($explode)) === $match) {
                        $found = $server;
                        break;
                    }
                    continue;
                }
                if (abs($value) === $match) {
                    $found = $server;
                    break;
                }
            }
        }
        $server = $found;
        $unAllocated = ! $isReserved && $server === DataParser::UNALLOCATED ;
        $private     = $server === DataParser::RESERVED_PRIVATE;
        $sample      = $server === DataParser::RESERVED_SAMPLE;
        $isReserved  = $private || $sample ? true : $isReserved;
        if ($server && !$isReserved && !$unAllocated) {
            $servers = array_unique(array_merge([$server], $servers));
        }

        return new ASNRecord([
            RAN::NAME_ASN_ADDRESS   => $match,
            RAN::NAME_ASN_16        => !$is32Bit,
            RAN::NAME_ASN_32        => $is32Bit,
            RAN::NAME_ASN_TYPE => $is32Bit ? RAN::NAME_ASN_32 : RAN::NAME_ASN_16,
            RAN::NAME_IS_UNALLOCATED      => $unAllocated,
            RAN::NAME_IS_RESERVED         => $isReserved,
            RAN::NAME_IS_RESERVED_SAMPLE  => $isReserved && $sample,
            RAN::NAME_IS_RESERVED_PRIVATE => $isReserved && $private,
            RAN::WHOIS_SERVER             => $servers
        ]);
    }
}
