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
 * @brief   WebAuthn passkey providers interface.
 *
 * This helps to retrieve passkey friendly name from its AAGUID through wwww deposit.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface ProviderInterface
{
    /**
     * Set passkey providers source list URL.
     *
     * @param   string  $url    The source file URL
     */
    public function setURL(string $url): void;

    /**
     * Get passekey provider friendly name.
     *
     * @param   string  $uuid   The passkey UUID
     *
     * @return  string  The friendly name
     */
    public function getProvider(string $uuid): string;

    /**
     * Update passkey providers list.
     */
    public function updateProviders(): void;
}