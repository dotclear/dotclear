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
 * @brief   WebAuthn credential type enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/CredentialsContainer/create#federated
 * https://developer.mozilla.org/en-US/docs/Web/API/CredentialsContainer/get#password
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum CredentialTypeEnum: string
{
    use EnumTrait;

    case PASSWORD = 'password';
    case IDENTITY = 'identity';
    //case FEDERATED  = 'federated'; // deprecated
    case OTP       = 'otp';
    case PUBLICKEY = 'publicKey';

    public const DEFAULT = self::PUBLICKEY;
}
