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

namespace Pentagonal\WhoIs\Abstracts;

use Pentagonal\WhoIs\App\ArrayCollector;

/**
 * Class RecordResultAbstract
 * @package Pentagonal\WhoIs\Abstracts
 */
abstract class RecordResultAbstract extends ArrayCollector
{
    /**
     * From Result Create
     *
     * @param WhoIsResultAbstract $result
     *
     * @return RecordResultAbstract
     */
    public static function fromResult(WhoIsResultAbstract $result) : RecordResultAbstract
    {
        $object = new static();
        // merge result
        $object->merge($object->initGenerateRecord($result)->toArray());
        return $object;
    }

    /**
     * Generate for detail
     *
     * @param WhoIsResultAbstract $result
     *
     * @return ArrayCollector
     */
    abstract protected function initGenerateRecord(WhoIsResultAbstract $result): ArrayCollector;
}
