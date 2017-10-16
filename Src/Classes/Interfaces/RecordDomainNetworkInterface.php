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

namespace Pentagonal\WhoIs\Interfaces;

/**
 * Interface RecordDomainNetworkInterface
 */
interface RecordDomainNetworkInterface extends RecordNetworkInterface
{
    const NAME_IS_TOP_DOMAIN    = 'IS_TOP_DOMAIN';
    const NAME_IS_GTLD_DOMAIN   = 'IS_GLTD_DOMAIN';
    const NAME_IS_STLD_DOMAIN   = 'IS_STLD_DOMAIN';

    const NAME_IS_SUB_DOMAIN    = 'IS_SUB_DOMAIN';

    const NAME_BASE_EXTENSION        = 'BASE_EXTENSION';
    const NAME_BASE_EXTENSION_ASCII  = 'BASE_EXTENSION_ASCII';

    const NAME_EXTENSION        = 'EXTENSION';
    const NAME_EXTENSION_ASCII  = 'EXTENSION_ASCII';

    const NAME_SUB_EXTENSION          = 'SUB_EXTENSION';
    const NAME_SUB_EXTENSION_ASCII    = 'SUB_EXTENSION_ASCII';

    const NAME_FULL_DOMAIN_NAME       = 'FULL_DOMAIN_NAME';
    const NAME_FULL_DOMAIN_NAME_ASCII = 'FULL_DOMAIN_NAME_ASCII';

    const NAME_MAIN_DOMAIN_NAME       = 'MAIN_DOMAIN_NAME';
    const NAME_MAIN_DOMAIN_NAME_ASCII = 'MAIN_DOMAIN_NAME_ASCII';

    const NAME_SUB_DOMAIN_NAME        = 'SUB_DOMAIN_NAME';
    const NAME_SUB_DOMAIN_NAME_ASCII  = 'SUB_DOMAIN_NAME_ASCII';

    const NAME_BASE_DOMAIN_NAME        = 'BASE_DOMAIN_NAME';
    const NAME_BASE_DOMAIN_NAME_ASCII  = 'BASE_DOMAIN_NAME_ASCII';

    /**
     * Get Domain Name
     *
     * @return string
     */
    public function getDomainName() : string;

    /**
     * Get Domain Name with encoded in ASCII
     *
     * @return string
     */
    public function getDomainNameAscii() : string;

    /**
     * Get Extension from given Domain Name
     *
     * @return string
     */
    public function getExtension() : string;

    /**
     * Get Extension from given Domain Name encoded ASCII
     *
     * @return string
     */
    public function getExtensionASCII() : string;
}
