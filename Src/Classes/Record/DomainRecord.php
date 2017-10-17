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

namespace Pentagonal\WhoIs\Record;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\App\TLDCollector;
use Pentagonal\WhoIs\Interfaces\RecordDomainNetworkInterface;

/**
 * Class DomainRecord
 * @package Pentagonal\WhoIs\Record
 */
class DomainRecord extends ArrayCollector implements RecordDomainNetworkInterface
{
    /**
     * List of sub extension that disallow to Register
     * @var array
     */
    protected $subExtensionDisallowRegister = [
        'blogspot',
        'amazonaws',
        'blogspot.com',
        'barsy',
        'cloudns',
        'dydns',
        'cupcake',
    ];

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getPointer() : string
    {
        return $this->getDomainName();
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function getWhoIsServers(): array
    {
        return (array) $this[self::WHOIS_SERVER];
    }

    /**
     * Check if Country Code Top Level Domain extension
     *
     * @return bool
     */
    public function isCCTLDExtension() : bool
    {
        static $collector;
        if (!isset($collector)) {
            $collector = new TLDCollector();
        }
        return in_array($this->getBaseExtension(), $collector->getCountryExtensionList());
    }

    /**
     * Check if is Do Not Allow To registered
     *
     * @return bool
     */
    public function isMaybeDisAllowToRegistered() : bool
    {
        if (!$this->isTopLevelDomain()) {
            return false;
        }

        if ($this->isGTLD()) {
            return true;
        }
        $sub = $this->getSubExtension();
        if (in_array($sub, $this->subExtensionDisallowRegister)
            // sub tld only allow for country domain
            || ! $this->isCCTLDExtension()
            // amazon, contain dns name or elasticbeanstalk is not allowed
            || preg_match('~amazon|elasticbeanstalk|[a-z]+dns~i', $sub)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if domain is top level Domain
     *
     * @return bool
     */
    public function isTopLevelDomain() : bool
    {
        return $this[self::NAME_IS_TOP_DOMAIN] === true;
    }

    /**
     * Check if Generic Top Level Domain
     *
     * @return bool
     */
    public function isGTLD() : bool
    {
        return $this[self::NAME_IS_GTLD_DOMAIN] === true;
    }

    /**
     * Check if Second Top Level Domain
     *
     * @return bool
     */
    public function isSTLD() : bool
    {
        return $this[self::NAME_IS_STLD_DOMAIN] === true;
    }

    /**
     * Check if sub domain
     *
     * @return bool
     */
    public function isSubDomain() : bool
    {
        return $this[self::NAME_IS_SUB_DOMAIN] === true;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDomainName() : string
    {
        return $this[self::NAME_FULL_DOMAIN_NAME];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDomainNameAscii() : string
    {
        return $this[self::NAME_FULL_DOMAIN_NAME_ASCII];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtension() : string
    {
        return $this[self::NAME_EXTENSION];
    }

    /**
     * Get Base extension encoded ASCII
     *
     * @return string
     */
    public function getBaseExtensionAscii()
    {
        return $this[self::NAME_BASE_EXTENSION_ASCII];
    }

    /**
     * Get Domain Name
     *
     * @return string
     */
    public function getFullDomainName()
    {
        return $this->getDomainName();
    }

    /**
     * Get Domain Name encoded ASCII
     *
     * @return string
     */
    public function getFullDomainNameAscii()
    {
        return $this->getDomainNameAscii();
    }

    /**
     * Get Base extension
     *
     * @return string
     */
    public function getBaseExtension()
    {
        return $this[self::NAME_BASE_EXTENSION];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtensionAscii() : string
    {
        return $this[self::NAME_EXTENSION];
    }

    /**
     * Get Sub extension if exist
     *
     * @return string
     */
    public function getSubExtension()
    {
        return $this[self::NAME_SUB_EXTENSION];
    }

    /**
     * Get Sub extension if exist encoded ASCII
     *
     * @return string
     */
    public function getSubExtensionAscii()
    {
        return $this[self::NAME_SUB_EXTENSION_ASCII];
    }

    /**
     * Get Base Main Domain Name
     *
     * @return string
     */
    public function getMainDomainName()
    {
        return $this[self::NAME_MAIN_DOMAIN_NAME];
    }

    /**
     * Get Base Main Domain Name
     *
     * @return string
     */
    public function getMainDomainNameAscii()
    {
        return $this[self::NAME_MAIN_DOMAIN_NAME_ASCII];
    }

    /**
     * Get domain name without extension
     *
     * @return string
     */
    public function getBaseDomainName()
    {
        return $this[self::NAME_BASE_DOMAIN_NAME];
    }

    /**
     * Get domain name without extension encoded ASCII
     *
     * @return string
     */
    public function getBaseDomainNameAscii()
    {
        return $this[self::NAME_BASE_DOMAIN_NAME_ASCII];
    }

    /**
     * Get sub domain name without extension and main domain name
     *
     * @return string
     */
    public function getSubDomainName()
    {
        return $this[self::NAME_SUB_DOMAIN_NAME];
    }

    /**
     * Get sub domain name without extension and main domain name encoded ASCII
     *
     * @return string
     */
    public function getSubDomainNameAscii()
    {
        return $this[self::NAME_SUB_DOMAIN_NAME_ASCII];
    }
}
