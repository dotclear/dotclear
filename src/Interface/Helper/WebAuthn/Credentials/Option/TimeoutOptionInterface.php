<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Credentials\Option;

/**
 * @brief   WebAuthn public key timeout interface.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#timeout
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialRequestOptions#timeout
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface TimeoutOptionInterface extends OptionInterface
{
}