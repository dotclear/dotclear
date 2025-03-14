<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;
use Dotclear\Core\Upgrade\Upgrade;
use Dotclear\Helper\File\Files;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_34_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // A bit of housecleaning for no longer needed folders
        Upgrade::houseCleaning(
            // Files
            [
            ],
            // Folders
            [
            ]
        );

        // Move backup archives in root folder to DC_BACKUP_VAR (= DC_VAR by default since 2.34) if necessary
        if (App::config()->backupRoot() !== App::config()->dotclearRoot()) {
            // Check if there is some backup archives in root folder
            $archives = [];
            foreach (Files::scanDir(App::config()->dotclearRoot()) as $v) {
                if (preg_match('/backup-([0-9A-Za-z\.-]+).zip/', $v)) {
                    $archives[] = $v;
                }
            }
            foreach ($archives as $archive) {
                @rename(
                    App::config()->dotclearRoot() . DIRECTORY_SEPARATOR . $archive,
                    App::config()->backupRoot() . DIRECTORY_SEPARATOR . $archive
                );
            }
        }

        return $cleanup_sessions;
    }
}
