<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\AuthenticatorAttachmentEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Helper\WebAuthn\Type\ResidentKeyEnum;
use Dotclear\Helper\WebAuthn\Type\UserVerificationEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\AuthenticatorSelectionOptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key authenticator seletion descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class AuthenticatorSelectionOption implements AuthenticatorSelectionOptionInterface
{
    /**
     * The resident key type.
     *
     * @var     ResidentKeyEnum   $resident_key
     */
    protected ResidentKeyEnum $resident_key;

    /**
     * The user verification type.
     *
     * @var     UserVerificationEnum  $user_verification
     */
    protected UserVerificationEnum $user_verification;

    /**
     * The authenticator attachment type.
     *
     * @var     AuthenticatorAttachmentEnum   $authenticator_attachment
     */
    protected AuthenticatorAttachmentEnum $authenticator_attachment;

    public function requireUserPresent(): bool
    {
        if (!$this->isConfigured()) {
            $this->configure();
        }

        return $this->resident_key === ResidentKeyEnum::REQUIRED;
    }

    public function requireUserVerification(): bool
    {
        if (!$this->isConfigured()) {
            $this->configure();
        }

        return $this->user_verification === UserVerificationEnum::REQUIRED;
    }

    public function configure(array $config = []): self
    {
        $this->resident_key             = isset($config['resident_key']) && is_a($config['resident_key'], ResidentKeyEnum::class) ? 
            $config['resident_key'] : ResidentKeyEnum::DEFAULT;
        $this->user_verification        = isset($config['user_verification']) && is_a($config['user_verification'], UserVerificationEnum::class) ? 
            $config['user_verification'] : UserVerificationEnum::DEFAULT;
        $this->authenticator_attachment = isset($config['authenticator_attachment']) && is_a($config['authenticator_attachment'], AuthenticatorAttachmentEnum::class) ? 
            $config['authenticator_attachment'] : AuthenticatorAttachmentEnum::DEFAULT;

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->resident_key) && isset($this->user_verification) && isset($this->authenticator_attachment);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        switch ($method) {
            case CredentialMethodEnum::CREATE:
                $arguments->authenticatorSelection                     = new stdClass();
                $arguments->authenticatorSelection->userVerification   = $this->user_verification->value;
                $arguments->authenticatorSelection->residentKey        = $this->resident_key->value;
                // deprecated: https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#requireresidentkey
                $arguments->authenticatorSelection->requireResidentKey = $this->resident_key->required();

                // filter authenticators attached with the specified authenticator attachment modality
                if ($this->authenticator_attachment !== AuthenticatorAttachmentEnum::ANY) {
                    $arguments->authenticatorSelection->authenticatorAttachment = $this->authenticator_attachment->value;
                }
                break;
            
            case CredentialMethodEnum::GET:
                $arguments->userVerification   = $this->user_verification->value;
                break;
            
            default:
                break;
        }
    }
}