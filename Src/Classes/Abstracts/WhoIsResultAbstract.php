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

namespace Pentagonal\WhoIs\Abstracts;

use Pentagonal\WhoIs\Interfaces\RecordASNNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordDomainNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordHandleNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordIPNetworkInterface;
use Pentagonal\WhoIs\Interfaces\RecordNetworkInterface;
use Pentagonal\WhoIs\Interfaces\WhoIsNetworkResultInterface;
use Pentagonal\WhoIs\Record\Result\ASN;
use Pentagonal\WhoIs\Record\Result\Domain;
use Pentagonal\WhoIs\Record\Result\Handle;
use Pentagonal\WhoIs\Record\Result\IP;
use Pentagonal\WhoIs\Util\DataParser;

/**
 * Class WhoIsResultAbstract
 * @package Pentagonal\WhoIs\Abstracts
 */
abstract class WhoIsResultAbstract implements WhoIsNetworkResultInterface
{
    /**
     * @var bool
     */
    protected $hasParsed = false;

    /**
     * @var RecordResultAbstract
     */
    protected $resultData;

    /**
     * @var RecordNetworkInterface
     */
    protected $networkRecord;

    /**
     * @var string|null
     */
    protected $server;

    /**
     * @var string
     */
    protected $originalResult = '';

    /**
     * @var bool
     */
    private $isLimited;

    /**
     * @var bool doing parse data when unserialize called
     *           give value into true use parse data after unserialize
     */
    protected $parseWhenUnSerialize = true;

    /**
     * @var int flags options of json Bitmask consisting of
     *          JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP,
     *          JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT,
     *          JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT,
     *          JSON_UNESCAPED_UNICODE
     * @link http://php.net/manual/en/json.constants.php
     */
    protected $jsonEncodeFlags = JSON_PRETTY_PRINT;

    /**
     * WhoIsResult constructor.
     *
     * @param RecordNetworkInterface $network
     * @param string $originalString
     * @param string $server optional server as additional url data
     *                      if empty will be fallback to default
     *                      on parsing process
     */
    final public function __construct(
        RecordNetworkInterface $network,
        string $originalString,
        string $server = null
    ) {
        $this->hasParsed     = false;
        $this->setDefaultConstruct($network, $originalString, $server);
    }

