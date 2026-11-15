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
        if ($this->data === false) {
            return false;
        }

        // Token binding is not supported (and deprecated), reject client data that claims to use it.
        if (property_exists($this->data, 'tokenBinding')
            && $this->data->tokenBinding instanceof stdClass
            && property_exists($this->data->tokenBinding, 'status')
            && $this->data->tokenBinding->status === 'present'
        ) {
            return false;
        }

        return true;
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
            && is_string($this->data->challenge)
            && $this->buffer->fromBase64Url($this->data->challenge)->equals($this->store->getChallenge());
    }

    public function checkOrigin(): bool
    {
        if ($this->data === false || !property_exists($this->data, 'origin')) {
            return false;
        }

        // Reject requests made from a cross-origin iframe (C.crossOrigin), we never embed authentication.
        if (property_exists($this->data, 'crossOrigin') && $this->data->crossOrigin === true) {
            return false;
        }

        $origin = is_string($origin = $this->data->origin) ? $origin : '';
        $rpid   = strtolower(trim($this->store->getRelyingParty()->id(), '.'));
        if ($rpid === '' || $origin === '') {
            return false;
        }

        // Checks if the origin value contains a known android key hash
        if (str_starts_with($origin, 'android:apk-key-hash:')) {
            $parts = explode('android:apk-key-hash:', $origin);
            if (count($parts) !== 2) {
                return false;
            }

            return in_array($parts[1], $this->android_hashes, true);
        }

        // Check if the origin scheme is https
        if ($rpid !== 'localhost' && parse_url($origin, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        // extract host from origin
        $host = (string) parse_url($origin, PHP_URL_HOST);
        $host = strtolower(trim($host, '.'));

        // The RP ID must be equal to the origin's effective domain, or a registrable domain suffix of the origin's effective domain.
        //return preg_match('/' . preg_quote($rpid, '/') . '$/i', $host) === 1;

        // The origin host must be exactly the RP ID.
        // Even if browsers allow a subdomain to use a parent domain as RP ID,
        // we do not accept it as a subdomain may be controlled by someone else
        // (ie: multiblog platform with one blog by subdomain).
        return $host === $rpid;
    }

    public function addAndroidKeyHash(string $hash): void
    {
        $this->android_hashes[] = $hash;
    }
}
