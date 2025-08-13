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
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\TimeoutOptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key timeout descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class TimeoutOption implements TimeoutOptionInterface
{
    /**
     * The query timeout.
     */
    protected int $timeout;

    public function configure(array $config = []): self
    {
        $this->timeout = isset($config['timeout']) && is_int($config['timeout']) ? $config['timeout'] : 30;

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->timeout);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method    === CredentialMethodEnum::CREATE
            || $method === CredentialMethodEnum::GET
        ) {
            $arguments->timeout = $this->timeout * 1000;
        }
    }
}
