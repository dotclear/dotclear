<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Dotclear\App;
use Dotclear\Core\Backend\Auth\OAuth2Client;
use Dotclear\Core\Backend\Auth\Otp;
use Dotclear\Core\Backend\Auth\WebAuthn;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;
use Throwable;

/**
 * @brief   Admin exotic authentication helpers library
 *
 * @since   2.36
 */
class Auth extends Container
{
    /**
     * @var string CONTAINER_ID
     */
    public const CONTAINER_ID = 'backendauth';

    /**
     * Form field name of the passkey verification token.
     *
     * @since   2.41
     *
     * @var string VERIFY_FIELD
     */
    public const VERIFY_FIELD = 'webauthn_verify';

    /**
     * Passkey verification token lifetime, in seconds.
     *
     * @since   2.41
     *
     * @var int VERIFY_TTL
     */
    public const VERIFY_TTL = 300;

    /**
     * Session key of the passkey verification token.
     *
     * @var string VERIFY_SESSION
     */
    protected const VERIFY_SESSION = 'webauthn_verify';

    public function __construct()
    {
        // Create a non replaceable factory
        parent::__construct(new Factory(static::CONTAINER_ID, false));
    }

    public function getDefaultServices(): array
    {
        return [
            OAuth2Client::class => OAuth2Client::class,
            Otp::class          => Otp::class,
            WebAuthn::class     => WebAuthn::class,
        ];
    }

    /**
     * Check if exotic authentication is allowed.
     */
    public function isAllowed(): bool
    {
        return App::backend()->safe_mode !== true && !App::config()->authPasswordOnly();
    }

    /**
     * Get backend otp authentication helper instance.
     */
    public function otp(): false|Otp
    {
        try {
            // @phpstan-ignore return.type
            return $this->isAllowed() ? $this->get(Otp::class) : false;
        } catch (Throwable) { // silently fail
            return false;
        }
    }

    /**
     * Get backend webauthn authentication helper instance.
     */
    public function webauthn(): false|WebAuthn
    {
        try {
            // @phpstan-ignore return.type
            return $this->isAllowed() ? $this->get(WebAuthn::class) : false;
        } catch (Throwable) { // silently fail
            return false;
        }
    }

    /**
     * Check if current user can confirm sensitive actions with a passkey.
     *
     * @since   2.41
     */
    public function canVerify(): bool
    {
        if (App::auth()->userID() === '' || !(bool) ($webauthn = $this->webauthn())) {
            return false;
        }

        try {
            return $webauthn->store()->getCredentials('', App::auth()->userID()) !== [];
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Create a passkey verification token for current user.
     *
     * Must only be called after a successful passkey verification.
     * Only one token is kept at a time, it can be used once.
     *
     * @since   2.41
     *
     * @return  string  The token to send back with the form
     */
    public function setVerifyToken(): string
    {
        $token = bin2hex(random_bytes(32));

        App::session()->set(static::VERIFY_SESSION, [
            'user' => App::auth()->userID(),
            'hash' => hash('sha256', $token),
            'time' => time(),
        ]);

        return $token;
    }

    /**
     * Check (and consume) a passkey verification token of current user.
     *
     * @since   2.41
     */
    public function checkVerifyToken(string $token): bool
    {
        $data = App::session()->get(static::VERIFY_SESSION);

        // A token can be used only once
        App::session()->unset(static::VERIFY_SESSION);

        return $token            !== ''
            && $this->webauthn() !== false
            && is_array($data)
            && isset($data['user'], $data['hash'], $data['time'])
            && is_string($data['user'])
            && is_string($data['hash'])
            && is_int($data['time'])
            && $data['user'] !== ''
            && $data['user'] === App::auth()->userID()
            && $data['time'] + static::VERIFY_TTL >= time()
            && hash_equals($data['hash'], hash('sha256', $token));
    }

    /**
     * Check if a passkey verification token is sent with the form.
     *
     * @since   2.41
     */
    public function hasVerifyToken(): bool
    {
        return $this->getVerifyToken() !== '';
    }

    /**
     * Get the passkey verification token sent with the form.
     *
     * @return  string  The token, empty string if none
     */
    protected function getVerifyToken(): string
    {
        return isset($_POST[static::VERIFY_FIELD]) && is_string($token = $_POST[static::VERIFY_FIELD]) ? $token : '';
    }

    /**
     * Check current user identity to confirm a sensitive action.
     *
     * If a passkey verification token is sent with the form (see VERIFY_FIELD),
     * it is checked instead of the password.
     *
     * @since   2.41
     *
     * @param   string  $pwd    The password given by user
     */
    public function checkPassword(string $pwd): bool
    {
        $token = $this->getVerifyToken();
        if ($token !== '') {
            return $this->checkVerifyToken($token);
        }

        return $pwd !== '' && App::auth()->checkPassword($pwd);
    }

    /**
     * Get backend oauth2 authentication helper instance.
     */
    public function oauth2(string $redirect_url = ''): false|OAuth2Client
    {
        try {
            // @phpstan-ignore return.type
            return $this->isAllowed() ? $this->get(OAuth2Client::class, false, redirect_url: $redirect_url) : false;
        } catch (Throwable) { // silently fail
            return false;
        }
    }
}
