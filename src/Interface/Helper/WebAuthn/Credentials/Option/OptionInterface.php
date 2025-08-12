<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use stdClass;

/**
 * @brief   WebAuthn public key option interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface OptionInterface
{
    /**
     * Configured public key option.
     *
     * @param   array<string, mixed>    $config     The public key option configuration parameters
     */
    public function configure(array $config = []): self;

    /**
     * Check if option is configured.
     *
     * @return  bool    True if it is configured
     */
    public function isConfigured(): bool;

    /**
     * Add "create" options to public key.
     *
     * @param   CredentialMethodEnum    $method     The credentials method
     * @param   stdClass                $arguments  The credential type arguments instance
     */
    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void;
}