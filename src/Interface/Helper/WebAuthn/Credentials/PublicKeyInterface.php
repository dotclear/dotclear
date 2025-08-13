<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Credentials;

use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\OptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface PublicKeyInterface
{
    /**
     * Add credentials option instance.
     *
     * @param   OptionInterface     $option     The credentials otpion instance
     */
    public function addOption(OptionInterface $option): void;

    /**
     * Parse credentials option.
     *
     * @param   CredentialMethodEnum    $method     The credentials method
     * @param   stdClass                $arguments  The credentials arguments instance
     */
    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void;
}