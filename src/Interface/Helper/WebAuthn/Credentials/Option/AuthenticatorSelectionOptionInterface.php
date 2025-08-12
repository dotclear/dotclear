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
 * @brief   WebAuthn public key authenticator seletion interface.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#authenticatorselection
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface AuthenticatorSelectionOptionInterface extends OptionInterface
{
	/**
	 * Check if user presence was required.
	 *
	 * @return 	bool 	True if required
	 */
    public function requireUserPresent(): bool;

    /**
     * Check if user verification was required.
     *
     * @return 	bool 	True if required
     */
    public function requireUserVerification(): bool;
}