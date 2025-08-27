<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Interface\Core\NonceInterface;

/**
 * @brief   Form nonce handler.
 *
 * @since   2.28, form nonce features have been grouped in this class
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Nonce implements NonceInterface
{
    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
    }

    public function getNonce(): string
    {
        return $this->core->auth()->cryptLegacy((string) session_id());
    }

    public function checkNonce(string $secret): bool
    {
        // 40 alphanumeric characters min
        if (!preg_match('/^([0-9a-f]{40,})$/i', $secret)) {
            return false;
        }

        return $secret === $this->core->auth()->cryptLegacy((string) session_id());
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
