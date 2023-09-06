<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Interface\Core\NonceInterface;

/**
 * Form nonce handler.
 */
class Nonce implements NonceInterface
{
    public function getNonce(): string
    {
        return App::auth()->cryptLegacy((string) session_id());
    }

    public function checkNonce(string $secret): bool
    {
        // 40 alphanumeric characters min
        if (!preg_match('/^([0-9a-f]{40,})$/i', $secret)) {
            return false;
        }

        return $secret == App::auth()->cryptLegacy((string) session_id());
    }

    public function getFormNonce(): string
    {
        return $this->formNonce()->render();
    }

    public function formNonce(): Hidden
    {
        return new Hidden(['xd_check'], !session_id() ? '' : $this->getNonce());
    }
}
