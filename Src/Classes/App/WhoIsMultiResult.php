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
    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * WhoIsMultiResult constructor.
     *
     * @param WhoIsResult[]|\Throwable $input
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
            // only allow throwable and WhoIsResult only
            if (!$whoIsRequest instanceof WhoIsResult
                && ! $whoIsRequest instanceof \Throwable
                && ! $whoIsRequest instanceof WhoIsMultiResult
            ) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Array of collection must be contains value instance of: %1$s or %2$s',
                        WhoIsResult::class,
                        \Throwable::class
                    ),
                    E_WARNING
                );
            }

            $this[$server] = $whoIsRequest;
        }
    }
}
