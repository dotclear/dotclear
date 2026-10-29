<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Database\Statement\SelectStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_0_beta3_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Populate media_dir field (since 2.0-beta3.3)
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns(['media_id', 'media_file'])
            ->from(App::db()->con()->prefix() . App::postMedia()::MEDIA_TABLE_NAME)
            ->select();
        if (!$rs instanceof MetaRecord) {
            return $cleanup_sessions;
        }

        while ($rs->fetch()) {
            $media_file = $rs->strField('media_file');
            $media_id   = $rs->intField('media_id');
            if ($media_file !== '' && $media_id !== 0) {
                $cur = App::media()->openMediaCursor();
                $cur->setStrField('media_dir', dirname($media_file));
                $sql = new UpdateStatement();
                $sql
                    ->where('media_id = ' . $media_id)
                    ->update($cur);
            }
        }

        return $cleanup_sessions;
    }
}
