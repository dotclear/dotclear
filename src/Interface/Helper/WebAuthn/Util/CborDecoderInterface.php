<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Util;

use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn CBOR decoder interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface CborDecoderInterface
{
    /**
     * Decode CBOR.
     *
     * @param   ByteBufferInterface|string  $data   The CBOR data
     *
     * @return  mixed   The decoded CBOR value
     */
    public function decode(ByteBufferInterface|string $data): mixed;

    /**
     * Partial decode CBOR.
     *
     * The end offset will be updated to the end of partial decoding.
     *
     * @param   ByteBufferInterface|string  $data   The CBOR data
     * @param   int                         $start  The start offset
     * @param   int                         $end    The end offset
     *
     * @return  mixed   The decoded CBOR value
     */
    public function decodeInPlace(ByteBufferInterface|string $data, int $start, int &$end = 0): mixed;

}
