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
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;
use stdClass;

/**
 * @brief   WebAuthn user descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class UserOption implements UserOptionInterface
{
    /**
     * The user ID.
     */
    protected string $id;

    /**
     * The user name.
     */
    protected string $name;

    /**
     * The user displayname.
     */
    protected string $displayname;

    /**
     * Load services from container.
     *
     * @param   ByteBufferInterface     $buffer     The byte buffer interface
     */
    public function __construct(
        protected ByteBufferInterface $buffer
    ) {
    }

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

    public function displayname(): string
    {
        if (!$this->isConfigured()) {
            $this->configure();
        }

        return $this->displayname;
    }

    public function configure(array $config = []): self
    {
        $this->id          = isset($config['id'])          && is_string($config['id']) ? trim($config['id']) : '';
        $this->name        = isset($config['name'])        && is_string($config['name']) ? trim($config['name']) : '';
        $this->displayname = isset($config['displayname']) && is_string($config['displayname']) ? trim($config['displayname']) : '';

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->id);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::CREATE) {
            $arguments->user              = new stdClass();
            $arguments->user->id          = $this->buffer->fromBinary($this->id);
            $arguments->user->name        = $this->name;
            $arguments->user->displayName = $this->displayname;
        }
    }
}
