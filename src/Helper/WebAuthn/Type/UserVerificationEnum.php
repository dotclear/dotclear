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
 * @brief   WebAuthn required user verification enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#userverification
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialRequestOptions#userverification
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum UserVerificationEnum: string
{
    use EnumTrait;

    case REQUIRED    = 'required';
    case PREFERRED   = 'preferred';
    case DISCOURAGED = 'discouraged';

    public const DEFAULT = self::PREFERRED;
}
