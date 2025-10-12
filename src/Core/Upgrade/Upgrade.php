<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Core\Session;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\File\Files;
use Dotclear\Schema\Schema;
use Exception;

/**
 * @brief   Dotclear upgrade procedure.
 *
 * This class is called from backend authentication page
 * or CLI command.
 */
class Upgrade
{
    /**
     * Do Dotclear upgrade if necessary.
     *
     * @throws  Exception
     */
    public static function dotclearUpgrade(): false|int
    {
        $version = App::version()->getVersion('core');

        if ($version === '') {
            return false;
        }

        if (version_compare($version, App::config()->dotclearVersion(), '<') == 1 || str_contains(App::config()->dotclearVersion(), 'dev')) {
            try {
                // Need to find a way to upgrade sqlite database
                $changes = 0;
                if (!str_contains(App::db()->con()->driver(), 'sqlite')) {
                    # Database upgrade
                    $_s = App::db()->structure();

                    # Fill database structrue
                    Schema::fillStructure($_s);

                    $changes = App::db()->structure()->synchronize($_s);
                }

                /* Some other upgrades
                ------------------------------------ */
                $cleanup_sessions = self::growUp($version);

                # Drop content from session table if changes or if needed (only if use Dotclear default session handler)
                if ($changes > 0 || $cleanup_sessions) {
                    $sql = new DeleteStatement();
                    $sql
                        ->from(App::db()->con()->prefix() . Session::SESSION_TABLE_NAME)
                    ->delete();
                }

                # Empty templates cache directory
                try {
                    App::cache()->emptyTemplatesCache();
                    App::cache()->emptyModulesStoreCache();
                } catch (Exception) {
                }

                return $changes;
            } catch (Exception $e) {
                throw new Exception(__('Something went wrong with auto upgrade:') . ' ' . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }

        # No upgrade?
        return false;
    }

    /**
     * Make necessary updates in DB and in filesystem.
     *
     * This method reads files from subfolder "GrowUp".
     *
     * @param   null|string     $version    The version
     *
     * @return  bool    True if a session cleanup is requested
     */
    public static function growUp(?string $version): bool
    {
        if ($version === '' || is_null($version)) {
            return false;
        }

        /**
         * Update it in a step that needed sessions to be removed
         */
        $cleanup_sessions = false;

        // Check upgrades by version
        foreach (self::getGrowUpVersions() as $upgrade) {
            // current version need upgrade
            if (version_compare($version, $upgrade['version'], $upgrade['equal'])) {
                require_once $upgrade['file'];
                $cleanup_sessions = $upgrade['class']::init($cleanup_sessions);
            }
        }

        // Set dc version
        App::version()->setVersion('core', App::config()->dotclearVersion());
        App::blogs()->blogDefaults();

        return $cleanup_sessions;
    }

    /**
     * @return  array<int, array<string, string>>
     */
    public static function getGrowUpVersions(): array
    {
        // Prepare upgrades scan
        $path = 'GrowUp';
        $dir  = implode(DIRECTORY_SEPARATOR, [__DIR__, $path, '']);
        $ns   = implode('\\', [__NAMESPACE__, $path, '']);

        // Scan GrowUp folder to find available upgrades
        $upgrades = [];
        foreach (Files::scandir($dir) as $file) {
            // Need only growup files
            if (!str_contains($file, $path . '_') || !str_contains($file, '.php')) {
                continue;
            }

            // Remove unwanted file name parts and split it by _
            $parts = explode('_', substr($file, 7, -4));

            $equal = '<';
            // remove eq or at least lt
            if (array_pop($parts) === 'eq') {
                $equal = '<=';
                // if eq exists remove also lt
                array_pop($parts);
            }

            $ver = '';
            foreach ($parts as $part) {
                // join by . numeric and _ alpha
                $ver .= (is_numeric($part) ? '.' : '-') . $part;
            }

            // set growup version info
            $upgrades[] = [
                'version' => substr($ver, 1),
                'equal'   => $equal,
                'file'    => $dir . $file,
                'class'   => $ns . substr($file, 0, -4),
            ];
        }

        // Sort growup versions
        usort($upgrades, fn (array $a, array $b): int => version_compare($a['version'], $b['version'], '>') ? 1 : -1);

        return $upgrades;
    }

    /**
     * Convert old-fashion serialized array setting to new-fashion json encoded array.
     *
     * @param   string  $ns         Settings workspace name
     * @param   string  $setting    The setting ID
     */
    public static function settings2array(string $ns, string $setting): void
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns([
                'setting_id',
                'blog_id',
                'setting_ns',
                'setting_type',
                'setting_value',
            ])
            ->from(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->where('setting_id =' . $sql->quote($setting))
            ->and('setting_ns = ' . $sql->quote($ns))
            ->and('setting_type = ' . $sql->quote('string'))
        ->select();

        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $value = @unserialize($rs->setting_value);
                if (!$value) {
                    $value = [];
                }
                $value = (array) $value;
                $value = json_encode($value, JSON_THROW_ON_ERROR);

                $sqlUpdate = new UpdateStatement();
                $sqlUpdate
                    ->from(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
                    ->sets([
                        'setting_type = ' . $sqlUpdate->quote('array'),
                        'setting_value = ' . $sqlUpdate->quote($value),
                    ])
                    ->where('setting_id =' . $sqlUpdate->quote($rs->setting_id))
                    ->and('setting_ns = ' . $sqlUpdate->quote($rs->setting_ns))
                    ->and((string) $rs->blog_id === '' ? $sqlUpdate->isNull('blog_id') : 'blog_id = ' . $sqlUpdate->quote($rs->blog_id))
                ->update();
            }
        }
    }

    /**
     * Convert old-fashion serialized array pref to new-fashion json encoded array.
     *
     * @param   string  $ws     Preferences workspace name
     * @param   string  $pref   The preference ID
     */
    public static function prefs2array(string $ws, string $pref): void
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns([
                'pref_id',
                'user_id',
                'pref_ws',
                'pref_type',
                'pref_value',
            ])
            ->from(App::db()->con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
            ->where('pref_id =' . $sql->quote($pref))
            ->and('pref_ws = ' . $sql->quote($ws))
            ->and('pref_type = ' . $sql->quote('string'))
        ->select();

        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $value = @unserialize($rs->pref_value);
                if (!$value) {
                    $value = [];
                }
                $value = (array) $value;
                $value = json_encode($value, JSON_THROW_ON_ERROR);

                $sqlUpdate = new UpdateStatement();
                $sqlUpdate
                    ->from(App::db()->con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
                    ->sets([
                        'pref_type = ' . $sqlUpdate->quote('array'),
                        'pref_value = ' . $sqlUpdate->quote($value),
                    ])
                    ->where('pref_id =' . $sqlUpdate->quote($rs->pref_id))
                    ->and('pref_ws = ' . $sqlUpdate->quote($rs->pref_ws))
                    ->and((string) $rs->user_id === '' ? $sqlUpdate->isNull('user_id') : 'user_id = ' . $sqlUpdate->quote($rs->user_id))
                ->update();
            }
        }
    }

