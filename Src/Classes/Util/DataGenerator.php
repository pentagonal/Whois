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
use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\App\TLDCollector;
use Pentagonal\WhoIs\Exceptions\ResourceException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use Pentagonal\WhoIs\Util\TransportClient as Transport;

/**
 * Class DataGenerator
 * @package Pentagonal\WhoIs\Util
 * @final
 * @internal
 *
 * *NOTE:
 *      This is class helper to help generate much of data to make sure the package working properly.
 *      This class must be only run via CLI / CRON mode to auto / manually generate data.
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

    public static function generateDefaultExtensionServerList()
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
        $iAna = DataParser::cleanIniComment(DataParser::convertResponseBodyToString($iAnaResponse));
        $iAna = DataParser::cleanMultipleWhiteSpaceTrim($iAna);
        $iAna = str_replace("\r\n", "\n", strtolower($iAna));
        $iAna = explode("\n", $iAna);
        $iAna = array_filter($iAna);

        // suffix
        $suffix = DataParser::cleanIniComment(DataParser::convertResponseBodyToString($suffixResponse));
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
            // ck domain has no STld
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
        $socket = @fopen($fileTLDExtensions, 'w+');
        if (!$socket) {
            throw new ResourceException(
                'Could not create resource stream',
                E_NOTICE
            );
        }

        if (!flock($socket, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if ($wouldBlock) {
                throw new ResourceException(
                    sprintf(
                        'Could not lock file: %s, maybe has been lock by other process',
                        $socket
                    ),
                    E_NOTICE
                );
            }
            throw new ResourceException(
                sprintf(
                    'Could not lock file: %s',
                    $socket
                ),
                E_NOTICE
            );
        }

        if ($collector->getAvailableExtensions() !== $arrayData) {
            $stream = new Stream($socket);
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

        $data = DataParser::convertResponseBodyToString($response);
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
     * Generate AS Block Number
     *
     * @return int|array -1 if denied and total data stored and array if success
     */
    public static function generateASNumberFileData()
    {
        $fileASN16Blocks   = DataParser::PATH_AS16_DEL_BLOCKS;
        $fileASN32Blocks   = DataParser::PATH_AS32_DEL_BLOCKS;
        if (($fileASN16BlocksExists = file_exists($fileASN16Blocks))) {
            if (!is_writeable($fileASN16Blocks)) {
                throw new ResourceException(
                    sprintf(
                        'File %s is not writable',
                        $fileASN16Blocks
                    ),
                    E_ERROR
                );
            }
        }
        if (($fileASN32BlocksExists = file_exists($fileASN32Blocks))) {
            if (!is_writeable($fileASN32Blocks)) {
                throw new ResourceException(
                    sprintf(
                        'File %s is not writable',
                        $fileASN32Blocks
                    ),
                    E_ERROR
                );
            }
        }

        if (!$fileASN16BlocksExists && !is_writeable(dirname($fileASN16Blocks))) {
            throw new ResourceException(
                sprintf(
                    'Directory %s is not writable',
                    dirname($fileASN16Blocks)
                ),
                E_ERROR
            );
        }
        if (!$fileASN32BlocksExists && !is_writeable(dirname($fileASN32Blocks))) {
            throw new ResourceException(
                sprintf(
                    'Directory %s is not writable',
                    dirname($fileASN32Blocks)
                ),
                E_ERROR
            );
        }

        // create tmp file because file have huge data
        $asn16BlockTemp = "{$fileASN16Blocks}.tmp";
        $asn16BlockHandler = @fopen($asn16BlockTemp, 'w+');
        if (!$asn16BlockHandler) {
            throw new ResourceException(
                sprintf(
                    'Could not create stream resource for %s',
                    $asn16BlockTemp
                ),
                E_ERROR
            );
        }

        if (!flock($asn16BlockHandler, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if ($wouldBlock) {
                throw new ResourceException(
                    sprintf(
                        'Could not temporary lock file: %s, maybe has been lock by other process',
                        $asn16BlockTemp
                    ),
                    E_NOTICE
                );
            }
            throw new ResourceException(
                sprintf(
                    'Could not lock temporary file: %s',
                    $asn16BlockTemp
                ),
                E_NOTICE
            );
        }

        $asn32BlockTemp    = "{$fileASN32Blocks}.tmp";
        $asn32BlockHandler = @fopen($asn32BlockTemp, 'w+');
        if (!$asn32BlockHandler) {
            throw new ResourceException(
                sprintf(
                    'Could not create stream resource for %s',
                    $asn32BlockTemp
                ),
                E_ERROR
            );
        }

        if (!flock($asn32BlockHandler, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if ($wouldBlock) {
                throw new ResourceException(
                    sprintf(
                        'Could not temporary lock file: %s, maybe has been lock by other process',
                        $asn32BlockTemp
                    ),
                    E_NOTICE
                );
            }
            throw new ResourceException(
                sprintf(
                    'Could not lock temporary file: %s',
                    $asn32BlockTemp
                ),
                E_NOTICE
            );
        }

        $asnBlockStream   = new Stream($asn16BlockHandler);
        $asn32BlockStream = new Stream($asn32BlockHandler);

        // add shutdown
        register_shutdown_function(function () use ($asn16BlockTemp, $asn32BlockTemp) {
            clearstatcache();
            if (file_exists($asn32BlockTemp) && is_writable($asn32BlockTemp)) {
                unlink($asn32BlockTemp);
            }
            if (file_exists($asn16BlockTemp) && is_writable($asn16BlockTemp)) {
                unlink($asn16BlockTemp);
            }
        });

        /* ------------------------------------------------------
         * CREATE RESOURCE
         * ------------------------------------------------------
         */

        // create stream for iana table
        $isXML = class_exists('SimpleXMLElement');
        $asnDelegatedTableURL = $isXML
            ? DataParser::URI_IANA_ASN_TABLE_XML
            : DataParser::URI_IANA_ASN_TABLE;
        $asNumber16 = [];
        $asNumber32 = [];
        $completed = 0;
        // use XML Element to make it faster
        if ($isXML) {
            $simpleXML = new \SimpleXMLElement($asnDelegatedTableURL, 0, true);
            /**
             * @var \SimpleXMLElement $value
             */
            foreach ($simpleXML as $key => $value) {
                if ($value->getName() !== 'registry') {
                    continue;
                }
                $attributes = $value->attributes();
                if (!isset($attributes['id'])
                    || ($id = (string) $attributes['id']) == ''
                    || strpos($id, 'as-numbers-') !== 0
                ) {
                    continue;
                }

                $pos = substr($id, -1);
                if (! is_numeric($pos) || ! in_array(abs($pos), [1, 2])) {
                    continue;
                }

                $pos       = abs($pos);
                $completed += $pos === 1 || $pos === 2 ? 1 : 0;
                $number    = $pos === 1 ? $asNumber16 : $asNumber32;
                /**
                 * @var \SimpleXMLElement $xml
                 */
                foreach ($value as $selector => $xml) {
                    if ($xml->getName() !== 'record') {
                        continue;
                    }
                    $children = $xml->children();
                    if (!isset($children->number)) {
                        continue;
                    }
                    $asn    = (string) $children->number;
                    $status = isset($children->description)
                        ? (string) $children->description
                        : '';
                    $server = isset($children->whois)
                        ? (string) $children->whois
                        : '';
                    if (stripos($status, 'Reserved') !== false) {
                        if (strpos($status, 'sample') !== false) {
                            $server = DataParser::RESERVED_SAMPLE;
                        } elseif (stripos($status, 'private') !== false) {
                            $server = DataParser::RESERVED_PRIVATE;
                        } else {
                            $server = DataParser::RESERVED;
                        }
                    } elseif (stripos($status, 'Unallocated') !== false) {
                        $server = DataParser::UNALLOCATED;
                    }
                    if ($server === '') {
                        continue;
                    }
                    if (! isset($number[$server])) {
                        $number[$server] = [];
                    }
                    $number[$server][] = $asn;
                }
                if ($pos === 1) {
                    $asNumber16 = $number;
                } elseif ($pos === 2) {
                    $asNumber32 = $number;
                }
                unset($number);
            }
        }

        // backward compat
        if (empty($asNumber16) || empty($asNumber32)) {
            $stream = TransportClient::requestConnection('GET', DataParser::URI_IANA_ASN_TABLE);
            $body   = (string)$stream->getBody();
            foreach (DataParser::htmlParenthesisParser('table', $body) as $arrayCollector) {
                $selector = $arrayCollector->get('selector');
                $html     = $arrayCollector->get('html', '');
                if (empty($selector['id'])
                    || ! is_string($selector['id'])
                    || strpos($selector['id'], 'table-as-numbers-') !== 0
                    || ! is_string($html)
                    || $html == ''
                ) {
                    continue;
                }
                $id     = $selector['id'];
                $parsed = DataParser::htmlParenthesisParser('tbody', $html);
                if (! $parsed->count() || $parsed->last()['html'] == '') {
                    continue;
                }
                if ($completed === 2) {
                    break;
                }

                $pos = substr($id, -1);
                if (! is_numeric($pos) || ! in_array(abs($pos), [1, 2])) {
                    continue;
                }
                $pos       = abs($pos);
                $completed += $pos === 1 || $pos === 2 ? 1 : 0;
                $number    = $pos === 1 ? $asNumber16 : $asNumber32;
                $parsed    = DataParser::htmlParenthesisParser('tr', $parsed->last()['html']);
                $parsed->each(
                    function (ArrayCollector $collector) use (&$number, &$completed) {
                        $parser = DataParser::htmlParenthesisParser('td', $collector->get('html'));
                        if (count($parser) <> 6) {
                            return;
                        }
                        $asn    = trim($parser->first()->get('html', ''));
                        $status = trim($parser->next()->get('html', ''));
                        $server = trim($parser->next()->get('html', ''));
                        if (stripos($status, 'Reserved') !== false) {
                            if (strpos($status, 'sample') !== false) {
                                $server = DataParser::RESERVED_SAMPLE;
                            } elseif (stripos($status, 'private') !== false) {
                                $server = DataParser::RESERVED_PRIVATE;
                            } else {
                                $server = DataParser::RESERVED;
                            }
                        } elseif (stripos($status, 'Unallocated') !== false) {
                            $server = DataParser::UNALLOCATED;
                        }

                        if ($server === '') {
                            return;
                        }
                        if (! isset($number[$server])) {
                            $number[$server] = [];
                        }
                        $number[$server][] = $asn;
                    }
                );
                if ($pos === 1) {
                    $asNumber16 = $number;
                } elseif ($pos === 2) {
                    $asNumber32 = $number;
                }
                unset($number);
            }
        }

        ksort($asNumber32);
        ksort($asNumber16);
        $asn16Written   = false;
        $asn32Written = false;

        /* ------------------------------------------------------
         * WRITE COMMENTS BLOCKS
         * ------------------------------------------------------
         */
        $reserved = DataParser::RESERVED;
        $reservedPrivate = DataParser::RESERVED_PRIVATE;
        $reservedSample = DataParser::RESERVED_SAMPLE;
        $unallocated = DataParser::UNALLOCATED;
        $asnUrl = DataParser::URI_IANA_ASN_TABLE;
        $comment = trim(ltrim(preg_replace('/^\s*[\/\*\\\]([\*\/]+)?/m', '# ', self::LICENSE), '#'));
        $comment = str_replace("\n", "\r\n", $comment);
        $comment = str_replace("\r\r", "\r", $comment)."\r\n";
        $comment .= <<<COMMENT
# =========================================================================================================\r\n
#
# This file contains auto generated ASN (Autonomous System Numbers) Please does not edit directly.\r\n
# 
# {{TYPE}}\r\n
#
# Database Provided by IANA ASN Delegation Table\r\n
# For more information, please refer to {$asnUrl}\r\n
#
# NOTE:\r\n
#     whois.ripe.net as example starting collection key server\r\n
#     123-567         The ( - ) is as separator that define it about `asn` is has range start and ending.\r\n
#                     If has no separator and end of asn number, it mean it was single `asn` with no range\r\n
#     New line separator is CRLF (Windows) or as is : \\r\\n
#
# [{$reserved}]         : ASN is Reserved by IANA
# [{$reservedSample}]  : ASN is Reserved for use in documentation and sample code
# [{$reservedPrivate}] : ASN is Reserved for Private Use
# [{$unallocated}]      : ASN is Unallocated or has not been yet registered
#
COMMENT;

        $comment = str_replace("\n\n", "\n", $comment);
        $asnBlockStream->write(
            str_replace('{{TYPE}}', '16-bit Autonomous System Numbers', $comment)
        );
        $asn32BlockStream->write(
            str_replace('{{TYPE}}', '32-bit Autonomous System Numbers', $comment)
        );
        $countedASN16 = 0;
        $countedASN32 = 0;
        foreach ($asNumber16 as $server => $asnArray) {
            $asnBlockStream->write("\r\n[{$server}]");
            foreach ($asnArray as $asn) {
                $countedASN16++;
                $asn16Written = true;
                $asnBlockStream->write("\r\n{$asn}");
            }
        }

        // close stream
        $asnBlockStream->close();
        // check if ASN has been written
        if ($asn16Written) {
            if ($fileASN16BlocksExists && ! unlink($fileASN16Blocks)) {
                $asn32BlockStream->close();
                throw new \RuntimeException(
                    'Data generator could not handle file ASN Block Please check',
                    E_ERROR
                );
            }
            // rename
            rename($asn16BlockTemp, $fileASN16Blocks);
        }

        foreach ($asNumber32 as $server => $asnArray) {
            $asn32BlockStream->write("\r\n[{$server}]");
            foreach ($asnArray as $asn) {
                $countedASN32++;
                $asn32Written = true;
                $asn32BlockStream->write("\r\n{$asn}");
            }
        }

        // close stream
        $asn32BlockStream->close();
        if ($asn32Written) {
            if ($fileASN32BlocksExists && ! unlink($fileASN32Blocks)) {
                throw new \RuntimeException(
                    'Data generator could not handle file ASN Block Please check',
                    E_ERROR
                );
            }

            rename($asn32BlockTemp, $fileASN32Blocks);
        }

        return [
            'asn16' => $countedASN16,
            'asn32' => $countedASN32,
            'total' => $countedASN32+$countedASN16,
        ];
    }

    /**
     * Generate IP Data
     *
     * @return int|array -1 if denied and total data stored and array if success
     */
    public static function generateIPv64FileData()
    {
        $fileIPv4Blocks   = DataParser::PATH_IP4_BLOCKS;
        $fileIPv6Blocks   = DataParser::PATH_IP6_BLOCKS;
        if (($fileIPv4locksExists = file_exists($fileIPv4Blocks))) {
            if (!is_writeable($fileIPv4Blocks)) {
                throw new ResourceException(
                    sprintf(
                        'File %s is not writable',
                        $fileIPv4Blocks
                    ),
                    E_ERROR
                );
            }
        }
        if (($fileIpv6BlocksExists = file_exists($fileIPv6Blocks))) {
            if (!is_writeable($fileIPv6Blocks)) {
                throw new ResourceException(
                    sprintf(
                        'File %s is not writable',
                        $fileIPv6Blocks
                    ),
                    E_ERROR
                );
            }
        }

        if (!$fileIPv4locksExists && !is_writeable(dirname($fileIPv4Blocks))) {
            throw new ResourceException(
                sprintf(
                    'Directory %s is not writable',
                    dirname($fileIPv4Blocks)
                ),
                E_ERROR
            );
        }
        if (!$fileIpv6BlocksExists && !is_writeable(dirname($fileIPv6Blocks))) {
            throw new ResourceException(
                sprintf(
                    'Directory %s is not writable',
                    dirname($fileIPv6Blocks)
                ),
                E_ERROR
            );
        }

        // create tmp file because file have huge data
        $ipv4BlockTemp = "{$fileIPv4Blocks}.tmp";
        $ipv4BlockHandler = @fopen($ipv4BlockTemp, 'w+');
        if (!$ipv4BlockHandler) {
            throw new ResourceException(
                sprintf(
                    'Could not create stream resource for %s',
                    $ipv4BlockTemp
                ),
                E_ERROR
            );
        }
        if (!flock($ipv4BlockHandler, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if ($wouldBlock) {
                throw new ResourceException(
                    sprintf(
                        'Could not temporary lock file: %s, maybe has been lock by other process',
                        $ipv4BlockTemp
                    ),
                    E_NOTICE
                );
            }
            throw new ResourceException(
                sprintf(
                    'Could not lock temporary file: %s',
                    $ipv4BlockTemp
                ),
                E_NOTICE
            );
        }

        $ipv6BlockTemp    = "{$fileIPv6Blocks}.tmp";
        $ipv6BlockHandler = @fopen($ipv6BlockTemp, 'w+');
        if (!$ipv6BlockHandler) {
            throw new ResourceException(
                sprintf(
                    'Could not create stream resource for %s',
                    $ipv6BlockTemp
                ),
                E_ERROR
            );
        }

        if (!flock($ipv6BlockHandler, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if ($wouldBlock) {
                throw new ResourceException(
                    sprintf(
                        'Could not temporary lock file: %s, maybe has been lock by other process',
                        $ipv6BlockTemp
                    ),
                    E_NOTICE
                );
            }
            throw new ResourceException(
                sprintf(
                    'Could not lock temporary file: %s',
                    $ipv6BlockTemp
                ),
                E_NOTICE
            );
        }

        $ipv4BlockStream   = new Stream($ipv4BlockHandler);
        $ipv6BlockStream = new Stream($ipv6BlockHandler);

        // add shutdown
        register_shutdown_function(function () use ($ipv4BlockTemp, $ipv6BlockTemp) {
            clearstatcache();
            if (file_exists($ipv6BlockTemp) && is_writable($ipv6BlockTemp)) {
                unlink($ipv6BlockTemp);
            }
            if (file_exists($ipv4BlockTemp) && is_writable($ipv4BlockTemp)) {
                unlink($ipv4BlockTemp);
            }
        });

        /* ------------------------------------------------------
         * CREATE RESOURCE
         * ------------------------------------------------------
         */

        // create stream for iana table
        $isXML = class_exists('SimpleXMLElement');
        $ipv4TableURL = $isXML
            ? DataParser::URI_IANA_IPV4_TABLE_XML
            : DataParser::URI_IANA_IPV4_TABLE;
        $ipv6TableURL = $isXML
            ? DataParser::URI_IANA_IPV6_TABLE_XML
            : DataParser::URI_IANA_IPV6_TABLE;
        $ipv4PrefixLists = [];
        $ipv6PrefixLists = [];
        $completed = 0;

        // use XML Element to make it faster
        if (!$isXML) {
            $simpleXMLIPv4 = new \SimpleXMLElement($ipv4TableURL, 0, true);
            $simpleXMLIPv6 = new \SimpleXMLElement($ipv6TableURL, 0, true);
            /**
             * @var \SimpleXMLElement $value
             */
            foreach ($simpleXMLIPv4 as $key => $value) {
                if ($value->getName() !== 'record') {
                    continue;
                }
                $children = $value->children();
                if (!isset($children->prefix)) {
                    continue;
                }

                $rangeIP = (string) $children->prefix;
                $status = isset($children->status)
                    ? (string) $children->status
                    : '';
                $designation = isset($children->designation)
                    ? (string) $children->designation
                    : '';
                $designation = !$designation
                    && isset($children->description)
                    ? (string) $children->description
                    : $designation;
                $server = isset($children->whois)
                    ? (string) $children->whois
                    : '';
                if (stripos($status, 'Reserved') !== false) {
                    if (strpos($designation, 'local') !== false) {
                        $server = DataParser::RESERVED_LOCAL;
                    } elseif (stripos($designation, 'private') !== false) {
                        $server = DataParser::RESERVED_PRIVATE;
                    } elseif (stripos($designation, 'future') !== false) {
                        $server = DataParser::RESERVED_FUTURE;
                    } elseif (stripos($designation, 'multicast') !== false) {
                        $server = DataParser::RESERVED_MULTICAST;
                    } else {
                        $server = DataParser::RESERVED;
                    }
                }

                if ($server === '') {
                    continue;
                }
                if (! isset($ipv4PrefixLists[$server])) {
                    $ipv4PrefixLists[$server] = [];
                }
                $ipv4PrefixLists[$server][] = $rangeIP;
            }

            /**
             * @var \SimpleXMLElement $value
             */
            foreach ($simpleXMLIPv6 as $key => $value) {
                if ($value->getName() !== 'record') {
                    continue;
                }

                $children = $value->children();
                if (!isset($children->prefix)) {
                    continue;
                }

                $rangeIP = trim((string) $children->prefix);
                $status = isset($children->status)
                    ? trim((string) $children->status)
                    : '';
                $designation = isset($children->designation)
                    ? trim((string) $children->designation)
                    : '';
                $designation = !$designation
                    && isset($children->description)
                    ? trim((string) $children->description)
                    : $designation;
                $server = isset($children->whois)
                    ? trim((string) $children->whois)
                    : '';
                if (stripos($status, 'Reserved') !== false) {
                    if (strpos($designation, 'local') !== false) {
                        $server = DataParser::RESERVED_LOCAL;
                    } elseif (stripos($designation, 'private') !== false) {
                        $server = DataParser::RESERVED_PRIVATE;
                    } elseif (stripos($designation, 'future') !== false) {
                        $server = DataParser::RESERVED_FUTURE;
                    } elseif (stripos($designation, 'multicast') !== false) {
                        $server = DataParser::RESERVED_MULTICAST;
                    } else {
                        $server = DataParser::RESERVED;
                    }
                }
                if ($server === '' && $designation) {
                    $server = DataParser::URI_IANA_WHOIS;
                }
                if ($server === '') {
                    continue;
                }

                if (! isset($ipv6PrefixLists[$server])) {
                    $ipv6PrefixLists[$server] = [];
                }
                $ipv6PrefixLists[$server][] = $rangeIP;
            }
        }

        // backward compat
        if (empty($ipv4PrefixLists) || empty($ipv6PrefixLists)) {
            $streamIPv4 = TransportClient::requestConnection('GET', DataParser::URI_IANA_IPV4_TABLE);
            $streamIPv6 = TransportClient::requestConnection('GET', DataParser::URI_IANA_IPV6_TABLE);
            $bodyIPv4   = (string)$streamIPv4->getBody();
            foreach (DataParser::htmlParenthesisParser('table', $bodyIPv4) as $arrayCollector) {
                $selector = $arrayCollector->get('selector');
                $html     = $arrayCollector->get('html', '');
                if (empty($selector['id'])
                    || ! is_string($selector['id'])
                    || strpos($selector['id'], 'table-ipv4-address-space') !== 0
                    || ! is_string($html)
                    || $html == ''
                ) {
                    continue;
                }

                $parsed = DataParser::htmlParenthesisParser('tbody', $html);
                if (! $parsed->count() || $parsed->last()['html'] == '') {
                    continue;
                }

                $parsed    = DataParser::htmlParenthesisParser('tr', $parsed->last()['html']);
                $parsed->each(
                    function (ArrayCollector $collector) use (&$ipv4PrefixLists, &$completed) {
                        $parser = DataParser::htmlParenthesisParser('td', $collector->get('html'));
                        if (count($parser) <> 7) {
                            return;
                        }
                        $ipRange    = trim($parser->first()->get('html', ''));
                        $designation = trim($parser->next()->get('html', ''));
                        $status = trim($parser->offset(5)->get('html', ''));
                        $server = trim($parser->offset(3)->get('html', ''));
                        if (stripos($status, 'Reserved') !== false) {
                            if (strpos($designation, 'local') !== false) {
                                $server = DataParser::RESERVED_LOCAL;
                            } elseif (stripos($designation, 'private') !== false) {
                                $server = DataParser::RESERVED_PRIVATE;
                            } elseif (stripos($designation, 'future') !== false) {
                                $server = DataParser::RESERVED_FUTURE;
                            } elseif (stripos($designation, 'multicast') !== false) {
                                $server = DataParser::RESERVED_MULTICAST;
                            } else {
                                $server = DataParser::RESERVED;
                            }
                        }
                        if (!$server && $designation) {
                            $server = DataParser::URI_IANA_WHOIS;
                        }

                        if ($server === '') {
                            return;
                        }

                        if (! isset($ipv4PrefixLists[$server])) {
                            $ipv4PrefixLists[$server] = [];
                        }
                        $ipv4PrefixLists[$server][] = $ipRange;
                    }
                );
            }
            $bodyIPv6   = (string)$streamIPv6->getBody();
            foreach (DataParser::htmlParenthesisParser('table', $bodyIPv6) as $arrayCollector) {
                $selector = $arrayCollector->get('selector');
                $html     = $arrayCollector->get('html', '');
                if (empty($selector['id'])
                    || ! is_string($selector['id'])
                    || strpos($selector['id'], 'table-ipv6-unicast-address-assignments') !== 0
                    || ! is_string($html)
                    || $html == ''
                ) {
                    continue;
                }

                $parsed = DataParser::htmlParenthesisParser('tbody', $html);
                if (! $parsed->count() || $parsed->last()['html'] == '') {
                    continue;
                }

                $parsed    = DataParser::htmlParenthesisParser('tr', $parsed->last()['html']);
                $parsed->each(
                    function (ArrayCollector $collector) use (&$ipv6PrefixLists, &$completed) {
                        $parser = DataParser::htmlParenthesisParser('td', $collector->get('html'));
                        if (count($parser) <> 7) {
                            return;
                        }
                        $ipRange    = trim($parser->first()->get('html', ''));
                        $designation = trim($parser->next()->get('html', ''));
                        $status = trim($parser->offset(5)->get('html', ''));
                        $server = trim($parser->offset(3)->get('html', ''));
                        if (stripos($status, 'Reserved') !== false) {
                            if (strpos($designation, 'local') !== false) {
                                $server = DataParser::RESERVED_LOCAL;
                            } elseif (stripos($designation, 'private') !== false) {
                                $server = DataParser::RESERVED_PRIVATE;
                            } elseif (stripos($designation, 'future') !== false) {
                                $server = DataParser::RESERVED_FUTURE;
                            } elseif (stripos($designation, 'multicast') !== false) {
                                $server = DataParser::RESERVED_MULTICAST;
                            } else {
                                $server = DataParser::RESERVED;
                            }
                        }
                        if (!$server && $designation) {
                            $server = DataParser::URI_IANA_WHOIS;
                        }

                        if ($server === '') {
                            return;
                        }

                        if (! isset($ipv6PrefixLists[$server])) {
                            $ipv6PrefixLists[$server] = [];
                        }
                        $ipv6PrefixLists[$server][] = $ipRange;
                    }
                );
            }
        }

        ksort($ipv6PrefixLists);
        ksort($ipv4PrefixLists);
        $ipv4Written   = false;
        $ipv6Written = false;
        /* ------------------------------------------------------
         * WRITE COMMENTS BLOCKS
         * ------------------------------------------------------
         */
        $reserved = DataParser::RESERVED;
        $reservedPrivate = DataParser::RESERVED_PRIVATE;
        $reservedLocal   = DataParser::RESERVED_LOCAL;
        $reservedMultiCast   = DataParser::RESERVED_MULTICAST;
        $reservedFuture   = DataParser::RESERVED_FUTURE;

        $comment = trim(ltrim(preg_replace('/^\s*[\/\*\\\]([\*\/]+)?/m', '# ', self::LICENSE), '#'));
        $comment = str_replace("\n", "\r\n", $comment);
        $comment = str_replace("\r\r", "\r", $comment)."\r\n";
        $comment .= <<<COMMENT
# =========================================================================================================\r\n
#
# This file contains auto generated {{TYPE}} Record Please does not edit directly.\r\n
# 
#
# Database Provided by IANA {{TYPE}} Table Space\r\n
# For more information, please refer to {{URL}}\r\n
#
# NOTE:\r\n
#     whois.ripe.net as example starting collection key server\r\n
#     123/12 or 123:345::/23
#               The (/) is as separator that define it about `CIDR`.\r\n
#               If has no separator and end of rangeIP number, it mean it was single `rangeIP` with no range\r\n
#     New line separator is CRLF (Windows) or as is : \\r\\n
#
# [{$reserved}]           : Reserved By IANA
# [{$reservedPrivate}]   : IP has been allocated for private
# [{$reservedLocal}]     : IP has been allocated for local network
# [{$reservedMultiCast}] : Multicast (formerly "Class D")
# [{$reservedFuture}]    : Reserved for future release
#
COMMENT;

        $comment = str_replace("\n\n", "\n", $comment);
        $ipv4BlockStream->write(
            str_replace(
                ['{{TYPE}}', '{{URL}}'],
                ['IPv4 Address', DataParser::URI_IANA_IPV4_TABLE],
                $comment
            )
        );
        $ipv6BlockStream->write(
            str_replace(
                ['{{TYPE}}', '{{URL}}'],
                ['IPv6 Address', DataParser::URI_IANA_IPV6_TABLE],
                $comment
            )
        );
        $countedIPv4 = 0;
        $countedIPv6 = 0;
        foreach ($ipv4PrefixLists as $server => $asnArray) {
            $ipv4BlockStream->write("\r\n[{$server}]");
            foreach ($asnArray as $rangeIP) {
                $countedIPv4++;
                $ipv4Written = true;
                $ipv4BlockStream->write("\r\n{$rangeIP}");
            }
        }

        // close stream
        $ipv4BlockStream->close();
        // check if ASN has been written
        if ($ipv4Written) {
            if ($fileIPv4locksExists && ! unlink($fileIPv4Blocks)) {
                $ipv6BlockStream->close();
                throw new \RuntimeException(
                    'Data generator could not handle file ASN Block Please check',
                    E_ERROR
                );
            }

            // rename
            rename($ipv4BlockTemp, $fileIPv4Blocks);
        }

        foreach ($ipv6PrefixLists as $server => $asnArray) {
            $ipv6BlockStream->write("\r\n[{$server}]");
            foreach ($asnArray as $rangeIP) {
                $countedIPv6++;
                $ipv6Written = true;
                $ipv6BlockStream->write("\r\n{$rangeIP}");
            }
        }

        // close stream
        $ipv6BlockStream->close();
        if ($ipv6Written) {
            if ($fileIpv6BlocksExists && ! unlink($fileIPv6Blocks)) {
                throw new \RuntimeException(
                    'Data generator could not handle file ASN Block Please check',
                    E_ERROR
                );
            }

            rename($ipv6BlockTemp, $fileIPv6Blocks);
        }

        return [
            'ipv4' => $countedIPv4,
            'ipv6' => $countedIPv6,
            'total' => $countedIPv6+$countedIPv4,
        ];
    }

    /**
     * Generate Array Extensions data to array files
     *
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
