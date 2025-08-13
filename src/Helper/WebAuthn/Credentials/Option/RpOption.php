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
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key relying party descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class RpOption implements RpOptionInterface
{
    /**
     * The relying party ID.
     */
    protected string $id;

    /**
     * The relying party name (domain).
     */
    protected string $name;

    public function id(): string
    {
        if (!$this->isConfigured()) {
            $this->configure();
        }

        return $this->id;
    }

    public function name(): string
    {
        if (!$this->isConfigured()) {
            $this->configure();
        }

        return $this->name;
    }

    public function hash(): string
    {
        if (!$this->isConfigured()) {
            $this->configure();
        }

        return hash('sha256', $this->id, true);
    }

    public function configure(array $config = []): self
    {
        $this->id   = isset($config['id'])   && is_string($config['id']) ? trim($config['id']) : '';
        $this->name = isset($config['name']) && is_string($config['name']) ? trim($config['name']) : '';

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->id) && isset($this->name);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::CREATE) {
            $arguments->rp       = new stdClass();
            $arguments->rp->name = $this->name;
            $arguments->rp->id   = $this->id;
        } elseif ($method === CredentialMethodEnum::GET) {
            $arguments->rpId = $this->id;
        }
    }
}
