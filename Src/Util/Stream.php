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
 */
class Stream
{
    const TYPE_READ = 'read';
    const TYPE_WRITE = 'write';

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var int
     */
    private $size;

    /**
     * @var bool
     */
    private $seekable;

    /**
     * @var bool
     */
    private $readable;

    /**
     * @var bool
     */
    private $writable;

    /**
     * @var array|mixed
     */
    private $uri;

    /**
     * @var array|mixed
     */
    private $customMetadata;

    /**
     * @var array
     * Collection Mode Of resource
     */
    private static $readWriteModes = [
        self::TYPE_READ => [
            'r', 'w+', 'r+', 'x+', 'c+',
            'rb', 'w+b', 'r+b', 'x+b',
            'c+b', 'rt', 'w+t', 'r+t',
            'x+t', 'c+t', 'a+'
        ],
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
     * @param $stream
     * @param array $options
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
     * @param string $name
     */
    public function __get($name)
    {
        if ($name == 'stream') {
            throw new \RuntimeException('The stream is detached');
        }

        throw new \BadMethodCallException('No value for ' . $name);
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return string
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
     * @return bool|string
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
     * @return null|resource
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
     * @return int|null
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
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * @return bool
     */
    public function eof()
    {
        return !$this->stream || feof($this->stream);
    }

    /**
     * @return bool|int
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
     * @param int $offset
     * @param int $whence
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
     * @param int $length
     *
     * @return bool|string
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
     * @param string $string
     *
     * @return bool|int
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
     * @param string $key
     *
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
