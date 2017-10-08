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

namespace Pentagonal\WhoIs\Util;

use InvalidArgumentException;

/**
 * Class DataGetter
 * @package Pentagonal\Whois\Util
 * For some reason I was found (on certain time) that iana does not include special
 * characters (idn) domain name. So, before regenerated please make sure you have been check it
 * before.
 */
class DataGetter
{
    const BASE_ORG_URL   = 'whois.iana.org';
    const BASE_ORG_TLD_ALPHA_URL = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';
    const TLD_PUBLIC_SUFFIX_URL = 'https://publicsuffix.org/list/effective_tld_names.dat';

    /**
     * TLD Lists
     *
     * @var array
     */
    protected $tldList;

    /**
     * File that stored extension (json file)
     *
     * @var string
     */
    protected $jsonDataFile;

    /**
     * Determine whether use default
     *
     * @var bool
     */
    private $useDefault = false;

    /**
     * @var array
     */
    protected static $tmpRemoteResultArray;

    /**
     * DataGetter constructor.
     *
     * @param string $jsonDataFile if given null it will be no action stored
     * @throws \Exception
     */
    public function __construct($jsonDataFile = null)
    {
        if ($jsonDataFile !== null && !is_string($jsonDataFile)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Json data file must be as a string %s given',
                    gettype($jsonDataFile)
                )
            );
        }

        if (!$jsonDataFile) {
            $this->setDefault(true);
        }

        /**
         * set json file for extension
         */
        $this->jsonDataFile = $jsonDataFile?: dirname(__DIR__) .'/Data/extensions.json';
    }

    /**
     * Set Default for generated data
     *
     * @param bool $isDefault
     */
    public function setDefault($isDefault)
    {
        $this->useDefault = (bool) $isDefault;
    }

    /**
     * Beware to set TLD list
     * this must be contains multidimensional arrays
     * with :
     *      [
     *          "ext" => [
     *              sub,
     *              sub2
     *          ]
     *      ]
     * @param array $tldList
     */
    public function setTldList($tldList)
    {
        if (!is_array($tldList)) {
            throw new InvalidArgumentException(
                sprintf(
                    'TLD list must be as array %s given',
                    gettype($tldList)
                ),
                E_WARNING
            );
        }

        $this->tldList = $tldList;
    }

    /**
     * @param StreamSocketTransport|Stream $stream
     *
     * @return string
     */
    private function removeCommentTextFromStream($stream)
    {
        $string = '';
        while (!$stream->eof()) {
            $string .= $stream->read(4096);
        }
        $stream->close();
        return preg_replace(
            [
                '/(\/\/|\#)[^\n]+/',
                '/(\s)+/'
            ],
            [
                '',
                '$1'
            ],
            $string
        );
    }

    /**
     * Get TLD Lists
     *
     * @return array
     */
    public function getTLDList()
    {
        if (!isset($this->tldList)) {
            $this->tldList = [];
            if (is_string($this->jsonDataFile) && file_exists($this->jsonDataFile) && is_file($this->jsonDataFile)) {
                $stream = new Stream(fopen($this->jsonDataFile, 'r+'));
                $data = '';
                while (!$stream->eof()) {
                    $data .= $stream->read(2048);
                }

                $stream->close();
                $data && $data = json_decode($data, true);
                if (is_array($data) && !empty($data)) {
                    $this->tldList = $data;
                    return $this->tldList;
                }
            }

            return $this->createNewRecordExtension();
        }

        return $this->tldList;
    }

    /**
     * Callback to filtering Result
     *
     * @param StreamSocketTransport|Stream $stream
     * @param $type
     */
    private function callBackFilterArrayTLDS($stream, $type)
    {
        $objectThis = $this;
        array_filter(
            array_map(
                function ($data) use ($objectThis, $type) {
                    $data = trim($data);
                    if ($type == 'iana') {
                        if (strlen($data) < 2) {
                            return;
                        }
                        $objectThis->tldList[idn_to_utf8($data)] = [];
                        return;
                    }

                    $data = ltrim($data, '.');
                    $countDot = substr_count($data, '.');
                    if ($countDot <> 1) {
                        return;
                    }
                    $data = idn_to_utf8($data);
                    $dataArray = explode('.', $data);
                    if (isset($objectThis->tldList[$dataArray[1]])) {
                        $objectThis->tldList[$dataArray[1]][] = $dataArray[0];
                    }
                },
                explode(
                    "\n",
                    strtolower(
                        $this->removeCommentTextFromStream($stream)
                    )
                )
            )
        );
    }

    /**
     * Build For Data Extension
     *
     * @return array
     * @throws \Exception
     */
    public function createNewRecordExtension()
    {
        if (is_string($this->jsonDataFile) && ! $this->useDefault) {
            $baseDir = dirname($this->jsonDataFile);
            if (! is_dir($baseDir)) {
                if (! @mkdir($baseDir, 0777, true)) {
                    throw new \RuntimeException(
                        'Json data directory can not being created. Directory %s is not write able',
                        dirname($baseDir)
                    );
                }
            } elseif (! file_exists($this->jsonDataFile) && ! is_writeable($baseDir)
              || ! is_writeable($this->jsonDataFile)
            ) {
                throw new \RuntimeException(
                    ! is_writeable($baseDir)
                        ? 'Json data can not being created. Data directory %s is not write able'
                        : 'Json data can not being renew. Json data file is not write able',
                    $baseDir
                );
            }
        }

        if (empty(self::$tmpRemoteResultArray['list_tld'])) {
            try {
                $this->registerTemporaryHandler();
                $baseSocket = fopen(self::BASE_ORG_TLD_ALPHA_URL, 'r');
            } catch (\Exception $e) {
                $this->restoreTemporaryHandler();
                throw $e;
            }

            try {
                $this->registerTemporaryHandler();
                $socket = fopen(self::TLD_PUBLIC_SUFFIX_URL, 'r');
            } catch (\Exception $e) {
                $this->restoreTemporaryHandler();
                fclose($baseSocket);
                throw $e;
            }

            $baseSocket = new Stream($baseSocket, 5);
            $socket     = new Stream($socket, 5);
            // async
            $this->callBackFilterArrayTLDS($baseSocket, 'iana');
            $this->callBackFilterArrayTLDS($socket, 'sub');
            self::$tmpRemoteResultArray['list_tld'] = !empty($this->tldList) ? $this->tldList : null;
        } else {
            $this->tldList = self::$tmpRemoteResultArray['list_tld'];
        }

        if (is_string($this->jsonDataFile) && ! $this->useDefault) {
            $socket = fopen($this->jsonDataFile, 'w+');
            $data = json_encode($this->tldList, JSON_PRETTY_PRINT);
            $length = strlen($data);
            $written = 0;
            while ($length > $written) {
                $fWrite = fwrite($socket, substr($data, $written, ($written+1024)));
                $written += (int) $fWrite;
                if (!$fWrite) {
                    break;
                }
            }

            fclose($socket);
            // create
            $this->createPhpAllExtensions();
            return $this->tldList;
        }

        return $this->tldList;
    }

    /**
     * Create Extensions List
     */
    final protected function createPhpAllExtensions()
    {
        $fileJsonReal = new \SplFileInfo(dirname(__DIR__) .'/Data/extensions.json');
        $fileJson = new \SplFileInfo($this->jsonDataFile);
        if (!$fileJsonReal->getRealPath() ||
            $fileJsonReal->getRealPath() !== $fileJson->getRealPath()
        ) {
            return;
        }

        $AllExt = dirname(__DIR__). '/Data/AllExtensions.php';
        if (is_writeable(dirname($AllExt)) && !file_exists(file_exists($AllExt))
            || file_exists($AllExt) && is_writeable($AllExt)
        ) {
            $arrPhp = "<?php\n";
            $arrPhp .= "/**\n"
                 . " * This package contains some code that reused by other repository(es) for private uses.\n"
                 . " * But on some certain conditions, it will also allowed to used as commercials project.\n"
                 . " * Some code & coding standard also used from other repositories as inspiration ideas.\n"
                 . " * And also uses 3rd-Party as to be used as result value without "
                    . "their permission but permit to be used.\n"
                 . " *\n"
                 . " * @license GPL-3.0  {@link https://www.gnu.org/licenses/gpl-3.0.en.html}\n"
                 . " * @copyright (c) 2017. Pentagonal Development\n"
                 . " * @author pentagonal <org@pentagonal.org>\n"
                 . " */\n\n";
            $arrPhp .= "/**\n * List of Whois Extensions\n";
            $arrPhp .= " * Automatic Generation File\n */\n";
            $arrPhp .= "return [\n";
            foreach ($this->tldList as $extension => $list) {
                $arrNow = " [";
                $has    = false;
                foreach ($list as $ext => $sub) {
                    if (! $has) {
                        $arrNow .= "\n";
                    }
                    $has    = true;
                    $arrNow .= "        '{$sub}',\n";
                }
                $arrNow .= ($has ? "    " : '') . "],\n";
                $arrPhp .= "    '{$extension}' => {$arrNow}";
            }
            $arrPhp .= "];\n";

            $socket = @fopen($AllExt, 'w+');
            if (! $socket) {
                return;
            }
            $length  = strlen($arrPhp);
            $written = 0;
            while ($length > $written) {
                $fWrite  = fwrite($socket, substr($arrPhp, $written, ($written + 1024)));
                $written += (int)$fWrite;
                if (! $fWrite) {
                    break;
                }
            }
            fclose($socket);
        }
    }

    /**
     * Error Handler of Socket
     */
    private function registerTemporaryHandler()
    {
        set_error_handler(function ($errNo, $errStr) {
            throw new \Exception(
                $errStr,
                $errNo
            );
        });
    }

    /**
     * Restore Error Handler
     */
    private function restoreTemporaryHandler()
    {
        restore_error_handler();
    }
}
