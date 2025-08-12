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
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#type
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#type_2
 *
 * For now only "public-key" is supported
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum TypeEnum: string
{
    use EnumTrait;

    case PASSWORD  = 'password';
    case FEDERATED = 'federated';
    case PUBLICKEY = 'public-key';

    public const DEFAULT = self::PUBLICKEY;
}
