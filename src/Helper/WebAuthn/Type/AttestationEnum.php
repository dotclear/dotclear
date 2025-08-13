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
 * @brief   WebAuthn attestation enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#attestation
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum AttestationEnum: string
{
    use EnumTrait;

    case NONE       = 'none';
    case DIRECT     = 'direct';
    case INDIRECT   = 'indirect';
    case ENTERPRISE = 'enterprise';

    public const DEFAULT = self::NONE;
}
