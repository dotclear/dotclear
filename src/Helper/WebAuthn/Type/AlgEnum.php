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
 * @brief   WebAuthn cryptographic algorithm enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#alg
 * 
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum AlgEnum: int
{
    use EnumTrait;

    case EDDSA = -8;
    case ES256 = -7;
    case RS256 = -257;
}
