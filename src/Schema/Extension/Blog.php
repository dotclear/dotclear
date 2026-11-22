<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Extension;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Interface\Database\RecordExtensionInterface;

/**
 * @brief Dotclear blog record helpers
 *
 * This class adds new methods to database blog results.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class Blog implements RecordExtensionInterface
{
    /**
     * Converts this Record to a Dotclear\Database\StaticRecord instance.
     *
     * @param      MetaRecord  $rs       Invisible parameter
     *
     * @return     MetaRecord  The resulting static record.
     *
     * @deprecated since 2.39 use MetaRecord::toStatic() instead
     */
    public static function toExtStatic(MetaRecord $rs): MetaRecord
    {
        App::deprecated()->set('MetaRecord::toStatic()', '2.39');

        $rs->toStatic();

        return $rs;
    }
}
