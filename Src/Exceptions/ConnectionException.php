<?php
namespace Pentagonal\WhoIs\Exceptions;

use GuzzleHttp\Exception\ConnectException;

/**
 * Class ConnectionException
 * @package Pentagonal\WhoIs\Exceptions
 */
class ConnectionException extends ConnectException
{
    /**
     * @param int $code
     */
    public function setCode(int $code)
    {
        $this->code = $code;
    }

    /**
     * @param string $file
     */
    public function setFile(string $file)
    {
        $this->file = $file;
    }

    /**
     * @param string $line
     */
    public function setLine(string $line)
    {
        $this->line = $line;
    }
}
