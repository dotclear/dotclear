<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Type;

/**
 * @brief   WebAuthn attestation formats enumeration.
 *
 * https://www.iana.org/assignments/webauthn/webauthn.xhtml#webauthn-attestation-statement-format-ids
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum AttestationFormatsEnum: string
{
    use EnumTrait;

    case KEY       = 'key';
    case SAFETYNET = 'android-safetynet';
    case APPLE     = 'apple';
    case FIDO      = 'fido-u2f';
    case PACKED    = 'packed';
    case TPM       = 'tpm';
    case NONE      = 'none';
}
