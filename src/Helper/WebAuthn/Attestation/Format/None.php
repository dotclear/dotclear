<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Attestation\Format;

use Dotclear\Interface\Helper\WebAuthn\Attestation\Format\FormatNoneInterface;

/**
 * @brief   WebAuthn attestation format option for no format.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class None extends FormatBase implements FormatNoneInterface
{
    public function getCertificatePem(): string
    {
        return '';
    }

    public function validateAttestation(string $clientDataHash): bool
    {
        return true;
    }

    public function validateRootCertificate(array $rootCas): bool
    {
        return false;
    }
}
