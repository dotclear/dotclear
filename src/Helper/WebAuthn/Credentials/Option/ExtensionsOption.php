<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\ExtensionsOptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key extensions descriptor.
 *
 * Not yet implemented.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class ExtensionsOption implements ExtensionsOptionInterface
{
    public function configure(array $config = []): self
    {
        return $this;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::CREATE) {
            $arguments->extensions = new stdClass();
            $arguments->extensions->exts = true;
        }
    }
}