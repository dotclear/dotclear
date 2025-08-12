<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Attestation;

use Dotclear\Helper\WebAuthn\Exception\AuthenticatorException;
use Dotclear\Interface\Helper\WebAuthn\Attestation\CredentialPublicKeyInterface;
use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestedCredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;

/**
 * @brief   WebAuthn attested credential data helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class AttestedCredential implements AttestedCredentialInterface
{

    protected string $binary;

    /**
     * The Authenticator Attestation Globally Unique Identifier.
     *
     * @var  string     $aaguid
     */
    protected string $aaguid = '';

    /**
     * The credential Id.
     *
     * @var  string     $credential_id
     */
    protected string $credential_id = '';

    /**
     * The public credential key instance.
     *
     * @var  CredentialPublicKeyInterface   $credential_public_key
     */
    //protected CredentialPublicKeyInterface $credential_public_key;

    /**
     * The current binary offset.
     *
     * @var     int     $offset
     */
    protected int $offset = 55;

    /**
     * Load services from container.
     *
     * @param   CredentialPublicKeyInterface    $authenticator  The credntial public key instance
     * @param   CborDecoderInterface            $cbor           The Cbor interface
     */
    public function __construct(
        protected CredentialPublicKeyInterface $credential_public_key,
        protected CborDecoderInterface $cbor
    ) {

    }

    /**
     * @param   string  $binary     The attested credential data binary
     */
    public function fromBinary(string $binary): void
    {
        $this->binary = $binary;
        $this->offset = 55;

        if (strlen($this->binary) <= 55) {
            throw new AuthenticatorException('Attested data should be present but is missing');
        }

        // The aaguid may be 0 if the user is using a old u2f device and/or if the browser is using the fido-u2f format.
        $this->aaguid = substr($this->binary, 37, 16);

        // Byte length L of Credential ID, 16-bit unsigned big-endian integer.
        $length = unpack('nlength', substr($this->binary, 53, 2));
        $length = $length['length'] ?? 0;
        $this->credential_id = substr($this->binary, 55, $length);

        // set end offset
        $this->offset += (int) $length;

        $enc = $this->cbor->decodeInPlace($this->binary, $this->offset, $this->offset);

        //$this->credential_public_key = new CredentialPublicKey();
        $this->credential_public_key->fromCbor($enc);
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getAAGUID(): string
    {
        return $this->aaguid;
    }

    public function getCredentialID(): string
    {
        return $this->credential_id;
    }

    public function getCredentialPublicKey(): CredentialPublicKeyInterface
    {
        return $this->credential_public_key;
    }
}
