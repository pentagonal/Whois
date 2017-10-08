<?php
/**
 * List Class Map
 */
$sourceDir = __DIR__ . DIRECTORY_SEPARATOR . 'Src' . DIRECTORY_SEPARATOR;
return [
    'Pentagonal\WhoIs\ArrayCache' => $sourceDir . 'ArrayCache.php',
    'Pentagonal\WhoIs\ServerList' => $sourceDir . 'ServerList.php',
    'Pentagonal\WhoIs\Verifier'   => $sourceDir . 'Verifier.php',
    'Pentagonal\WhoIs\WhoIs'      => $sourceDir . 'Whois.php',
    'Pentagonal\WhoIs\Exceptions\ConnectionException' => $sourceDir . 'Exceptions' . DIRECTORY_SEPARATOR . 'ConnectionException.php',
    'Pentagonal\WhoIs\Exceptions\ConnectionRefuseException' => $sourceDir . 'Exceptions' . DIRECTORY_SEPARATOR . 'ConnectionRefuseException.php',
    'Pentagonal\WhoIs\Exceptions\HttpBadAddressException' => $sourceDir . 'Exceptions' . DIRECTORY_SEPARATOR . 'HttpBadAddressException.php',
    'Pentagonal\WhoIs\Exceptions\HttpException' => $sourceDir . 'Exceptions' . DIRECTORY_SEPARATOR . 'HttpException.php',
    'Pentagonal\WhoIs\Exceptions\HttpExpiredException' => $sourceDir . 'Exceptions' . DIRECTORY_SEPARATOR . 'HttpExpiredException.php',
    'Pentagonal\WhoIs\Exceptions\HttpPermissionException' => $sourceDir . 'Exceptions' . DIRECTORY_SEPARATOR . 'HttpPermissionException.php',
    'Pentagonal\WhoIs\Exceptions\TimeOutException' => $sourceDir . 'Exceptions' . DIRECTORY_SEPARATOR . 'TimeOutException.php',
    'Pentagonal\WhoIs\Interfaces\CacheInterface' => $sourceDir . 'Interfaces' . DIRECTORY_SEPARATOR . 'CacheInterface.php',
    'Pentagonal\WhoIs\Util\Collection' => $sourceDir . 'Util' . DIRECTORY_SEPARATOR . 'Collection.php',
    'Pentagonal\WhoIs\Util\DataGetter' => $sourceDir . 'Util' . DIRECTORY_SEPARATOR . 'DataGetter.php',
    'Pentagonal\WhoIs\Util\ExtensionStorage' => $sourceDir . 'Util' . DIRECTORY_SEPARATOR . 'ExtensionStorage.php',
    'Pentagonal\WhoIs\Util\Stream' => $sourceDir . 'Util' . DIRECTORY_SEPARATOR . 'Stream.php',
    'Pentagonal\WhoIs\Util\StreamSocketTransport' => $sourceDir . 'Util' . DIRECTORY_SEPARATOR . 'StreamSocketTransport.php',
    'Pentagonal\WhoIs\Util\Uri' => $sourceDir . 'Util' . DIRECTORY_SEPARATOR . 'Uri.php',
];
