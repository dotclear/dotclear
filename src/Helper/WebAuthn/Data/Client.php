<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Data;

use Dotclear\Interface\Helper\WebAuthn\Data\ClientInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\StoreInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use stdClass;

/**
 * @brief   WebAuthn client data helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Client implements ClientInterface
{
    /**
     * Client data from response.
     */
    private false|stdClass $data = false;

    /**
     * Client data hash.
     */
    private string $hash = '';

    /**
     * Android APK key hashes.
     *
     * @var     string[]    $android_hashes
     */
    private array $android_hashes = [];

    /**
     * Load services from container.
     *
     * @param   StoreInterface          $store      The store instance
     * @param   ByteBufferInterface     $buffer     The byte buffer interface
     */
    public function __construct(
        protected StoreInterface $store,
        protected ByteBufferInterface $buffer
    ) {
    }

    public function fromResponse(string $binary): void
    {
        // raz
        $this->data = false;

        $data = json_decode($binary);
        if ($data instanceof stdClass) {
            $this->hash = hash('sha256', $binary, true);
            $this->data = $data;
        }
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function checkObject(): bool
    {
        return $this->data !== false;
    }

    public function checkCreate(): bool
    {
        return $this->data !== false
            && property_exists($this->data, 'type')
            && $this->data->type === 'webauthn.create';
    }

    public function checkGet(): bool
    {
        return $this->data !== false
            && property_exists($this->data, 'type')
            && $this->data->type === 'webauthn.get';
    }

    public function checkChallenge(): bool
    {
        return $this->data !== false
            && property_exists($this->data, 'challenge')
            && $this->buffer->fromBase64Url($this->data->challenge)->equals($this->store->getChallenge());
    }

    public function checkOrigin(): bool
    {
        if ($this->data === false || !property_exists($this->data, 'origin')) {
            return false;
        }

        $origin = $this->data->origin;
        $rpid   = $this->store->getRelyingParty()->id();

        // Checks if the origin value contains a known android key hash
        if (str_starts_with((string) $origin, 'android:apk-key-hash:')) {
            $parts = explode('android:apk-key-hash:', (string) $origin);
            if (count($parts) !== 2) {
                return false;
            }

            return in_array($parts[1], $this->android_hashes, true);
        }

        // Check if the origin scheme is https
        if ($rpid !== 'localhost' && parse_url((string) $origin, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        // extract host from origin
        $host = (string) parse_url((string) $origin, PHP_URL_HOST);
        $host = trim($host, '.');

        // The RP ID must be equal to the origin's effective domain, or a registrable domain suffix of the origin's effective domain.
        return preg_match('/' . preg_quote($rpid, '/') . '$/i', $host) === 1;
    }

    public function addAndroidKeyHash(string $hash): void
    {
        $this->android_hashes[] = $hash;
    }
}
