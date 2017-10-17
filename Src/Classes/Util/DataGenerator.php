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
use Pentagonal\WhoIs\Util\TransportClient as Transport;

/**
 * Class DataGenerator
 * @package Pentagonal\WhoIs\Util
 * @final
 * @internal
 */
final class DataGenerator
{
    const ERR_DENIED = -1;

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
            return self::ERR_DENIED;
        }

        if (!file_exists($fileTLDExtensions)) {
            if (!touch($fileTLDExtensions)) {
                return self::ERR_DENIED;
            }
        }

        if (!file_exists($fileServers)) {
            if (!touch($fileServers)) {
                return self::ERR_DENIED;
            }
        }

        if (!is_writeable($fileTLDExtensions) || !is_writeable($fileServers)) {
            return self::ERR_DENIED;
        }

        try {
            $iAnaResponse = Transport::get(DataParser::URI_IANA_IDN);
        } catch (TimeOutException $e) {
            $iAnaResponse = Transport::get(DataParser::URI_IANA_IDN);
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            $suffixResponse = Transport::get(DataParser::URI_PUBLIC_SUFFIX);
        } catch (TimeOutException $e) {
            $suffixResponse = Transport::get(DataParser::URI_PUBLIC_SUFFIX);
        } catch (\Exception $e) {
            throw $e;
        }

        // iana
        $iAna = DataParser::cleanIniComment(DataParser::convertResponseBodyToString($iAnaResponse, false));
        $iAna = DataParser::cleanMultipleWhiteSpaceTrim($iAna);
        $iAna = str_replace("\r\n", "\n", strtolower($iAna));
        $iAna = explode("\n", $iAna);
        $iAna = array_filter($iAna);

        // suffix
        $suffix = DataParser::cleanIniComment(DataParser::convertResponseBodyToString($suffixResponse, false));
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
            if (strpos($extension, '.') === false
                // blogspot is not valid
                || strpos($extension, 'blogspot') === 0
                // amazon aws is not valid
                || strpos($extension, 'amazonaws') !== false
                // contains dash (-) is not valid
                || stripos($extension, 'xn--')  === false
                   && strpos($extension, '-') !== false
            ) {
                continue;
            }

            $extArray = explode('.', $extension);
            if (strpos($extension, '*') === 0) {
                array_shift($extArray);
            }

            $extension = implode('.', $extArray);
            $extension = $collector->encode($extension);
            $extArray  = explode('.', $extension);
            $ext = array_pop($extArray);
            // ck domain has no stld
            if (!isset($arrayData[$ext])
                 || empty($extArray)
                 || !in_array($ext, $collector->getCountryExtensionList())
            ) {
                continue;
            }

            $extension = ltrim(implode('.', $extArray), '!');
            $arrayData[$ext][] = $extension;
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
        $fileExists = file_exists(DataParser::PATH_CACERT);
        if ($fileExists) {
            if (!is_writeable(DataParser::PATH_CACERT)) {
                return self::ERR_DENIED;
            }
        }
        if (!$fileExists && !is_writeable(dirname(DataParser::PATH_CACERT))) {
            return self::ERR_DENIED;
        }
        try {
            $response = Transport::get(DataParser::URI_CACERT);
        } catch (TimeOutException $e) {
            $response = Transport::get(DataParser::URI_CACERT);
        } catch (\Exception $e) {
            throw $e;
        }

        $data = DataParser::convertResponseBodyToString($response, false);
        unset($response);

        $stream = new Stream(fopen(DataParser::PATH_CACERT, 'w+'));
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
                          . implode("',\n{$repeatedSeparator}'", $server)
                          . "',\n{$baseSeparator}";
                }
            }

            $serverArr .= ']';
            $newData .= "{$baseSeparator}'{$extension}' => {$serverArr},\n";
        }

        $newData .= "];\n";
        return $newData;
    }
}
