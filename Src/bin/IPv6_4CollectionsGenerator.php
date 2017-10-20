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
echo "    Starting Generate IP Range List data\n";
echo "==============================================\n";

$countArray = DataGenerator::generateIPv64FileData();
if (!is_array($countArray)) {
    echo "There was an error\n";
    exit(255);
}

echo <<<BLOCK
Successfully Generate total [{$countArray['total']}] IP Data.

IPv4 : {$countArray['ipv4']} IP Range
IPv6 : {$countArray['ipv6']} IP Range
\n
BLOCK;

echo "==============================================\n\n";
