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

/**
 * @brief   Upgrade step.
 *
 * @todo switch to SqlStatement
 */
class GrowUp_2_0_beta3_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Populate media_dir field (since 2.0-beta3.3)
        $strReq = 'SELECT media_id, media_file FROM ' . App::db()->con()->prefix() . App::postMedia()::MEDIA_TABLE_NAME . ' ';
        $rs_m   = new MetaRecord(App::db()->con()->select($strReq));
        while ($rs_m->fetch()) {
            $media_file = $rs_m->strField('media_file');
            $media_id   = $rs_m->intField('media_id');
            if ($media_file !== '' && $media_id !== 0) {
                $cur            = App::media()->openMediaCursor();
                $cur->media_dir = dirname($media_file);
                $cur->update('WHERE media_id = ' . $media_id);
            }
        }

        return $cleanup_sessions;
    }
}
