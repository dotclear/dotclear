<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Data;

use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn store interface.
 *
 * WebAuthn store manage some binary strings, 
 * they must be encoded in order to be added to database.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface StoreInterface
{
    /**
     * Safe encode binary string.
     *
     * Required to encode binary data before adding it to database.
     *
     * @param   null|string     $data   The data to encode
     *
     * @return  string  Encoded data
     */
    public static function encodeValue(?string $data): string;

    /**
     * Safe decode binary string.
     *
     * Decode binary string encoded from js webauthn script or self::encodeValue().
     *
     * @param   null|string    $data   The encoded data
     *
     * @return  string  The decoded data
     */
    public static function decodeValue(?string $data): string;

    /**
     * Safe encode binary data.
     *
     * Required to encode binary data before adding them to database.
     *
     * @param   array<string, mixed>    $data   The data to encode
     *
     * @return  array<string, mixed>    The encoded data
     */
    public static function encodeData(array $data): array;

    /**
     * Safe decode binary data.
     *
     * Decode binary string encoded with self::encodeData().
     *
     * @param   array<string, mixed>    $data   The data to decode
     *
     * @return  array<string, mixed>    The decoded data
     */
    public static function decodeData(array $data): array;

    /**
     * Get webauthn type.
     *
     * For now it is always 'webauthn'.
     *
     * @return  string  The webauthn type
     */
    public function getType(): string;

    /**
     * Set webauthn challenge in session.
     *
     * @param   ByteBufferInterface     $challenge  The challenge instance
     */
    public function setChallenge(ByteBufferInterface $challenge): void;

    /**
     * Get webauthn challenge from session.
     *
     * @return  ByteBufferInterface     The challenge instance
     */
    public function getChallenge(): ByteBufferInterface;

    /**
     * Get relying party definition.
     *
     * @return  RpOptionInterface   The relying party instance
     */
    public function getRelyingParty(): RpOptionInterface;

    /**
     * Get (logged) user definition.
     *
     * @return  UserOptionInterface     The user instance
     */
    public function getUser(): UserOptionInterface;

    /**
     * Save user credentials
     *
     * @param   CredentialInterface     $credential     The user credentials
     */
    public function setCredential(CredentialInterface $credential): void;

    /**
     * Get users credentials.
     *
     * @param   string  $credential_id  The optional credential ID to search
     * @param   string  $user_id        The optional user ID to search
     *
     * @return  CredentialInterface[]
     */
    public function getCredentials(string $credential_id = '', string $user_id = ''): array;

    /**
     * Delete a credential.
     *
     * @param   string  $credential_id  The credential ID
     */
    public function delCredential(string $credential_id): void;

    /**
     * Set passkey providers list.
     *
     * @param   array<string,string>    $data   The passkey providers list
     */
    public function setProviders(array $data): void;

    /**
     * Get passkey providers list.
     *
     * @return    array<string,string>    $data   The passkey providers list
     */
    public function getProviders(): array;
}