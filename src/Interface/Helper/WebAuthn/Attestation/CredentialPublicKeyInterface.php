<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Attestation;

/**
 * @brief   WebAuthn credential public key interface.
 *
 * from attested credential data from authenticator data.
 *
 * @see     https://www.w3.org/TR/webauthn/#sctn-authenticator-data
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface CredentialPublicKeyInterface
{
    /**
     * Create credential public key instance.
     *
     * from CBOR decoded value.
     *
     * @param   mixed[]     $enc    The data form CBOR
     */
    public function fromCbor(array $enc): void;

    /**
     * Get public key in PEM format.
     */
    public function getPem(): string;

    /**
     * Get public key in U2F format.
     */
    public function getU2F(): string;
}
