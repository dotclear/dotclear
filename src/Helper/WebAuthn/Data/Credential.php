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
        return isset($this->data['createDate']) && is_string($value = $this->data['createDate']) ? $value : '';
    }

    public function label(): string
    {
        return isset($this->data['label']) && is_string($value = $this->data['label']) ? $value : '';
    }

    public function rpId(): string
    {
        return isset($this->data['rpId']) && is_string($value = $this->data['rpId']) ? $value : '';
    }

    public function attestationFormat(): string
    {
        return isset($this->data['attestationFormat']) && is_string($value = $this->data['attestationFormat']) ? $value : '';
    }

    public function credentialId(): string
    {
        return isset($this->data['credentialId']) && is_string($value = $this->data['credentialId']) ? $value : '';
    }

    public function credentialPublicKey(): string
    {
        return isset($this->data['credentialPublicKey']) && is_string($value = $this->data['credentialPublicKey']) ? $value : '';
    }

    public function certificateChain(): string
    {
        return isset($this->data['certificateChain']) && is_string($value = $this->data['certificateChain']) ? $value : '';
    }

    public function certificate(): string
    {
        return isset($this->data['certificate']) && is_string($value = $this->data['certificate']) ? $value : '';
    }

    public function certificateIssuer(): string
    {
        return isset($this->data['certificateIssuer']) && is_string($value = $this->data['certificateIssuer']) ? $value : '';
    }

    public function certificateSubject(): string
    {
        return isset($this->data['certificateSubject']) && is_string($value = $this->data['certificateSubject']) ? $value : '';
    }

    public function signatureCounter(): int
    {
        return isset($this->data['signatureCounter']) && is_numeric($value = $this->data['signatureCounter']) ? (int) $value : 0;
    }

    public function AAGUID(): string
    {
        return isset($this->data['AAGUID']) && is_string($value = $this->data['AAGUID']) ? $value : '';
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
