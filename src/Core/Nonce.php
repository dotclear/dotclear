<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\NonceInterface;

/**
 * @brief   Form nonce handler.
 *
 * @since   2.28, form nonce features have been grouped in this class
 */
class Nonce implements NonceInterface
{
    /**
     * Constructor.
     *
     * @param   AuthInterface   $auth   The authentication instance
     */
    public function __construct(
        protected AuthInterface $auth
    ) {
    }

    public function getNonce(): string
    {
        return $this->auth->cryptLegacy((string) session_id());
    }

    public function checkNonce(string $secret): bool
    {
        // 40 alphanumeric characters min
        if (!preg_match('/^([0-9a-f]{40,})$/i', $secret)) {
            return false;
        }

        return $secret === $this->auth->cryptLegacy((string) session_id());
    }

    public function getFormNonce(): string
    {
        return $this->formNonce()->render();
    }

    public function formNonce(): Hidden
    {
        return new Hidden(['xd_check'], session_id() ? $this->getNonce() : '');
    }
}
