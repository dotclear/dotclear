<?php

/**
 * @package     Dotclear
 * @subpackage Core
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Core\Upgrade\Update;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Helper\Network\HttpCacheStack;
use Dotclear\Interface\Core\CacheInterface;
use Dotclear\Interface\Core\ConfigInterface;
use Dotclear\Module\StoreReader;

/**
 * @brief   Application cache handler.
 *
 * @since   2.28, cache features have been grouped in this class
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Cache extends HttpCacheStack implements CacheInterface
{
    /**
     * The full cache directory path.
     */
    protected string $cache_dir;

    /**
     * Try to avoid browser cache.
     */
    protected bool $avoid_cache = false;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(Core $core)
    {
        $this->cache_dir = $core->config()->cacheRoot();
    }

    public function emptyTemplatesCache(): void
    {
        if (is_dir($this->cache_dir . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER)) {
            Files::deltree($this->cache_dir . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER);
        }
    }

    public function emptyModulesStoreCache(): void
    {
        if (is_dir($this->cache_dir . DIRECTORY_SEPARATOR . StoreReader::CACHE_FOLDER)) {
            Files::deltree($this->cache_dir . DIRECTORY_SEPARATOR . StoreReader::CACHE_FOLDER);
        }
    }

    public function emptyDotclearVersionsCache(): void
    {
        if (is_dir($this->cache_dir . DIRECTORY_SEPARATOR . Update::CACHE_FOLDER)) {
            Files::deltree($this->cache_dir . DIRECTORY_SEPARATOR . Update::CACHE_FOLDER);
        }
    }

    public function setAvoidCache(bool $avoid): void
    {
        $this->avoid_cache = true;
    }

    public function isAvoidCache(): bool
    {
        return $this->avoid_cache;
    }
}
