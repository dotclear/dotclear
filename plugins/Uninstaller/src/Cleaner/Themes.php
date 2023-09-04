<?php
/**
 * @brief Uninstaller, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller\Cleaner;

use Dotclear\App;
use Dotclear\Plugin\Uninstaller\{
    ActionDescriptor,
    CleanerDescriptor,
    CleanerParent,
    ValueDescriptor,
    Helper\DirTrait
};

/**
 * Cleaner for Dotclear themes.
 *
 * It allows modules to delete their own folder.
 */
class Themes extends CleanerParent
{
    use DirTrait;

    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'themes',
            name: __('Themes'),
            desc: __('Folders from blog themes directory'),
            actions: [
                // delete $ns theme folder
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected themes files and directories'),
                    query:   __('delete "%s" theme files and directories'),
                    success: __('"%s" theme files and directories deleted'),
                    error:   __('Failed to delete "%s" theme files and directories'),
                    default: true
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return explode(',', DC_DISTRIB_THEMES);
    }

    public function values(): array
    {
        if (($path = App::blog()->themesPath()) == '') {
            return [];
        }

        $stack = [];
        foreach ($dirs = self::getDirs($path) as $path => $count) {
            $stack[] = new ValueDescriptor(
                ns:    $path,
                count: $count
            );
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action != 'delete' || ($path = App::blog()->themesPath()) == '') {
            return false;
        }

        self::delDir($path, $ns, true);

        return true;
    }
}
