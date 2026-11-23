<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Database;

/**
 * @brief   Record extension marker interface.
 *
 * A record extension is a class given to MetaRecord::extend() (or Record::extend()):
 * its public static methods become methods of the record, called with the record
 * as first argument. Ex: Post::getURL(MetaRecord $rs) is called as $rs->getURL().
 *
 * This interface has no method. It only marks record extensions, so that static
 * analysis (see build-tools/phpstan) knows the methods they add to records.
 * Extensions of Dotclear (Dotclear\Schema\Extension) implement it, and so do
 * classes that inherit from them (ex: a plugin class extending Post).
 *
 * @since   2.41
 */
interface RecordExtensionInterface
{
}