    /**
     * Set Record for construct
     *
     * @param RecordNetworkInterface $network
     * @param string $originalString
     * @param string $server
     */
    protected function setDefaultConstruct(
        RecordNetworkInterface $network,
        string $originalString,
        string $server = null
    ) {
        if (! $network instanceof RecordDomainNetworkInterface
            && ! $network instanceof RecordIPNetworkInterface
            && ! $network instanceof RecordASNNetworkInterface
            && ! $network instanceof RecordHandleNetworkInterface
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Network Must be instance of : %1$s, %2$s, %3$s or %4$s',
                    RecordDomainNetworkInterface::class,
                    RecordIPNetworkInterface::class,
                    RecordASNNetworkInterface::class,
                    RecordHandleNetworkInterface::class
                )
            );
        }

        $this->networkRecord  = $network;
        $this->originalResult = $originalString;
        ($server && trim($server) !== '')
            && $this->server        = $server;
    }

    /* --------------------------------------------------------------------------------*
     |                              JSON SERIALIZABLE                                  |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Magic Method for @uses json_encode()
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /* --------------------------------------------------------------------------------*
     |                                ARRAY ACCESS                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Magic method @uses \ArrayAccess::offsetExists()
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return $this->getResultData()->offsetExists($offset);
    }

    /**
     * Magic method @uses \ArrayAccess::offsetGet()
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getResultData()->offsetGet($offset);
    }

    /**
     * Magic method @uses \ArrayAccess::offsetSet()
     *
     * @param string $offset
     * @param mixed $value
     * @final to prevent override
     */
    final public function offsetSet($offset, $value)
    {
        // no set please
    }

    /**
     * Magic method @uses \ArrayAccess::offsetUnset()
     *
     * @param string $offset
     * @final to prevent override
     */
    final public function offsetUnset($offset)
    {
        // no unset please
    }

    /* --------------------------------------------------------------------------------*
     |                                SERIALIZABLE                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @final to prevent override and make result consequent
     * @return string
     */
    final public function serialize() : string
    {
        return serialize([
            $this->networkRecord,
            $this->getOriginalResultString(),
            $this->getServer(),
        ]);
    }

    /**
     * Unserialize object value
     *
     * @param string $serialized
     * @final to prevent override and make result consequent
     * @throws \InvalidArgumentException
     */
    final public function unserialize($serialized)
    {
        $unSerialized = @unserialize($serialized);
        if (! is_array($unSerialized)) {
            throw new \InvalidArgumentException(
                'Invalid serialized value',
                E_WARNING
            );
        }

        // set construct
        call_user_func_array([$this, 'setDefaultConstruct'], $unSerialized);
        // set parsed as false
        $this->hasParsed  = false;
        // doing parse data
        if (!empty($this->parseWhenUnSerialize)) {
            $this->parseData();
        }
    }

    /* --------------------------------------------------------------------------------*
     |                                MAGIC METHOD                                     |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Magic Method to string
     * @return string
     */
    public function __toString() : string
    {

        $flag = ! $this->jsonEncodeFlags || !is_int($this->jsonEncodeFlags)
            ? 0
            : $this->jsonEncodeFlags;
        // call parse data
        return $this->toJson($flag);
    }

    /* --------------------------------------------------------------------------------*
     |                                   GETTERS                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * @return RecordNetworkInterface
     */
    public function getNetworkRecord(): RecordNetworkInterface
    {
        return $this->networkRecord;
    }

    /**
     * Get Domain Name
     *
     * @return string
     */
    public function getPointer() : string
    {
        return $this->networkRecord->getPointer();
    }

    /**
     * @return string
     */
    public function getOriginalResultString() : string
    {
        return $this->originalResult;
    }

    /**
     * Get Array Detail data that convert into RecordResultAbstract
     *
     * @final to prevent override and make result consequent
     * @return RecordResultAbstract
     */
    final public function getResultData() : RecordResultAbstract
    {
        return $this->parseData()->resultData;
    }

    /**
     * Returning Detail into array
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->getResultData()->toArray();
    }

    /**
     * Returning Detail into array
     *
     * @param int $flags flags options of json Bitmask consisting of
     *                   JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP,
     *                   JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT,
     *                   JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT,
     *                   JSON_UNESCAPED_UNICODE
     * @link http://php.net/manual/en/json.constants.php
     *
     * @return string
     */
    public function toJson(int $flags = 0) : string
    {
        return json_encode(
            $this,
            $flags
        );
    }

    /**
     * Get Registered Status
     *
     * @uses DataParser::STATUS_UNKNOWN
     * @uses DataParser::STATUS_UNREGISTERED
     * @uses DataParser::STATUS_REGISTERED
     * @uses DataParser::STATUS_RESERVED
     * @uses DataParser::STATUS_LIMIT
     *
     * @return bool|string
     */
    public function getRegisteredStatus()
    {
        return DataParser::getRegisteredDomainStatus($this->getOriginalResultString());
    }

    /* --------------------------------------------------------------------------------*
     |                                   UTILITY                                       |
     |---------------------------------------------------------------------------------|
     */

    /**
     * Parsing data
     *
     * @access internal
     * @final for fallback parser
     *
     * @throws \Throwable
     */
    final public function parseData() : WhoIsResultAbstract
    {
        // if has parsed stop
        if ($this->hasParsed === true && $this->resultData instanceof RecordResultAbstract) {
            return $this;
        }

        // set has parsed
        // behaviour to make it sure put has parse on before create array collector
        $this->hasParsed  = true;
        $this->resultData = $this->parseResult();
        return $this;
    }

    /**
     * Get Server
     *
     * @return string|null
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Check if type is IP
     *
     * @return bool
     */
    public function isIP() : bool
    {
        return $this->getNetworkRecord() instanceof RecordIPNetworkInterface;
    }

    /**
     * Check if type is IP
     *
     * @return bool
     */
    public function isHandle() : bool
    {
        return $this->getNetworkRecord() instanceof RecordHandleNetworkInterface;
    }

    /**
     * Check if type is ASN
     *
     * @return bool
     */
    public function isASN() : bool
    {
        return $this->getNetworkRecord() instanceof RecordASNNetworkInterface;
    }

    /**
     * Check if type is Domain
     *
     * @return bool
     */
    public function isDomain() : bool
    {
        return $this->getNetworkRecord() instanceof RecordDomainNetworkInterface;
    }

    /**
     * Check if result is Limited
     *
     * @return bool
     */
    final public function isLimited() : bool
    {
        if (!isset($this->isLimited)) {
            $original = $this->getOriginalResultString();
            $this->isLimited = $original && DataParser::hasContainLimitedResultData($original);
        }

        return $this->isLimited;
    }

    /**
     * Parse Detail
     *
     * @return RecordResultAbstract
     */
    protected function parseResult() : RecordResultAbstract
    {
        if ($this->networkRecord instanceof RecordDomainNetworkInterface) {
            return Domain::fromResult($this);
        }
        if ($this->networkRecord instanceof RecordASNNetworkInterface) {
            return ASN::fromResult($this);
        }
        if ($this->networkRecord instanceof RecordIPNetworkInterface) {
            return IP::fromResult($this);
        }
        if ($this->networkRecord instanceof RecordHandleNetworkInterface) {
            return Handle::fromResult($this);
        }

        throw new \RuntimeException(
            'Could not determine record network type',
            E_COMPILE_WARNING
        );
    }
}
