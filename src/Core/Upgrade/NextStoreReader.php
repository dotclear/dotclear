<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\Module\StoreReader;

/**
 * @brief   Unversionned Store reader.
 *
 * This does not use cache.
 *
 * @since   2.29
 */
class NextStoreReader extends StoreReader
{
    /**
     * Overwrite StoreReader to remove cache and use NextStoreParser.
     *
     * {@inheritdoc}
     */
    public function parse(string $url): bool|NextStoreParser
    {
        $this->validators = [];

        if (!$this->getModulesXML($url) || $this->getStatus() != '200') {
            return false;
        }

        return new NextStoreParser($this->getContent());
    }

    /**
     * Overwrite StoreReader to remove cache and use NextStoreParser.
     *
     * {@inheritdoc}
     */
    public static function quickParse(string $url, ?string $cache_dir = null, ?bool $force = false): bool|NextStoreParser
    {
        $parser = new self();

        return $parser->parse($url);
    }
}
