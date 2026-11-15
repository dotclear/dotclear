<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Data;

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
     * Challenge time to live in seconds.
     *
     * Must be greater than credentials timeout option.
     */
    public const CHALLENGE_TTL = 300;

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
        return is_null($data) || $data === '' ? '' : base64_encode($data);
    }

    public static function decodeValue(?string $data): string
    {
        // @phpstan-ignore function.strict
        return is_null($data) || $data === '' ? '' : base64_decode($data, false);
    }

    public static function encodeData(array $data): array
    {
        return array_map(fn ($v): string => is_scalar($v) ? base64_encode((string) $v) : '', $data);
    }

    public static function decodeData(array $data): array
    {
        // @phpstan-ignore function.strict
        return array_map(fn ($v): string => is_scalar($v) ? base64_decode((string) $v, false) : '', $data);
    }

    public function getType(): string
    {
        return 'webauthn';
    }

    public function setChallenge(ByteBufferInterface $challenge): void
    {
        // note: encode binary string for database session store.
        $_SESSION['webauthn_challenge']      = static::encodeValue($challenge->getBinaryString());
        $_SESSION['webauthn_challenge_time'] = time();
    }

    public function getChallenge(): ByteBufferInterface
    {
        $webauthn_challenge = isset($_SESSION['webauthn_challenge']) && is_string($webauthn_challenge = $_SESSION['webauthn_challenge']) ? $webauthn_challenge : '';
        $webauthn_time      = isset($_SESSION['webauthn_challenge_time']) && is_int($webauthn_time = $_SESSION['webauthn_challenge_time']) ? $webauthn_time : 0;

        return $webauthn_challenge !== '' && !static::isChallengeExpired($webauthn_time) ? $this->buffer->fromBinary(static::decodeValue($webauthn_challenge)) : $this->buffer->randomBuffer(32);
    }

    public function delChallenge(): void
    {
        unset($_SESSION['webauthn_challenge'], $_SESSION['webauthn_challenge_time']);
    }

    /**
     * Check if a challenge creation time is expired.
     *
     * @param   int     $time   The challenge creation timestamp
     *
     * @return  bool    True if expired
     */
    public static function isChallengeExpired(int $time): bool
    {
        return $time <= 0 || ($time + self::CHALLENGE_TTL) < time();
    }

    public function getRelyingParty(): RpOptionInterface
    {
        return $this->rp;
    }

    public function getUser(): UserOptionInterface
    {
        return $this->user;
    }

    public function setCredential(CredentialInterface $credential): void
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
