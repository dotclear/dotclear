<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Helper\Html\Form\Hidden;

/**
 * @brief   Form nonce handler interface.
 *
 * @since   2.28
 */
interface NonceInterface
{
    /**
     * Gets the nonce.
     */
    public function getNonce(): string;

    /**
     * Check the nonce.
     *
     * @param   string  $secret     The nonce
     */
    public function checkNonce(string $secret): bool;

    /**
     * Get the nonce HTML code.
     */
    public function getFormNonce(): string ;

    /**
     * Get the nonce Form element code.
     */
    public function formNonce(): Hidden;
}
