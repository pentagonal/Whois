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

use Pentagonal\WhoIs\App\TLDCollector;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Handler\TransportSocketClient;

/**
 * Class Generator
 * @package Pentagonal\WhoIs\Util
 */
final class Generator
{
    const IANA_URI     = 'whois.iana.org';
    const IANA_IDN_URI = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';
    const PUBLIC_SUFFIX_URI = 'https://publicsuffix.org/list/effective_tld_names.dat';

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
            return -1;
        }

        if (!file_exists($fileTLDExtensions)) {
            if (!touch($fileTLDExtensions)) {
                return -1;
            }
        }

        if (!file_exists($fileServers)) {
            if (!touch($fileServers)) {
                return -1;
            }
        }

        if (!is_writeable($fileTLDExtensions) || !is_writeable($fileServers)) {
            return -1;
        }

        $client = TransportSocketClient::createClient();
        $ianaURI = TransportSocketClient::createUri(self::IANA_IDN_URI);
        $suffixURI = TransportSocketClient::createUri(self::PUBLIC_SUFFIX_URI);
        try {
            $ianaResponse = $client->request('GET', $ianaURI);
        } catch (TimeOutException $e) {
            $ianaResponse = $client->request('GET', $ianaURI);
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            $suffixResponse = $client->request('GET', $suffixURI);
        } catch (TimeOutException $e) {
            $suffixResponse = $client->request('GET', $suffixURI);
        } catch (\Exception $e) {
            throw $e;
        }

        // iana
        $iAna = Cleaner::cleanIniComment((string) $ianaResponse->getBody());
        $iAna = Cleaner::cleanMultipleWhiteSpaceTrim($iAna);
        $iAna = str_replace("\r\n", "\n", strtolower($iAna));
        $iAna = explode("\n", $iAna);
        $iAna = array_filter($iAna);

        // sufix
        $suffix = Cleaner::cleanIniComment((string) $suffixResponse->getBody());
        $suffix = Cleaner::cleanSlashComment($suffix);
        $suffix = Cleaner::cleanMultipleWhiteSpaceTrim($suffix);
        $suffix = str_replace("\r\n", "\n", strtolower($suffix));
        $suffix = explode("\n", $suffix);
        $suffix = array_filter($suffix);
        $arrayData = [];
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
            @file_put_contents($fileTLDExtensions, self::generateArrayToStringDefaultServer($arrayData));
        }

        $serverList = $collector->getAvailableServers();
        $diff = array_diff(array_keys($arrayData), array_keys($serverList));
        ksort($serverList);
        ksort($arrayData);
        if (!empty($diff)) {
            foreach ($diff as $val) {
                $serverList[$val] = [];
            }

            @file_put_contents($fileServers, self::generateArrayToStringDefaultServer($serverList));
        }
        unset($serverList);
        return $arrayData;
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
        foreach ($data as $extension => $server) {
            if (!is_array($server)) {
                continue;
            }
            $serverArr = '[';
            if (!empty($server)) {
                $serverArr .= "\n        '".implode("',\n        '", $server)."',\n    ";
            }
            $serverArr .= ']';
            $newData .= "    '{$extension}' => {$serverArr},\n";
        }
        $newData .= "];\n";
        return $newData;
    }
}
