<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Util;

use Dotclear\Helper\WebAuthn\Exception\CborException;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn CBOR decoder.
 *
 * Modified version from Thomas Bleeker under MIT license :
 * https://github.com/madwizard-thomas/webauthn-server/blob/master/src/Format/CborDecoder.php
 * Modified version from Lukas Buchs under MIT license :
 * https://github.com/lbuchs/WebAuthn/blob/master/src/CBOR/CborDecoder.php
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class CborDecoder implements CborDecoderInterface
{
    public const MAJOR_UNSIGNED_INT = 0;
    public const MAJOR_TEXT_STRING  = 3;
    public const MAJOR_FLOAT_SIMPLE = 7;
    public const MAJOR_NEGATIVE_INT = 1;
    public const MAJOR_ARRAY        = 4;
    public const MAJOR_TAG          = 6;
    public const MAJOR_MAP          = 5;
    public const MAJOR_BYTE_STRING  = 2;

    /**
     * Load services from container.
     *
     * @param   ByteBufferInterface     $buffer     The byte buffer interface
     */
    public function __construct(
        protected ByteBufferInterface $buffer
    ) {

    }

    public function decode(ByteBufferInterface|string $data): mixed
    {
        $buffer = $data instanceof ByteBufferInterface ? $data : $this->buffer->fromBinary($data);

        $offset = 0;
        $result = $this->_parseItem($buffer, $offset);
        if ($offset !== $buffer->getLength()) {
            throw new CborException('Unused bytes after data item.');
        }

        return $result;
    }

    public function decodeInPlace(ByteBufferInterface|string $data, int $start, int &$end = 0): mixed
    {
        $buffer = $data instanceof ByteBufferInterface ? $data : $this->buffer->frombinary($data);

        $offset = $start;
        $data   = $this->_parseItem($buffer, $offset);
        $end    = $offset;

        return $data;
    }

    /**
     * @param   ByteBufferInterface     $buf
     * @param   int                     $offset
     *
     * @return  mixed
     */
    protected function _parseItem(ByteBufferInterface $buf, int &$offset): mixed
    {
        $first = $buf->getByteVal($offset++);
        $type  = $first >> 5;
        $val   = $first & 0b11111;

        if ($type === static::MAJOR_FLOAT_SIMPLE) {
            return $this->_parseFloatSimple($val, $buf, $offset);
        }

        $val = $this->_parseExtraLength($val, $buf, $offset);

        return $this->_parseItemData($type, $val, $buf, $offset);
    }

    /**
     * @param   int                     $val
     * @param   ByteBufferInterface     $buf
     * @param   int                     $offset
     *
     * @return  mixed
     *
     * @throws  CborException
     */
    protected function _parseFloatSimple(int $val, ByteBufferInterface $buf, int &$offset): mixed
    {
        switch ($val) {
            case 24:
                $val = $buf->getByteVal($offset);
                $offset++;
                return $this->_parseSimple($val);

            case 25:
                $floatValue = $buf->getHalfFloatVal($offset);
                $offset += 2;
                return $floatValue;

            case 26:
                $floatValue = $buf->getFloatVal($offset);
                $offset += 4;
                return $floatValue;

            case 27:
                $floatValue = $buf->getDoubleVal($offset);
                $offset += 8;
                return $floatValue;

            case 28:
            case 29:
            case 30:
                throw new CborException('Reserved value used.');

            case 31:
                throw new CborException('Indefinite length is not supported.');
        }

        return $this->_parseSimple($val);
    }

    /**
     * @param   int     $val
     *
     * @return  mixed
     *
     * @throws  CborException
     */
    protected function _parseSimple(int $val): mixed
    {
        if ($val === 20) {
            return false;
        }
        if ($val === 21) {
            return true;
        }
        if ($val === 22) {
            return null;
        }
        throw new CborException(sprintf('Unsupported simple value %d.', $val));
    }

    /**
     * @param   int                     $val
     * @param   ByteBufferInterface     $buf
     * @param   int                     $offset
     *
     * @return  mixed
     *
     * @throws  CborException
     */
    protected function _parseExtraLength(int $val, ByteBufferInterface $buf, int &$offset): mixed
    {
        switch ($val) {
            case 24:
                $val = $buf->getByteVal($offset);
                $offset++;
                break;

            case 25:
                $val = $buf->getUint16Val($offset);
                $offset += 2;
                break;

            case 26:
                $val = $buf->getUint32Val($offset);
                $offset += 4;
                break;

            case 27:
                $val = $buf->getUint64Val($offset);
                $offset += 8;
                break;

            case 28:
            case 29:
            case 30:
                throw new CborException('Reserved value used.');

            case 31:
                throw new CborException('Indefinite length is not supported.');
        }

        return $val;
    }

    /**
     * @param   int                     $type
     * @param   int                     $val
     * @param   ByteBufferInterface     $buf
     * @param   int                     $offset
     *
     * @return  mixed
     *
     * @throws  CborException
     */
    protected function _parseItemData(int $type, int $val, ByteBufferInterface $buf, int &$offset): mixed
    {
        switch ($type) {
            case static::MAJOR_UNSIGNED_INT: // uint
                return $val;

            case static::MAJOR_NEGATIVE_INT:
                return -1 - $val;

            case static::MAJOR_BYTE_STRING:
                $data = $buf->getBytes($offset, $val);
                $offset += $val;
                return $this->buffer->fromBinary($data); // bytes

            case static::MAJOR_TEXT_STRING:
                $data = $buf->getBytes($offset, $val);
                $offset += $val;
                return $data; // UTF-8

            case static::MAJOR_ARRAY:
                return $this->_parseArray($buf, $offset, $val);

            case static::MAJOR_MAP:
                return $this->_parseMap($buf, $offset, $val);

            case static::MAJOR_TAG:
                return $this->_parseItem($buf, $offset); // 1 embedded data item
        }

        // This should never be reached
        throw new CborException(sprintf('Unknown major type %d.', $type));
    }

    /**
     * @param   ByteBufferInterface     $buf
     * @param   int                     $offset
     * @param   int                     $count
     *
     * @return  mixed
     *
     * @throws  CborException
     */
    protected function _parseMap(ByteBufferInterface $buf, int &$offset, int $count): mixed
    {
        $map = array();

        for ($i = 0; $i < $count; $i++) {
            $mapKey = $this->_parseItem($buf, $offset);
            $mapVal = $this->_parseItem($buf, $offset);

            if (!\is_int($mapKey) && !\is_string($mapKey)) {
                throw new CborException('Can only use strings or integers as map keys');
            }

            $map[$mapKey] = $mapVal; // todo dup
        }

        return $map;
    }

    /**
     * @param   ByteBufferInterface     $buf
     * @param   int                     $offset
     * @param   int                     $count
     *
     * @return  mixed
     */
    protected function _parseArray(ByteBufferInterface $buf, int &$offset, int $count): mixed
    {
        $arr = array();
        for ($i = 0; $i < $count; $i++) {
            $arr[] = $this->_parseItem($buf, $offset);
        }

        return $arr;
    }
}
