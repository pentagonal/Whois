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
 * Class Stream
 * @package Pentagonal\Whois\Util
 *
 * Stream Handler for WhoIs usage & data getter,
 * this property only used for internal only but you can implement on other application
 * if you prefer.
 */
class Stream
{
    const TYPE_READ = 'read';
    const TYPE_WRITE = 'write';

    /**
     * Stream resource data property
     *
     * @var resource
     */
    private $stream;

    /**
     * Size of stream
     *
     * @var int
     */
    private $size;

    /**
     * Stored property if data is seekable
     *
     * @var bool
     */
    private $seekable;

    /**
     * Stored property if data is readable
     *
     * @var bool
     */
    private $readable;

    /**
     * Stored property if data is writable
     *
     * @var bool
     */
    private $writable;

    /**
     * Stored uri metadata
     *
     * @var array|mixed
     */
    private $uri;

    /**
     * Stored Custom Metadata
     *
     * @var array|mixed
     */
    private $customMetadata;

    /**
     * @var array
     * Collection Mode Of resource
     */
    private static $readWriteModes = [
        /**
         * Readable Mode
         */
        self::TYPE_READ => [
            'r', 'w+', 'r+', 'x+', 'c+',
            'rb', 'w+b', 'r+b', 'x+b',
            'c+b', 'rt', 'w+t', 'r+t',
            'x+t', 'c+t', 'a+'
        ],
        /**
         * Writable Mode
         */
        self::TYPE_WRITE => [
            'w', 'w+', 'rw', 'r+', 'x+',
            'c+', 'wb', 'w+b', 'r+b',
            'x+b', 'c+b', 'w+t', 'r+t',
            'x+t', 'c+t', 'a', 'a+'
        ]
    ];

    /**
     * Stream constructor.
     *
     * @param resource $stream  stream resource (eg `fopen`)
     * @param array $options    options configuration
     */
    public function __construct($stream, $options = [])
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Stream must be a resource, %s given',
                    gettype($stream)
                )
            );
        }

        if (isset($options['size'])) {
            $this->size = $options['size'];
        }

        $this->customMetadata = isset($options['metadata'])
            ? $options['metadata']
            : [];

        $this->stream   = $stream;
        $meta           = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'];
        $this->writable = in_array($meta['mode'], self::$readWriteModes[self::TYPE_WRITE]);
        $this->readable = in_array($meta['mode'], self::$readWriteModes[self::TYPE_READ]);
        $this->uri      = $this->getMetadata('uri');
    }

    /**
     * Magic Method for backward compatibility
     *
     * @param string $name  property name
     * @throws \RuntimeException
     * @throws \BadMethodCallException
     */
    public function __get($name)
    {
        if ($name == 'stream') {
            throw new \RuntimeException('The stream is detached');
        }

        throw new \BadMethodCallException('No value for ' . $name);
    }

    /**
     * Magic Method that to Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Magic Method if there was object (this) print into string
     *
     * @return string content data
     */
    public function __toString()
    {
        try {
            $this->seek(0);
            return (string) stream_get_contents($this->stream);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Getting contents from stream
     *
     * @return bool|string  returning string if has content bool false if failed
     */
    public function getContents()
    {
        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * Close Stream Connection
     */
    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }

            $this->detach();
        }
    }

    /**
     * Detach stream from object resource
     *
     * @return null|resource    returning null if stream has not been attached yet otherwise resource
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size =
        $this->uri = null;
        $this->readable =
        $this->writable =
        $this->seekable = false;

        return $result;
    }

    /**
     * Get Size of content buffers
     *
     * @return int|null     returning integer byte size otherwise null if stream has not been attached yet
     */
    public function getSize()
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        // Clear the stat cache if the stream has a URI
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    /**
     * Check if stream is readable
     *
     * @return bool     true if stream readable
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * Check if stream is writable
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Check if stream is seekable
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * Check if stream is end of file / end resource line
     *
     * @return bool
     */
    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * Tell the stream
     *
     * @return bool|int     bool false if failed otherwise integer
     * @throws \RuntimeException
     */
    public function tell()
    {
        $result = ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * Rewind resource
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Seek data by offset
     *
     * @param int $offset
     * @param int $whence
     * @throws \RuntimeException
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to seek to stream position %s with whence ' . var_export($whence, true),
                    $offset
                )
            );
        }
    }

    /**
     * Read data from stream
     *
     * @param int $length   byte size to read
     * @return bool|string  boolean false if no content (end of) and string if there are exists
     * @throws \RuntimeException
     */
    public function read($length)
    {
        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        if ($length < 0) {
            throw new \RuntimeException('Length parameter cannot be negative');
        }

        if (0 === $length) {
            return '';
        }

        $string = fread($this->stream, $length);
        if (false === $string) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $string;
    }

    /**
     * Write data into stream
     *
     * @param string $string    data to put
     * @return bool|int         integer size of written into stream
     * @throws \RuntimeException
     */
    public function write($string)
    {
        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        // We can't know the size after writing anything
        $this->size = null;
        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    /**
     * Get metadata from stream
     *
     * @param string $key   key of metadata
     * @return array|mixed|null
     */
    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        } elseif (!$key) {
            return $this->customMetadata + stream_get_meta_data($this->stream);
        } elseif (isset($this->customMetadata[$key])) {
            return $this->customMetadata[$key];
        }

        $meta = stream_get_meta_data($this->stream);

        return isset($meta[$key]) ? $meta[$key] : null;
    }
}
