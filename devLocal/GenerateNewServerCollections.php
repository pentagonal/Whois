#!/usr/bin/env php
<?php
if (php_sapi_name() != 'cli') {
    exit('Script must be run as CLI');
}

require __DIR__ .'/../vendor/autoload.php';

use Pentagonal\WhoIs\Util\DataGenerator;

echo "Starting Generate Whois & Server List data\n";
$array = DataGenerator::generateDefaultExtensionServerList();
$count = count($array);
$totalSubDomain = 0;
array_filter($array, function ($m) use(&$totalSubDomain) {
    if (count($m) > 0) {
        $totalSubDomain += count($m);
        return true;
    }
    return false;
});
echo "Successfully Generate [{$count}] TLD Extensions and [{$totalSubDomain}] Sub TLD.\n\n";
