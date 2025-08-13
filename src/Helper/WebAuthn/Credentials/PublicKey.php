<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Credentials;

use Dotclear\Helper\WebAuthn\Exception\CredentialsException;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\OptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\PublicKeyInterface;
use Exception;
use stdClass;

/**
 * @brief   WebAuthn public key class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class PublicKey implements PublicKeyInterface
{
    /**
     * Public key options stack.
     *
     * @var     OptionInterface[]  $options
     */
    private $options = [];

    public function addOption(OptionInterface $option): void
    {
        if (!$option->isConfigured()) {
            try {
                $option->configure();
            } catch (Exception $e) {
                throw new CredentialsException('missconfigured public key option');
            }
        }

        $this->options[] = $option;
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        $arguments->publicKey = new stdClass();

        foreach($this->options as $option) {
            $option->parseCredentialTypeOptions($method, $arguments->publicKey);
        }
    }
}