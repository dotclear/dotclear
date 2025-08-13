<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Util;

/**
 * @brief   WebAuthn DER encoder interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface DerEncoderInterface
{
    /**
     * Returns DER encoded EC2 key.
     *
     * @return  string
     */
    public static function encodeEC2(string $key_u2f): string;

    /**
     * Returns DER encoded EdDSA key.
     *
     * @return  string
     */
    public static function encodeOKP(string $key_x): string;

    /**
     * Returns DER encoded RSA key.
     *
     * @return  string
     */
    public static function encodeRSA(string $key_n, string $key_e): string;

}
