<?php
namespace Pentagonal\WhoIs\App;

use Pentagonal\WhoIs\Util\Puny;

/**
 * Class TLDCollector
 * @package Pentagonal\WhoIs\App
 */
class TLDCollector
{
    /**
     * @var array[]|string[][]
     */
    protected $availableServers = [];

    /**
     * @var array[]|string[][]
     */
    protected $availableExtensions = [];

    /**
     * @var Puny
     */
    protected $punyCodeInstance;

    /**
     * @var string
     */
    protected $availableServersFile;

    /**
     * @var string
     */
    protected $availableExtensionsFile;

    /**
     * TLDCollector constructor.
     *
     * @param array|null $serverList
     * @final
     */
    final public function __construct(array $serverList = null)
    {
        $this->punyCodeInstance        = new Puny();
        $this->availableServersFile    = dirname(__DIR__) . '/Data/AvailableServers.php';
        $this->availableExtensionsFile = dirname(__DIR__) . '/Data/AvailableExtensions.php';

        /** @noinspection PhpIncludeInspection */
        $this->availableServers = $this->checkTLDList($serverList ?: require $this->availableServersFile);
        /** @noinspection PhpIncludeInspection */
        $this->availableExtensions = $this->checkTLDList($serverList ?: require $this->availableExtensionsFile);
    }

    /**
     * Get Server List File
     *
     * @return string
     */
    public function getAvailableServersFile() : string
    {
        return $this->availableServersFile;
    }

    /**
     * @return string
     */
    public function getAvailableExtensionsFile() : string
    {
        return $this->availableExtensionsFile;
    }

    /**
     * @param array $list
     *
     * @return array
     */
    protected function checkTLDList(array $list) : array
    {
        $data = [];
        foreach ($list as $key => $value) {
            if (!is_string($key) || ! is_array($value)) {
                throw new \RuntimeException(
                    'Invalid whois server list declared. This contains invalid whois server or extension.',
                    E_WARNING
                );
            }

            $key = $this->encode($key);
            $data[$key] = [];
            foreach ($value as $server) {
                if (!is_string($server)) {
                    throw new \RuntimeException(
                        'Invalid whois server list declared. This contains invalid whois server definition.',
                        E_WARNING
                    );
                }
                $data[$key][] = $this->encode($server);
            }
        }

        return $data;
    }

    /**
     * @return Puny
     */
    public function getPunyCode() : Puny
    {
        return clone $this->punyCodeInstance;
    }

    /**
     * Encode Puny code
     *
     * @param string $string
     *
     * @return string
     */
    public function encode(string $string) : string
    {
        return $this->punyCodeInstance->encode($string);
    }

    /**
     * Decode puny code
     *
     * @param string $string
     *
     * @return string
     */
    public function decode(string $string) : string
    {
        return $this->punyCodeInstance->decode($string);
    }

    /**
     * Check if extension exists
     *
     * @param string $extension
     *
     * @return bool
     */
    public function isExtensionExists(string $extension) : bool
    {
        $extension = $this->encode(trim($extension));
        return isset($this->availableServers[$extension]);
    }

    /**
     * @return array[]|\string[][]
     */
    public function getAvailableServers() : array
    {
        return $this->availableServers;
    }

    /**
     * @return array
     */
    public function getAvailableExtensions() : array
    {
        return $this->availableExtensions;
    }

    /**
     * Get servers list from extensions, when it was empty return null
     *
     * @param string $extension
     *
     * @return null|ArrayCollector
     */
    public function getServersFromExtension(string $extension)
    {
        if (trim($extension) == '') {
            throw new \InvalidArgumentException(
                'Extension could not be empty or white space only',
                E_NOTICE
            );
        }

        $extension = $this->encode(trim($extension));
        return !empty($this->availableServers[$extension])
            ? new ArrayCollector($this->availableServers[$extension])
            : null;
    }

    /**
     * Get Server From Servers
     *
     * @param string $extension
     * @param int $position
     *
     * @return string|null
     */
    public function getServerFromExtension(string $extension, int $position = 0)
    {
        $servers = $this->getServersFromExtension($extension);
        if ($servers === null) {
            return null;
        }

        // check if possible
        $isEnd = $position >= count($servers);
        $position = isset($servers[$position])
            ? $position
            : ($isEnd ? count($servers)-1 : 0);

        return $servers[$position];
    }

    public function getSubDomainFromExtension(string $extension)
    {
        if (trim($extension) == '') {
            throw new \InvalidArgumentException(
                'Extension could not be empty or white space only',
                E_NOTICE
            );
        }
        $extension = $this->encode(trim($extension));
        return !empty($this->availableExtensions[$extension])
            ? new ArrayCollector($this->availableExtensions[$extension])
            : null;
    }
}
