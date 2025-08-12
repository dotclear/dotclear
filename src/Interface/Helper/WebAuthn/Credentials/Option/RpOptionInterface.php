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
 * @brief   WebAuthn public key relying party interface.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#rp
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialRequestOptions#rpid
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface RpOptionInterface extends OptionInterface
{
    /**
     * Get relying party ID.
     *
     * @return  string  The relying party ID
     */
    public function id(): string;

    /**
     * Get relying party name (domain).
     *
     * @return  string  The relying party name
     */
    public function name(): string;

    /**
     * Get relying party hash ID.
     *
     * We do not include hash to constructor as WebAuthn class must check requirements before.
     *
     * @return  string  The relying party hash ID
     */ 
    public function hash(): string;
}