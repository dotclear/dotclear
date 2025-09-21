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
    public const CONTAINER_ID = 'backendauth';

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
        return App::backend()->safe_mode !== true && App::config()->authPasswordOnly() !== true;
    }

    /**
     * Get backend otp authentication helper instance.
     */
    public function otp(): false|Otp
    {
        try {
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
            return $this->isAllowed() ? $this->get(WebAuthn::class) : false;
        } catch (Throwable) { // silently fail
            return false;
        }
    }

    /**
     * Get backend oauth2 authentication helper instance.
     */
    public function oauth2(string $redirect_url = ''): false|OAuth2Client
    {
        try {
            return $this->isAllowed() ? $this->get(OAuth2Client::class, false, redirect_url: $redirect_url) : false;
        } catch (Throwable) { // silently fail
            return false;
        }
    }
}