<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\Module\StoreReader;
use Dotclear\Module\StoreParser;

class NextStoreReader extends StoreReader
{
    # overwrite StoreReader to remove cache and use NextStoreParser
    public function parse(string $url): bool|StoreParser
    {
        $this->validators = [];

        if (!$this->getModulesXML($url) || $this->getStatus() != '200') {
            return false;
        }

        return new NextStoreParser($this->getContent());
    }

    # overwrite StoreReader to remove cache and use NextStoreParser
    public static function quickParse(string $url, ?string $cache_dir = null, ?bool $force = false): bool|StoreParser
    {
        $parser = new self();

        return $parser->parse($url);
    }
}
