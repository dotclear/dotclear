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
use Dotclear\Core\Backend\Auth\Otp;
use Dotclear\Core\Backend\Auth\WebAuthn;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;
use Throwable;

/**
 * @brief   Admin auth helpers library
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
            Otp::class      => Otp::class,
            WebAuthn::class => WebAuthn::class,
        ];
    }

    public function otp(): false|Otp
    {
        try {
            return App::backend()->safe_mode || App::config()->authPasswordOnly() ? false : $this->get(Otp::class);
        } catch (Throwable) { // silently fail
            return false;
        }
    }

    public function webauthn(): false|WebAuthn
    {
        try {
            return App::backend()->safe_mode || App::config()->authPasswordOnly() ? false : $this->get(WebAuthn::class);
        } catch (Throwable) { // silently fail
            return false;
        }
    }
}