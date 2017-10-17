#!/usr/bin/env php
<?php
if (php_sapi_name() != 'cli') {
    exit('Script must be run as CLI');
}

$last   = 0;
$fileToWrite = __DIR__ . '/build/list_asn_range.txt';
if (!is_dir(dirname($fileToWrite))) {
  if (!@mkdir(dirname($fileToWrite))) {
      exit('Permission denied to create directory : '. dirname($fileToWrite));
  }
}
if (@file_put_contents($fileToWrite, '') === false) {
    exit('Can not write to : '. $fileToWrite);
}

while (true) {
    if ($last === null) {
        break;
    }

    $last++;
    // verbose
    echo "Checking from [{$last}]";
    sleep(3);
    $socket = @fsockopen('whois.iana.org', 43);
    if (!$socket) {
        break;
    }
    fputs($socket, "{$last}\r\n");
    $data = '';
    while(!feof($socket)) {
        $data .= fread($socket, 4096);
    }
    fclose($socket);
    unset($socket);
    preg_match_all('~
        as-block:\s*(?P<block>[^\n]+)
        | whois\:\s*(?P<whois>[^\n]+)
        ~xi',
        $data,
        $match
    );
    if (empty($match['whois'])) {
        break;
    }
    $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
    // make 2D array as sorted integer start with 0 if not empty
    $match = array_map(
        'array_values',
        // filter empty value
        array_map(
            function ($v) {
                return array_filter(array_map('trim', $v));
            },
            $match
        )
    );
    $whois = reset($match['whois']);
    $block = reset($match['block']);
    $whois = strtolower($whois);
    if (empty($whois)) {
        break;
    }
    $blockArray = array_filter(array_map('trim', explode('-', $block)));
    $blockArray = array_values($blockArray);
    if (empty($blockArray)) {
        break;
    }
    $last = end($blockArray);
    $content = "{$whois} : {$block}\r\n";
    echo " ---> {$content}";
    if (!@file_put_contents($fileToWrite, $content, FILE_APPEND)) {
        break;
    }
    sleep(1);
}
