<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Attestation\Format;

use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;;

/**
 * @brief   WebAuthn no attestation format interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface FormatNoneInterface extends FormatBaseInterface
{
    public const TYPE = AttestationFormatsEnum::NONE;
}