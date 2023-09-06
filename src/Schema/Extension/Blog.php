<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Schema\Extension;

use Dotclear\Database\MetaRecord;

/**
 * @brief Dotclear blog record helpers
 *
 * This class adds new methods to database blog results.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class Blog
{
    /**
     * Converts this Record to a {@link StaticRecord} instance.
     *
     * @param      MetaRecord  $rs       Invisible parameter
     *
     * @return     MetaRecord  The extent static record.
     */
    public static function toExtStatic(MetaRecord $rs): MetaRecord
    {
        $rs->toStatic();

        return $rs;
    }
}
