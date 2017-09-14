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
 * Class Uri
 * @package Pentagonal\WhoIs\Util
 * Just like Implementing PSR7 UriInterface
 */
class Uri
{
    /**
     * Default Port List For Available Uri
     *
     * @var array
     */
    private static $defaultPorts = [
        ''      => 80,
        'http'  => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    /**
     * Base Scheme
     *
     * @var string
     */
    protected $scheme = '';

    /**
     * Stored Auth User Uri
     *
     * @var string
     */
    protected $user = '';

    /**
     * Stored Auth Password Uri
     * @var string
     */
    protected $password = '';

    /**
     * Stored Uri Host
     *
     * @var string
     */
    protected $host = '';

    /**
     * Stored Uri Port
     *
     * @var int|null
     */
    protected $port;

    /**
     * Base Path Of Uri
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Stored Uri Path
     *
     * @var string
     */
    protected $path = '';

    /**
     * Stored Query String
     *
     * @var string
     */
    protected $query = '';

    /**
     * Stored Fragment Uri
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * Uri constructor.
     * @param string $scheme    uri scheme (eg: http)
     * @param string $host      uri host (eg: example.com)
     * @param int|null $port    uri port (eg: 8080)
     * @param string $path      uri path (eg: /path/)
     * @param string $query     uri query (eg: query=1&query_2=2)
     * @param string $fragment  uri fragment (eg: fragment)
     * @param string $user      uri user auth
     * @param string $password  uri password auth for user
     */
    public function __construct(
        $scheme,
        $host,
        $port = null,
        $path = '/',
        $query = '',
        $fragment = '',
        $user = '',
        $password = ''
    ) {
        $this->scheme = $this->filterScheme($scheme);
        $this->host = $host;
        $this->port = $this->filterPort($port);
        $this->path = empty($path) ? '/' : $this->filterPath($path);
        $this->query = $this->filterQuery($query);
        $this->fragment = $this->filterQuery($fragment);
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Create generate Object @uses Uri from uri string
     *
     * @param string $uri
     * @return Uri
     */
    public static function createFromString($uri)
    {
        if (!is_string($uri) && !method_exists($uri, '__toString')) {
            throw new InvalidArgumentException('Uri must be a string');
        }

        $parts = parse_url($uri);
        return new static(
            (isset($parts['scheme']) ? $parts['scheme'] : ''),
            (isset($parts['host']) ? $parts['host'] : ''),
            (isset($parts['port']) ? $parts['port'] : null),
            (isset($parts['path']) ? $parts['path'] : ''),
            (isset($parts['query']) ? $parts['query'] : ''),
            (isset($parts['fragment']) ? $parts['fragment'] : ''),
            (isset($parts['user']) ? $parts['user'] : ''),
            (isset($parts['pass']) ? $parts['pass'] : '')
        );
    }

    /**
     * Get Scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Create new cloned object by scheme
     *
     * @param string $scheme
     *
     * @return Uri Cloned Uri with new Scheme
     */
    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($scheme);
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * Filter Uri scheme.
     *
     * @param  string $scheme Raw Uri scheme.
     * @return string
     *
     * @throws InvalidArgumentException If the Uri scheme is not a string.
     */
    protected function filterScheme($scheme)
    {
        if (!is_string($scheme) && !method_exists($scheme, '__toString')) {
            throw new InvalidArgumentException('Uri scheme must be a string');
        }

        $scheme = str_replace('://', '', strtolower((string)$scheme));
        if (!isset(self::$defaultPorts[$scheme])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Uri scheme must be one of: %s.',
                    '"'.implode('", "', array_keys(self::$defaultPorts)).'"'
                )
            );
        }

        return $scheme;
    }

    /**
     * Get Authority Details
     *
     * @return string
     */
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();

        return ($userInfo ? $userInfo . '@' : '') . $host . ($port !== null ? ':' . $port : '');
    }

    /**
     * Get User Info Details
     *
     * @return string
     */
    public function getUserInfo()
    {
        return $this->user . ($this->password ? ':' . $this->password : '');
    }

    /**
     * @param string $user
     * @param string $password
     *
     * @return Uri
     */
    public function withUserInfo($user, $password = null)
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password ? $password : '';

        return $clone;
    }

    /**
     * Get Host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Create new cloned object by host
     *
     * @param string $host
     *
     * @return Uri
     */
    public function withHost($host)
    {
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Get port if available
     *
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Create new cloned object by port
     *
     * @param int $port
     *
     * @return Uri
     */
    public function withPort($port)
    {
        $port = $this->filterPort($port);
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Filter Uri port.
     *
     * @param  null|int $port The Uri port number.
     * @return null|int
     *
     * @throws InvalidArgumentException If the port is invalid.
     */
    protected function filterPort($port)
    {
        if (is_null($port) || (is_integer($port) && ($port >= 1 && $port <= 65535))) {
            if ($port === null) {
                if (isset(self::$defaultPorts[$this->getScheme()])) {
                    $port = self::$defaultPorts[$this->getScheme()];
                }
            }
            return $port;
        }

        throw new InvalidArgumentException('Uri port must be null or an integer between 1 and 65535 (inclusive)');
    }

    /**
     * Get Uri Path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return Uri
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Uri path must be a string');
        }

        $clone = clone $this;
        $clone->path = $this->filterPath($path);

        // if the path is absolute, then clear basePath
        if (substr($path, 0, 1) == '/') {
            $clone->basePath = '';
        }

        return $clone;
    }

    /**
     * Filter Uri path.
     *
     *
     * @param  string $path The raw uri path.
     * @return string       The RFC 3986 percent-encoded uri path.
     * @link   http://www.faqs.org/rfcs/rfc3986.html
     */
    protected function filterPath($path)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     *
     * @return Uri
     */
    public function withQuery($query)
    {
        if (!is_string($query) && !method_exists($query, '__toString')) {
            throw new InvalidArgumentException('Uri query must be a string');
        }
        $query = ltrim((string)$query, '?');
        $clone = clone $this;
        $clone->query = $this->filterQuery($query);

        return $clone;
    }

    /**
     * Filters the query string or fragment of a URI.
     *
     * @param string $query The raw uri query string.
     * @return string The percent-encoded query string.
     */
    protected function filterQuery($query)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        );
    }

    /**
     * Get fragment from uri eg: http://example.uri/path/#this-fragment
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Create cloned object Uri with new fragment instead
     *
     * @param string $fragment  fragment to add
     *
     * @return Uri
     */
    public function withFragment($fragment)
    {
        if (!is_string($fragment) && !method_exists($fragment, '__toString')) {
            throw new InvalidArgumentException('Uri fragment must be a string');
        }
        $fragment = ltrim((string)$fragment, '#');
        $clone = clone $this;
        $clone->fragment = $this->filterQuery($fragment);

        return $clone;
    }

    /**
     * Magic Method if there was object (this) print into string
     *
     * @return string
     */
    public function __toString()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $basePath = $this->basePath;
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        $path = $basePath . '/' . ltrim($path, '/');

        return ($scheme ? $scheme . ':' : '')
               . ($authority ? '//' . $authority : '')
               . $path
               . ($query ? '?' . $query : '')
               . ($fragment ? '#' . $fragment : '');
    }
}
