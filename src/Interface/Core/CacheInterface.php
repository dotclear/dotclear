<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * Dotclear cache handler .
 *
 * @since 2.28
 */
interface CacheInterface
{
    /**
     * Empty templates cache directory.
     */
    public function emptyTemplatesCache(): void;

    /**
     * Empty modules store cache directory.
     */
    public function emptyModulesStoreCache(): void;
}
