<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\AttestationFormatsEnum;

/**
 * @brief   WebAuthn public key attestation formats interface.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#attestationformats
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface AttestationFormatsOptionInterface extends OptionInterface
{
	/**
	 * Get attestation formats instances.
	 *
	 * @return 	AttestationFormatsEnum[] 	The allowed attestation formats
	 */
	public function formats(): array;
}