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
 * @brief   WebAuthn required resident key enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#residentkey
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum ResidentKeyEnum: string
{
    use EnumTrait;

    case REQUIRED    = 'required';
    case PREFERRED   = 'preferred';
    case DISCOURAGED = 'discouraged';

    public const DEFAULT = self::REQUIRED;

    public function required(): bool
    {
        return $this === self::REQUIRED;
    }
}
