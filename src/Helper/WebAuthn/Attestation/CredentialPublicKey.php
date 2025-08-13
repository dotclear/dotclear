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
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\CborDecoderInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\DerEncoderInterface;

/**
 * @brief   WebAuthn credential public key instance.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class CredentialPublicKey implements CredentialPublicKeyInterface
{
    // Cose encoded keys
    public const COSE_KTY = 1;
    public const COSE_ALG = 3;

    // Cose curve
    public const COSE_CRV = -1;
    public const COSE_X   = -2;
    public const COSE_Y   = -3;

    // Cose RSA PS256
    public const COSE_N = -1;
    public const COSE_E = -2;

    // EC2 key type
    public const EC2_TYPE  = 2;
    public const EC2_ES256 = -7;
    public const EC2_P256  = 1;

    // RSA key type
    public const RSA_TYPE  = 3;
    public const RSA_RS256 = -257;

    // OKP key type
    public const OKP_TYPE    = 1;
    public const OKP_ED25519 = 6;
    public const OKP_EDDSA   = -8;

    protected int $kty = 0;
    protected int $alg = 0;
    protected int $crv = 0;
    protected string $x;
    protected string $y;
    protected string $n;
    protected string $e;

    /**
     * The data form CBOR.
     *
     * @var     mixed[]     $enc
     */
    protected array $enc;

    /**
     * Load services from container.
     *
     * @param   DerEncoderInterface     $der    The DER decoder interface
     * @param   CborDecoderInterface    $cbor   The CBOR interface
     */
    public function __construct(
        protected CborDecoderInterface $cbor,
        protected DerEncoderInterface $der,
    ) {
    }

    public function fromCbor(array $enc): void
    {
        $this->enc = $enc;
        $this->kty = $this->enc[self::COSE_KTY];
        $this->alg = $this->enc[self::COSE_ALG];

        switch ($this->alg) {
            case self::EC2_ES256: $this->_readES256();

                break;
            case self::RSA_RS256: $this->_readRS256();

                break;
            case self::OKP_EDDSA: $this->_readEDDSA();

                break;
        }
    }

    public function getPem(): string
    {
        $der = match ($this->kty) {
            self::EC2_TYPE => $this->der->encodeEC2($this->getU2F()),
            self::OKP_TYPE => $this->der->encodeOKP($this->x),
            self::RSA_TYPE => $this->der->encodeRSA($this->n, $this->e),
            default        => throw new AuthenticatorException('invalid key type')
        };

        return sprintf(
            "-----BEGIN PUBLIC KEY-----\n%s-----END PUBLIC KEY-----\n",
            chunk_split(base64_encode($der), 64, "\n")
        );
    }

    public function getU2F(): string
    {
        if ($this->kty !== self::EC2_TYPE) {
            throw new AuthenticatorException('signature algorithm not ES256');
        }

        return "\x04" . $this->x . $this->y;
    }

    /**
     * Extract EDDSA informations from cose.
     */
    private function _readEDDSA(): void
    {
        $this->crv = $this->enc[self::COSE_CRV];
        $this->x   = $this->enc[self::COSE_X] instanceof ByteBufferInterface ? $this->enc[self::COSE_X]->getBinaryString() : '';
        //unset ($this->enc);

        // Validation
        if ($this->kty !== self::OKP_TYPE) {
            throw new AuthenticatorException('public key not in OKP format');
        }

        if ($this->alg !== self::OKP_EDDSA) {
            throw new AuthenticatorException('signature algorithm not EdDSA');
        }

        if ($this->crv !== self::OKP_ED25519) {
            throw new AuthenticatorException('curve not Ed25519');
        }

        if (strlen($this->x) !== 32) {
            throw new AuthenticatorException('Invalid X-coordinate');
        }
    }

    /**
     * Extract ES256 informations from cose.
     */
    private function _readES256(): void
    {
        $this->crv = $this->enc[self::COSE_CRV];
        $this->x   = $this->enc[self::COSE_X] instanceof ByteBufferInterface ? $this->enc[self::COSE_X]->getBinaryString() : '';
        $this->y   = $this->enc[self::COSE_Y] instanceof ByteBufferInterface ? $this->enc[self::COSE_Y]->getBinaryString() : '';
        //unset ($this->enc);

        // Validation
        if ($this->kty !== self::EC2_TYPE) {
            throw new AuthenticatorException('public key not in EC2 format');
        }

        if ($this->alg !== self::EC2_ES256) {
            throw new AuthenticatorException('signature algorithm not ES256');
        }

        if ($this->crv !== self::EC2_P256) {
            throw new AuthenticatorException('curve not P-256');
        }

        if (strlen($this->x) !== 32) {
            throw new AuthenticatorException('Invalid X-coordinate');
        }

        if (strlen($this->y) !== 32) {
            throw new AuthenticatorException('Invalid Y-coordinate');
        }
    }

    /**
     * Extract RS256 informations from COSE.
     */
    private function _readRS256(): void
    {
        $this->n = $this->enc[self::COSE_N] instanceof ByteBufferInterface ? $this->enc[self::COSE_N]->getBinaryString() : '';
        $this->e = $this->enc[self::COSE_E] instanceof ByteBufferInterface ? $this->enc[self::COSE_E]->getBinaryString() : '';
        //unset ($this->enc);

        // Validation
        if ($this->kty !== self::RSA_TYPE) {
            throw new AuthenticatorException('public key not in RSA format');
        }

        if ($this->alg !== self::RSA_RS256) {
            throw new AuthenticatorException('signature algorithm not ES256');
        }

        if (strlen($this->n) !== 256) {
            throw new AuthenticatorException('Invalid RSA modulus');
        }

        if (strlen($this->e) !== 3) {
            throw new AuthenticatorException('Invalid RSA public exponent');
        }
    }
}
