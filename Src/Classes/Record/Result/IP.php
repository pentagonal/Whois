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

namespace Pentagonal\WhoIs\Record\Result;

use Pentagonal\WhoIs\Abstracts\RecordResultAbstract;
use Pentagonal\WhoIs\Abstracts\WhoIsResultAbstract;
use Pentagonal\WhoIs\App\ArrayCollector;

/**
 * Class IP
 * @package Pentagonal\WhoIs\Record\Result
 * @todo Parsing Process
 */
class IP extends RecordResultAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function initGenerateRecord(WhoIsResultAbstract $result) : ArrayCollector
    {
        return $this->parseIPDetail($result);
    }

    /* --------------------------------------------------------------------------------*
     |                                   UTILITY                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Parse data for result
     *
     * @param WhoIsResultAbstract $result
     *
     * @return ArrayCollector
     */
    protected function parseIPDetail(WhoIsResultAbstract $result) : ArrayCollector
    {
        return new ArrayCollector();
    }
}
