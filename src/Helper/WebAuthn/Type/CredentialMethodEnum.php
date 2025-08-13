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
 * https://developer.mozilla.org/en-US/docs/Web/API/CredentialsContainer
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum CredentialMethodEnum: string
{
    use EnumTrait;

    case CREATE = 'create';
    case GET    = 'get';
    case STORE  = 'store'; // not used
}
