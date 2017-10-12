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

namespace Pentagonal\WhoIs\Util;

use GuzzleHttp\Psr7\Stream;
use Pentagonal\WhoIs\App\TLDCollector;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Handler\TransportClient as Transport;

/**
 * Class DataGenerator
 * @package Pentagonal\WhoIs\Util
 */
final class DataGenerator
{
    const ERRR_DENIED = -1;
    const PORT_WHOIS  = 43;

    // Uri
    const
        URI_IANA_WHOIS = 'whois.iana.org',
        URI_IANA_IDN   = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt',
        URI_PUBLIC_SUFFIX = 'https://publicsuffix.org/list/effective_tld_names.dat',
        URI_CACERT     = 'https://curl.haxx.se/ca/cacert.pem';

    // Path
    const
        PATH_WHOIS_SERVERS = __DIR__ . '/../Data/Servers/AvailableServers.php',
        PATH_EXTENSIONS_AVAILABLE = __DIR__ . '/../Data/Servers/AvailableServers.php',
        PATH_CACERT     = __DIR__ . '/../Data/Certs/cacert.pem';

    const
        ARIN_NET_PREFIX_COMMAND    = 'n +',
        RIPE_NET_PREFIX_COMMAND    = '-V Md5.2',
        APNIC_NET_PREFIX_COMMAND   = '-V Md5.2',
        AFRINIC_NET_PREFIX_COMMAND = '-V Md5.2',
        LACNIC_NET_PREFIX_COMMAND  = '';

    /**
     * @var array
     */
    protected static $serverPrefixList = [
        'whois.arin.net'    => self::ARIN_NET_PREFIX_COMMAND,
        'whois.ripe.net'    => self::RIPE_NET_PREFIX_COMMAND,
        'whois.apnic.net'   => self::APNIC_NET_PREFIX_COMMAND,
        'whois.afrinic.net' => self::AFRINIC_NET_PREFIX_COMMAND,
        'whois.lacnic.net'  => self::LACNIC_NET_PREFIX_COMMAND,
    ];

    const LICENSE = <<<LICENSE
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
LICENSE;

    final public static function generateDefaultExtensionServerList()
    {
        $collector = new TLDCollector();
        $fileTLDExtensions = $collector->getAvailableExtensionsFile();
        $fileServers = $collector->getAvailableServersFile();
        $dirFileTLDExtensions = dirname($fileTLDExtensions);
        $dirFileServers = dirname($fileServers);
        if (!file_exists($fileTLDExtensions) && !is_writeable($dirFileTLDExtensions)
            || !file_exists($fileServers) && !is_writeable($dirFileServers)
        ) {
            return self::ERRR_DENIED;
        }

        if (!file_exists($fileTLDExtensions)) {
            if (!touch($fileTLDExtensions)) {
                return self::ERRR_DENIED;
            }
        }

        if (!file_exists($fileServers)) {
            if (!touch($fileServers)) {
                return self::ERRR_DENIED;
            }
        }

        if (!is_writeable($fileTLDExtensions) || !is_writeable($fileServers)) {
            return self::ERRR_DENIED;
        }

        try {
            $iAnaResponse = Transport::get(self::URI_IANA_IDN);
        } catch (TimeOutException $e) {
            $iAnaResponse = Transport::get(self::URI_IANA_IDN);
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            $suffixResponse = Transport::get(self::URI_PUBLIC_SUFFIX);
        } catch (TimeOutException $e) {
            $suffixResponse = Transport::get(self::URI_PUBLIC_SUFFIX);
        } catch (\Exception $e) {
            throw $e;
        }

        $dataResponse = '';
        $body = $iAnaResponse->getBody();
        while (!$body->eof()) {
            $dataResponse .= $body->read(4096);
        }
        $body->close();

        // iana
        $iAna = DataParser::cleanIniComment($dataResponse);
        $iAna = DataParser::cleanMultipleWhiteSpaceTrim($iAna);
        $iAna = str_replace("\r\n", "\n", strtolower($iAna));
        $iAna = explode("\n", $iAna);
        $iAna = array_filter($iAna);

        $dataResponse = '';
        $body = $suffixResponse->getBody();
        while (!$body->eof()) {
            $dataResponse .= $body->read(4096);
        }
        $body->close();

        // suffix
        $suffix = DataParser::cleanIniComment($dataResponse);
        $suffix = DataParser::cleanSlashComment($suffix);
        $suffix = DataParser::cleanMultipleWhiteSpaceTrim($suffix);
        $suffix = str_replace("\r\n", "\n", strtolower($suffix));
        $suffix = explode("\n", $suffix);
        $suffix = array_filter($suffix);
        $arrayData = [];
        // clear
        unset($body, $dataResponse);

        foreach ($iAna as $extension) {
            $extension = trim($extension);
            $extension = $collector->encode($extension);
            $arrayData[$extension] = [];
        }

        foreach ($suffix as $key => $extension) {
            $extension = trim($extension);
            if ($extension === '') {
                continue;
            }
            $extension = trim(strtolower($extension));
            if (strpos($extension, '.') === false) {
                continue;
            }

            $extArray = explode('.', $extension);
            if (strpos($extension, '*') === 0) {
                array_shift($extArray);
            }
            $extension = implode('.', $extArray);
            $extension = $collector->encode($extension);
            $extArray = explode('.', $extension);
            $ext = array_pop($extArray);
            if (!isset($arrayData[$ext]) || empty($extArray)) {
                continue;
            }
            $arrayData[$ext][] = implode('.', $extArray);
        }

        unset($iAna, $suffix);
        if ($collector->getAvailableExtensions() !== $arrayData) {
            $stream = new Stream(fopen($fileTLDExtensions, 'w+'));
            $data = self::generateArrayToStringDefaultServer($arrayData);
            $written = 0;
            while (true) {
                $dataToWrite = substr($data, $written, 4096);
                if (strlen($dataToWrite) < 1 || !($write = $stream->write($dataToWrite))) {
                    break;
                }
                $written += $write;
            }
            $stream->close();
        }

        $serverList = $collector->getAvailableServers();
        $diff = array_diff(array_keys($arrayData), array_keys($serverList));
        ksort($serverList);
        ksort($arrayData);
        if (!empty($diff)) {
            foreach ($diff as $val) {
                $serverList[$val] = [];
            }
            $stream = new Stream(fopen($fileServers, 'w+'));
            $data = self::generateArrayToStringDefaultServer($serverList);
            $written = 0;
            while (true) {
                $dataToWrite = substr($data, $written, 4096);
                if (strlen($dataToWrite) < 1 || !($write = $stream->write($dataToWrite))) {
                    break;
                }
                $written += $write;
            }
            $stream->close();
        }

        unset($serverList, $stream);
        return $arrayData;
    }

