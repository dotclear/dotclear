<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Data;

use Dotclear\Interface\Helper\WebAuthn\Attestation\AttestationInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface;

/**
 * @brief   WebAuthn credential data helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Credential implements CredentialInterface
{
    /**
     * The credential data from flow response.
     *
     * @var     array<string, mixed>    $data
     */
    private array $data = [];
    public function __construct(
        protected RpOptionInterface $rp
    ) {
    }

    public function fromAttestation(AttestationInterface $attestation, string $label = ''): void
    {
        $this->data = [
            'createDate'          => date('Y-m-d H:i:s'),
            'label'               => trim($label),
            'rpId'                => $this->rp->id(),
            'attestationFormat'   => $attestation->getAttestationFormatType()->value,
            'credentialId'        => $attestation->getAuthenticator()->getCredentialId(),
            'credentialPublicKey' => $attestation->getAuthenticator()->getPublicKeyPem(),
            'certificateChain'    => $attestation->getCertificateChain(),
            'certificate'         => $attestation->getCertificatePem(),
            'certificateIssuer'   => $attestation->getCertificateIssuer(),
            'certificateSubject'  => $attestation->getCertificateSubject(),
            'signatureCounter'    => $attestation->getAuthenticator()->getSignCount(),
            'AAGUID'              => $attestation->getAuthenticator()->getAAGUID(),
            'userPresent'         => $attestation->getAuthenticator()->isUserPresent(),
            'userVerified'        => $attestation->getAuthenticator()->isUserVerified(),
            'isBackupEligible'    => $attestation->getAuthenticator()->isBackupEligible(),
            'isBackedUp'          => $attestation->getAuthenticator()->isBackup(),
            // missing transports and rootValid
        ];
    }

    public function fromArray(array $res): void
    {
        $this->data = [
            'createDate'          => $res['createDate']          ?? date('Y-m-d H:i:s'),
            'label'               => $res['label']               ?? '',
            'rpId'                => $res['rpId']                ?? '',
            'attestationFormat'   => $res['attestationFormat']   ?? '',
            'credentialId'        => $res['credentialId']        ?? '',
            'credentialPublicKey' => $res['credentialPublicKey'] ?? '',
            'certificateChain'    => $res['certificateChain']    ?? '',
            'certificate'         => $res['certificate']         ?? '',
            'certificateIssuer'   => $res['certificateIssuer']   ?? '',
            'certificateSubject'  => $res['certificateSubject']  ?? '',
            'signatureCounter'    => $res['signatureCounter']    ?? '',
            'AAGUID'              => $res['AAGUID']              ?? '',
            'userPresent'         => $res['userPresent']         ?? '',
            'userVerified'        => $res['userVerified']        ?? '',
            'isBackupEligible'    => $res['isBackupEligible']    ?? '',
            'isBackedUp'          => $res['isBackedUp']          ?? '',
        ];
    }

    public function newFromArray(array $res): CredentialInterface
    {
        $clone = clone $this;
        $clone->fromArray($res);

        return $clone;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function CreateDate(): string
    {
        return $this->data['createDate'] ?? '';
    }

    public function label(): string
    {
        return $this->data['label'] ?: '';
    }

    public function rpId(): string
    {
        return $this->data['rpId'] ?? '';
    }

    public function attestationFormat(): string
    {
        return $this->data['attestationFormat'] ?? '';
    }

    public function credentialId(): string
    {
        return $this->data['credentialId'] ?? '';
    }

    public function credentialPublicKey(): string
    {
        return $this->data['credentialPublicKey'] ?? '';
    }

    public function certificateChain(): string
    {
        return $this->data['certificateChain'] ?? '';
    }

    public function certificate(): string
    {
        return $this->data['certificate'] ?? '';
    }

    public function certificateIssuer(): string
    {
        return $this->data['certificateIssuer'] ?? '';
    }

    public function certificateSubject(): string
    {
        return $this->data['certificateSubject'] ?? '';
    }

    public function signatureCounter(): int
    {
        return (int) ($this->data['signatureCounter'] ?? 0);
    }

    public function AAGUID(): string
    {
        return $this->data['AAGUID'] ?? '';
    }

    public function UUID(): string
    {
        $s = str_split(bin2hex($this->AAGUID()), 4);

        return vsprintf('%s-%s-%s-%s-%s', [$s[0] . $s[1], $s[2], $s[3], $s[4], $s[5] . $s[6] . $s[7]]);
    }

    public function userPresent(): bool
    {
        return !empty($this->data['userPresent'] ?? '');
    }

    public function userVerified(): bool
    {
        return !empty($this->data['userVerified'] ?? '');
    }

    public function isBackupEligible(): bool
    {
        return !empty($this->data['isBackupEligible'] ?? '');
    }

    public function isBackedUp(): bool
    {
        return !empty($this->data['isBackedUp'] ?? '');
    }
}
