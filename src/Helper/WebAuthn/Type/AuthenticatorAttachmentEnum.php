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
 * @brief   WebAuthn authenticator attachement type enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#authenticatorattachment
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum AuthenticatorAttachmentEnum: string
{
    use EnumTrait;

    case ANY           = 'any';
    case PLATFORM      = 'platform'; // windows hello, android safetynet
    case CROSSPLATFORM = 'cross-platform'; // fido usb

    public const DEFAULT = self::ANY;
}
