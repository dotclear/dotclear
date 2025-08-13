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
 * @brief   WebAuthn credential mediation enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/CredentialsContainer/get#mediation
 *
 * Not used yet
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum MediationEnum: string
{
    use EnumTrait;

    case CONDITIONAL = 'conditional';
    case OPTIONAL    = 'optional';
    case REQUIRED    = 'required';
    case SILENT      = 'slient';

    public const DEFAULT = self::OPTIONAL;
}
