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
 * @brief   WebAuthn public key user interface.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#user
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface UserOptionInterface extends OptionInterface
{
    /**
     * Get usery ID.
     *
     * @return  string  The user ID
     */
    public function id(): string;

    /**
     * Get user name.
     *
     * @return  string  The user name
     */
    public function name(): string;

    /**
     * Get display name.
     *
     * @return  string  The user display name
     */
    public function displayname(): string;
}