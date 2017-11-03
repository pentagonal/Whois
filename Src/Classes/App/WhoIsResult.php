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

use Pentagonal\WhoIs\Abstracts\WhoIsResultAbstract;
use Pentagonal\WhoIs\Interfaces\RecordNetworkInterface;

/**
 * Class WhoIsResult
 * @package Pentagonal\WhoIs\App
 * By default on string result is parse on JSON
 * @todo Completion Detail & Methods
 */
class WhoIsResult extends WhoIsResultAbstract
{
    /* --------------------------------------------------------------------------------*
     |                                  INSTANCE                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Create Whois Result Instance
     *
     * @param RecordNetworkInterface $network
     * @param string $originalData
     * @param string $server
     *
     * @return WhoIsResult
     */
    public static function createInstance(
        RecordNetworkInterface $network,
        string $originalData,
        string $server = null
    ) : WhoIsResult {
        return new static($network, $originalData, $server);
    }
}
