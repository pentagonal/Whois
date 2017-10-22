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
 * Class WhoIsMultiResult
 * @package Pentagonal\WhoIs\App
 */
class WhoIsMultiResult extends ArrayCollector
{
    /**
     * WhoIsMultiResult constructor.
     *
     * @param WhoIsResult[] $input
     */
    public function __construct(array $input)
    {
        if (empty($input)) {
            throw new \InvalidArgumentException(
                'Whois result collection could not be empty array',
                E_WARNING
            );
        }
        foreach ($input as $server => $whoIsRequest) {
            if (!is_string($server)) {
                throw new \InvalidArgumentException(
                    'Input whois result key name must be as a string server',
                    E_WARNING
                );
            }
            if (!$whoIsRequest instanceof WhoIsResult) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Array of collection must be contains value instance of: %s',
                        WhoIsResult::class
                    ),
                    E_WARNING
                );
            }
        }

        parent::__construct($input);
    }
}