    /**
     * Generate CACERT
     *
     * @return int|string
     * @throws \Exception
     */
    public static function generateCaCertificate()
    {
        $fileExists = file_exists(self::PATH_CACERT);
        if ($fileExists) {
            if (!is_writeable(self::PATH_CACERT)) {
                return self::ERRR_DENIED;
            }
        }
        if (!$fileExists && !is_writeable(dirname(self::PATH_CACERT))) {
            return self::ERRR_DENIED;
        }
        try {
            $response = Transport::get(self::URI_CACERT);
        } catch (TimeOutException $e) {
            $response = Transport::get(self::URI_CACERT);
        } catch (\Exception $e) {
            throw $e;
        }
        $body = $response->getBody();
        $data = '';
        while (!$body->eof()) {
            $data .= $body->read(4096);
        }
        $body->close();

        $stream = new Stream(fopen(self::PATH_CACERT, 'w+'));
        $written = 0;
        while (true) {
            $dataToWrite = substr($data, $written, 4096);
            if (strlen($dataToWrite) < 1 || !($write = $stream->write($dataToWrite))) {
                break;
            }
            $written += $write;
        }
        $stream->close();

        return $data;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private static function generateArrayToStringDefaultServer(array $data)
    {
        $license = static::LICENSE;
        $newData = "<?php\n{$license}\nreturn [\n";
        $baseSeparator = '    ';
        $repeatedSeparator = str_repeat($baseSeparator, 2);
        foreach ($data as $extension => $server) {
            if (!is_array($server)) {
                continue;
            }
            $serverArr = '[';
            if (!empty($server)) {
                $server = array_map(function ($data) {
                    if (!is_string($data) || ($data = trim($data)) == ''
                        || strpos($data, "'") !== false
                    ) {
                        return false;
                    }

                    return strtolower($data);
                }, $server);

                $server = array_filter($server);
                if (!empty($server)) {
                    $serverArr .= "\n{$repeatedSeparator}'"
                          . implode("', \n{$repeatedSeparator}'", $server)
                          . "',\n{$baseSeparator}";
                }
            }

            $serverArr .= ']';
            $newData .= "{$baseSeparator}'{$extension}' => {$serverArr},\n";
        }

        $newData .= "];\n";
        return $newData;
    }

    /**
     * @param string $ip
     * @param string $server
     *
     * @return string
     */
    public static function buildNetworkAddressCommandServer(string $ip, string $server) : string
    {
        // if contain white space on IP ignore it
        if (! preg_match('/\s+/', trim($ip))
            && ($server = strtolower(trim($server))) !== ''
            && isset(static::$serverPrefixList[$server])
            && static::$serverPrefixList[$server]
        ) {
            $prefix = static::$serverPrefixList[$server];
            if (strpos($ip, "{$prefix} ") !== 0) {
                $ip = "{$prefix} {$ip}";
            }
        }

        return $ip;
    }

    /**
     * @param string $ip
     * @param string $server
     *
     * @return string
     */
    public static function buildASNCommandServer(string $ip, string $server) : string
    {
        // if contain white space on IP ignore it
        if (! preg_match('/\s+/', trim($ip))
            && ($server = strtolower(trim($server))) !== ''
            && isset(static::$serverPrefixList[$server])
            && static::$serverPrefixList[$server]
        ) {
            $ip     = ltrim($ip);
            if ($server === 'whois.arin.net') {
                return "a + {$ip}";
            }

            $ip     = ltrim($ip);
            $prefix = static::$serverPrefixList[$server];
            if (strpos($ip, "{$prefix} ") !== 0) {
                $ip = "{$prefix} $ip";
            }
        }

        return $ip;
    }
}