    /**
     * Remove files and/or folders.
     *
     * @param   array<string>|null  $files      The files
     * @param   array<string>|null  $folders    The folders
     */
    public static function houseCleaning(?array $files = null, ?array $folders = null): void
    {
        if (App::config()->dotclearRoot() === '') {
            return;
        }

        // Files removing
        if (is_array($files)) {
            foreach ($files as $pattern) {
                $list = glob(App::config()->dotclearRoot() . '/' . $pattern);
                if ($list !== false) {
                    foreach ($list as $item) {
                        self::houseCleaningHelper($item, false);
                    }
                }
            }
        }

        // Folders removing
        if (is_array($folders)) {
            foreach ($folders as $pattern) {
                $list = glob(App::config()->dotclearRoot() . '/' . $pattern, GLOB_ONLYDIR);
                if ($list !== false) {
                    foreach ($list as $item) {
                        self::houseCleaningHelper($item, true);
                    }
                }
            }
        }
    }

    /**
     * Removing file/folder helper
     *
     * Note: if the file/folder cannot be removed it will be then renamed (by adding -OLD to its fullname)
     *
     * @param      string  $fullname  The fullname
     * @param      bool    $is_dir    Indicates if it is a folder
     */
    private static function houseCleaningHelper(string $fullname, bool $is_dir = false): void
    {
        if (file_exists($fullname)) {
            if ($is_dir && !is_dir($fullname)) {
                // Let some room for light mistakes (ie erroneous put files in directory list to be cleaned)
                $is_dir = false;
            }

            // First removal pass
            if ($is_dir) {
                // Delete folder
                Files::deltree($fullname);
            } else {
                // Delete file
                @unlink($fullname);
            }

            // Clear PHP file or folder information cache
            clearstatcache(true, $fullname);

            if (file_exists($fullname)) {
                // Try a second removal pass
                if ($is_dir) {
                    // Delete folder
                    Files::deltree($fullname);
                } else {
                    // Delete file
                    @unlink($fullname);
                }
            } else {
                return;
            }

            // Clear PHP file or folder information cache
            clearstatcache(true, $fullname);

            if (file_exists($fullname)) {
                // File or Folder still exists, try to rename it (adding -OLD to its fullname)
                // It will at least prevents .php files (renamed to .php-OLD) to be taken into account
                try {
                    rename($fullname, $fullname . '-OLD');
                } catch (Exception) {
                }
            }
        }
    }
}
