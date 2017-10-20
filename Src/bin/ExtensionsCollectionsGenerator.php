#!/usr/bin/env php
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

if (php_sapi_name() != 'cli') {
    exit('Script must be run as CLI');
}

require __DIR__ .'/../../vendor/autoload.php';

use Pentagonal\WhoIs\Util\DataGenerator;

echo "==============================================\n";
echo "  Starting Generate Whois & Server List data\n";
echo "==============================================\n";

try {
    $array          = DataGenerator::generateDefaultExtensionServerList();
    $count          = count($array);
    $totalSubDomain = 0;
    array_filter($array, function ($m) use (&$totalSubDomain) {
        if (count($m) > 0) {
            $totalSubDomain += count($m);

            return true;
        }

        return false;
    });

    echo <<<BLOCK
Successfully Generate [{$count}] TLD Extensions.

TLD Extensions : {$count} Extensions List
STLD Extensions: {$totalSubDomain} Extensions List
\n
BLOCK;
} catch (\Exception $e) {
    echo "[ERROR] {$e->getMessage()}\n";
}

echo "==============================================\n\n";
