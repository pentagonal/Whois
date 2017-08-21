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

/**
 * Class StreamSocketTransport
 * @package Pentagonal\WhoIs\Util
 *
 * @method void close();
 * @method resource|null detach();
 * @method int getSize();
 * @method int tell();
 * @method bool eof();
 * @method bool isSeekable();
 * @method void seek($offset, $whence = SEEK_SET);
 * @method void rewind();
 * @method bool isWritable();
 * @method bool|int write(string $string);
 * @method bool isReadable();
 * @method string read(int $length);
 * @method string getContents();
 * @method mixed getMetadata(string $key = null);
 */
class StreamSocketTransport
{
    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var int
     */
    protected $timeout = 5;

    /**
     * @var int
     */
    protected $errorCode;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * SocketTransport constructor.
     * @param string|resource $uri
     * @param int $timeout
     * @throws \Exception
     */
    public function __construct($uri, $timeout = 5)
    {
        $transport = Uri::createFromString($uri);
        $this->timeout = $timeout;
        $socket = @fsockopen(
            $transport->getHost(),
            $transport->getPort(),
            $this->errorCode,
            $this->errorMessage,
            $this->timeout
        );
        if (!$socket) {
            throw new \Exception(
                $this->errorMessage,
                $this->errorCode
            );
        }

        $this->stream = new Stream($socket);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Arguments method must be as a string %s given',
                    gettype($name)
                )
            );
        }
        if ($this->stream) {
            return call_user_func_array([$this->stream, $name], $arguments);
        }

        throw new \BadMethodCallException(
            sprintf("Call to undefined method %s.", $name),
            E_USER_ERROR
        );
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return (bool) $this->getErrorCode();
    }

    /**
     * @return Stream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->stream;
    }

    /**
     * Destruct
     */
    public function __destruct()
    {
        if (isset($this->stream)) {
            $this->stream->close();
            $this->stream = null;
        }
    }
}
