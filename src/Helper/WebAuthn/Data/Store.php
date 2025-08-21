<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Data;

use Dotclear\Helper\WebAuthn\Exception\StoreException;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn store class.
 *
 * This class MUST be overloaded through WebAuthn container.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class Store implements StoreInterface
{
    /**
     * Load services from container.
     *
     * @param   ByteBufferInterface     $buffer     The byte buffer interface
     * @param   RpOptionInterface       $rp         The relying party option instance
     * @param   UserOptionInterface     $user       The user option instance
     */
    public function __construct(
        protected ByteBufferInterface $buffer,
        protected RpOptionInterface $rp,
        protected UserOptionInterface $user
    ) {
    }

    public static function encodeValue(?string $data): string
    {
        return empty($data) ? '' : base64_encode((string) $data);
    }

    public static function decodeValue(?string $data): string
    {
        return empty($data) ? '' : base64_decode((string) $data, false);
    }

    public static function encodeData(array $data): array
    {
        return array_map(fn ($v): string => base64_encode((string) $v), $data);
    }

    public static function decodeData(array $data): array
    {
        return array_map(fn ($v): string => base64_decode((string) $v, false), $data);
    }

    public function getType(): string
    {
        return 'webauthn';
    }

    public function setChallenge(ByteBufferInterface $challenge): void
    {
        // note: encode binary string for database session store.
        $_SESSION['webauthn_challenge'] = $this->encodeValue($challenge->getBinaryString());
    }

    public function getChallenge(): ByteBufferInterface
    {
        return isset($_SESSION['webauthn_challenge']) ? $this->buffer->fromBinary($this->decodeValue($_SESSION['webauthn_challenge'])) : $this->buffer->randomBuffer(32);
    }

    public function getRelyingParty(): RpOptionInterface
    {
        return $this->rp;
    }

    public function getUser(): UserOptionInterface
    {
        return $this->user;
    }

    public function setCredential(CredentialInterface $data): void
    {
    }

    public function getCredentials(string $credential_id = '', string $user_id = ''): array
    {
        return [];
    }

    public function delCredential(string $credential_id): void
    {
    }

    public function setProviders(array $data): void
    {
    }

    public function getProviders(): array
    {
        return [];
    }
}
