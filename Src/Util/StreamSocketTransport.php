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

use Exception;
use Pentagonal\WhoIs\Exceptions\ConnectionException;
use Pentagonal\WhoIs\Exceptions\ConnectionRefuseException;
use Pentagonal\WhoIs\Exceptions\HttpBadAddressException;
use Pentagonal\WhoIs\Exceptions\HttpException;
use Pentagonal\WhoIs\Exceptions\HttpExpiredException;
use Pentagonal\WhoIs\Exceptions\HttpPermissionException;
use Pentagonal\WhoIs\Exceptions\TimeOutException;
use RuntimeException;

/**
 * Class StreamSocketTransport
 * @package Pentagonal\WhoIs\Util
 *
 * - This class for internal use only.
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
     * Stored Stream object
     *
     * @var Stream
     */
    protected $stream;

    /**
     * Time out process & connection
     *
     * @var int
     */
    protected $timeout = 5;

    /**
     * Error Code if there was an error
     *
     * @var int
     */
    protected $errorCode;

    /**
     * Error Message if there was an error
     *
     * @var string
     */
    protected $errorMessage = '';

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
            $throwable = $this->determineErrorException($this->errorCode, HttpException::class);
            throw new $throwable(
                $this->errorMessage,
                $this->errorCode
            );
        }

        $this->stream = new Stream($socket);
    }

    /**
     * @param int $code
     * @param string $default
     * @return string
     */
    private function determineErrorException($code, $default = Exception::class)
    {
        switch ($code) {
            case SOCKET_ETIMEDOUT:
                return TimeOutException::class;
            case SOCKET_ETIME:
                return HttpExpiredException::class;
            case SOCKET_ECONNREFUSED:
                return ConnectionRefuseException::class;
            case SOCKET_EACCES:
                return HttpPermissionException::class;
            case SOCKET_EFAULT:
                return HttpBadAddressException::class;
            case SOCKET_EPROTONOSUPPORT:
            case SOCKET_EPROTO:
            case SOCKET_EPROTOTYPE:
                return ConnectionException::class;
            case SOCKET_EINVAL:
            case SOCKET_EINTR:
                return RuntimeException::class;
        }

        // default http exception
        return $default;
    }

    /**
     * Magic method for instance call @uses Stream
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \BadMethodCallException
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
     * Get Timeout set
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Get Error Code if there was an error
     *
     * @return int|null null or int 0 if there are no error
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get Error Message if there was an error
     *
     * @return string|null  null if there are no error
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Check if connection / process has an error
     *
     * @return bool true if there was error
     */
    public function isError()
    {
        return (bool) $this->getErrorCode();
    }

    /**
     * Get Stream Object
     *
     * @return Stream
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Magic Method if there was object (this) print into string
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->stream;
    }

    /**
     * Magic Method Destruct when object done
     */
    public function __destruct()
    {
        if (isset($this->stream)) {
            $this->stream->close();
            $this->stream = null;
        }
    }
}
