<?php

/**
 * @package     Dotclear
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
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\CacheInterface;
use Dotclear\Module\StoreReader;

/**
 * @brief   Application cache handler.
 *
 * @since   2.28, cache features have been grouped in this class
 */
class Cache extends HttpCacheStack implements CacheInterface
{
    /**
     * The full cache directory path.
     */
    protected string $cache_dir;

    /**
     * Constructor.
     *
     * @param   ConfigInterface     $config     The application configuration
     */
    public function __construct(ConfigInterface $config)
    {
        $this->cache_dir = $config->cacheRoot();
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

    /**
     * Empty Dotclear versions cache directory.
     */
    public function emptyDotclearVersionsCache(): void
    {
        if (is_dir($this->cache_dir . DIRECTORY_SEPARATOR . Update::CACHE_FOLDER)) {
            Files::deltree($this->cache_dir . DIRECTORY_SEPARATOR . Update::CACHE_FOLDER);
        }
    }
}
