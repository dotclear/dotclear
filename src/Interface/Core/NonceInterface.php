<?php
/**
 * form nonce handler interface.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Helper\Html\Form\Hidden;

interface NonceInterface
{
    /**
     * Gets the nonce.
     *
     * @return  string  The nonce.
     */
    public function getNonce(): string;

    /**
     * Check the nonce.
     *
     * @param   string  $secret     The nonce
     *
     * @return  bool
     */
    public function checkNonce(string $secret): bool;

    /**
     * Get the nonce HTML code.
     *
     * @return  string
     */
    public function getFormNonce(): string ;

    /**
     * Get the nonce Form element code.
     *
     * @return  Hidden
     */
    public function formNonce(): Hidden;
}
