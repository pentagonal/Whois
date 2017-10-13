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

use Pentagonal\WhoIs\Interfaces\DomainRecordInterface;

/**
 * Class DomainRecord
 * @package Pentagonal\WhoIs\App
 */
class DomainRecord extends ArrayCollector implements DomainRecordInterface
{
    /**
     * Check if domain is top level Domain
     *
     * @return bool
     */
    public function isTopLevelDomain() : bool
    {
        return $this[Validator::NAME_IS_TOP_DOMAIN] === true;
    }

    /**
     * Check if Generic Top Level Domain
     *
     * @return bool
     */
    public function isGTLD() : bool
    {
        return $this[Validator::NAME_IS_GTLD_DOMAIN] === true;
    }

    /**
     * Check if Second Top Level Domain
     *
     * @return bool
     */
    public function isSTLD() : bool
    {
        return $this[Validator::NAME_IS_STLD_DOMAIN] === true;
    }

    /**
     * Check if sub domain
     *
     * @return bool
     */
    public function isSubDomain() : bool
    {
        return $this[Validator::NAME_IS_SUB_DOMAIN] === true;
    }

    /**
     * Get Domain Name
     *
     * @return string
     */
    public function getDomainName()
    {
        return $this[Validator::NAME_FULL_DOMAIN_NAME];
    }

    /**
     * Get Domain Name encoded ASCII
     *
     * @return string
     */
    public function getDomainNameAscii()
    {
        return $this[Validator::NAME_FULL_DOMAIN_NAME_ASCII];
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
        return $this[Validator::NAME_BASE_EXTENSION];
    }

    /**
     * Get Base extension encoded ASCII
     *
     * @return string
     */
    public function getBaseExtensionAscii()
    {
        return $this[Validator::NAME_BASE_EXTENSION_ASCII];
    }

    /**
     * Get Extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this[Validator::NAME_EXTENSION];
    }

    /**
     * Get Extension encoded ASCII
     *
     * @return string
     */
    public function getExtensionAscii()
    {
        return $this[Validator::NAME_EXTENSION];
    }

    /**
     * Get Sub extension if exist
     *
     * @return string
     */
    public function getSubExtension()
    {
        return $this[Validator::NAME_SUB_EXTENSION];
    }

    /**
     * Get Sub extension if exist encoded ASCII
     *
     * @return string
     */
    public function getSubExtensionAscii()
    {
        return $this[Validator::NAME_SUB_EXTENSION_ASCII];
    }

    /**
     * Get Base Main Domain Name
     *
     * @return string
     */
    public function getMainDomainName()
    {
        return $this[Validator::NAME_MAIN_DOMAIN_NAME];
    }

    /**
     * Get Base Main Domain Name
     *
     * @return string
     */
    public function getMainDomainNameAscii()
    {
        return $this[Validator::NAME_MAIN_DOMAIN_NAME_ASCII];
    }

    /**
     * Get domain name without extension
     *
     * @return string
     */
    public function getBaseDomainName()
    {
        return $this[Validator::NAME_BASE_DOMAIN_NAME];
    }

    /**
     * Get domain name without extension encoded ASCII
     *
     * @return string
     */
    public function getBaseDomainNameAscii()
    {
        return $this[Validator::NAME_BASE_DOMAIN_NAME_ASCII];
    }

    /**
     * Get sub domain name without extension and main domain name
     *
     * @return string
     */
    public function getSubDomainName()
    {
        return $this[Validator::NAME_SUB_DOMAIN_NAME];
    }

    /**
     * Get sub domain name without extension and main domain name encoded ASCII
     *
     * @return string
     */
    public function getSubDomainNameAscii()
    {
        return $this[Validator::NAME_SUB_DOMAIN_NAME_ASCII];
    }
}
