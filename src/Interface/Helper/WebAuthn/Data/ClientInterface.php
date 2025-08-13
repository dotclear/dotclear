<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Data;

/**
 * @brief   WebAuthn client data interface.
 *
 * Methods are used to follow some rules from
 * https://www.w3.org/TR/webauthn/#sctn-registering-a-new-credential
 * 
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface ClientInterface
{
    /**
     * Parse client data from flow response.
     *
     * 5. Let JSONtext be the result of running UTF-8 decode on the value of response.clientDataJSON.
     *
     * @param   string  $binary     The data from flow response
     */
    public function fromResponse(string $binary): void;

    /**
     * Get data hash.
     *
     * 11. Let hash be the result of computing a hash over response.clientDataJSON using SHA-256.
     *
     * @return  string  The data hash
     */
    public function getHash(): string;

    /**
     * Add key hash for android verification.
     *
     * Required for 19. Verify that attStmt is a correct attestation statement, conveying a valid attestation signature
     *
     * @param   string    $hash
     */
    public function addAndroidKeyHash(string $hash): void;

    /**
     * Check if data are an object.
     *
     * 6. Let C, the client data claimed as collected during the credential creation, be the result of running an implementation-specific JSON parser on JSONtext.
     *
     * @return  bool    True if it is an object
     */
    public function checkObject(): bool;

    /**
     * Check if data come from create method.
     *
     * 7. Verify that the value of C.type is webauthn.create.
     *
     * @return  bool    True if it is from create method
     */
    public function checkCreate(): bool;

    /**
     * Check if data come from get method.
     *
     * 7. Verify that the value of C.type is webauthn.get.
     *
     * @return  bool    True if it is from get method
     */
    public function checkGet(): bool;

    /**
     * Check if data challenge match stored challenge.
     *
     * 8. Verify that the value of C.challenge matches the challenge that was sent to the authenticator in the create() call.
     *
     * @return  bool    True if they match
     */
    public function checkChallenge(): bool;

    /**
     * Check if data origin match current palteform.
     *
     * @return  bool    True if it matches
     */
    public function checkOrigin(): bool;
}