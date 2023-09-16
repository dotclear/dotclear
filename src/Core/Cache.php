<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Module\StoreReader;
use Dotclear\Interface\Core\CacheInterface;

/**
 * @brief   Dotclear cache handler .
 *
 * @since 2.28
 */
class Cache implements CacheInterface
{
    /**
     * Constructor.
     *
     * @param   string  $cache_dir  The full cache directory path
     */
    public function __construct(
        protected string $cache_dir
    ) {
    }

    /**
     * Empty templates cache directory.
     */
    public function emptyTemplatesCache(): void
    {
        if (is_dir($this->cache_dir . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER)) {
            Files::deltree($this->cache_dir . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER);
        }
    }

    /**
     * Empty modules store cache directory.
     */
    public function emptyModulesStoreCache(): void
    {
        if (is_dir($this->cache_dir . DIRECTORY_SEPARATOR . StoreReader::CACHE_FOLDER)) {
            Files::deltree($this->cache_dir . DIRECTORY_SEPARATOR . StoreReader::CACHE_FOLDER);
        }
    }
}
