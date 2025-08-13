<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Credentials\Option;

use Dotclear\Helper\WebAuthn\Type\AlgEnum;
use Dotclear\Helper\WebAuthn\Type\CredentialMethodEnum;
use Dotclear\Helper\WebAuthn\Type\TypeEnum;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\PubKeyCredParamsOptionInterface;
use stdClass;

/**
 * @brief   WebAuthn public key cryptographic algorithms descriptor.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class PubKeyCredParamsOption implements PubKeyCredParamsOptionInterface
{
    /**
     * The public key credential params.
     *
     * @var     AlgEnum[]  $params
     */
    protected array $params;

    public function configure(array $config = []): self
    {
        $this->params = [];

        if (function_exists('sodium_crypto_sign_verify_detached') || in_array('ed25519', openssl_get_curve_names() ?: [], true)) {
            $this->params[] = AlgEnum::EDDSA; // EdDSA -8
        }

        if (\in_array('prime256v1', openssl_get_curve_names() ?: [], true)) {
            $this->params[] = AlgEnum::ES256; // ES256 -7
        }

        $this->params[] = AlgEnum::RS256; // RS256 -257

        return $this;
    }

    public function isConfigured(): bool
    {
        return isset($this->params);
    }

    public function parseCredentialTypeOptions(CredentialMethodEnum $method, stdClass $arguments): void
    {
        if ($method === CredentialMethodEnum::CREATE) {
            $params = [];
            foreach($this->params as $algo) {
                $tmp       = new stdClass();
                $tmp->type = TypeEnum::PUBLICKEY->value;
                $tmp->alg  = $algo->value;
                $params[]  = $tmp;
            }

            $arguments->pubKeyCredParams = $params;
        }
    }
}