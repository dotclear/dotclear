<?php
/**
 * form nonce handler.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use dcAuth;
use Dotclear\Helper\Html\Form\Hidden;

class Nonce
{
    /** @var    dcAuth  The auth instance */
    private dcAuth $auth;

    public function __construct()
    {
        $this->auth = dcCore::app()->auth;
    }

    /**
     * Gets the nonce.
     *
     * @return  string  The nonce.
     */
    public function getNonce(): string
    {
        return $this->auth->cryptLegacy((string) session_id());
    }

    /**
     * Check the nonce.
     *
     * @param   string  $secret     The nonce
     *
     * @return  bool
     */
    public function checkNonce(string $secret): bool
    {
        // 40 alphanumeric characters min
        if (!preg_match('/^([0-9a-f]{40,})$/i', $secret)) {
            return false;
        }

        return $secret == $this->auth->cryptLegacy((string) session_id());
    }

    /**
     * Get the nonce HTML code.
     *
     * @return  string
     */
    public function getFormNonce()
    {
        return $this->formNonce()->render();
    }

    /**
     * Get the nonce Form element code.
     *
     * @return  Hidden
     */
    public function formNonce()
    {
        return new Hidden(['xd_check'], !session_id() ? '' : $this->getNonce());
    }
}