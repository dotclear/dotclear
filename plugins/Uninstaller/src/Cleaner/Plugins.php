<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
 * @brief   Cleaner for Dotclear plugins.
 * @ingroup Uninstaller
 *
 * It allows modules to delete their own folder.
 */
class Plugins extends CleanerParent
{
    use DirTrait;

    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'plugins',
            name: __('Plugins'),
            desc: __('Folders from plugins directories'),
            actions: [
                // delete $ns plugin folder
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected plugins files and directories'),
                    query:   __('delete "%s" plugin files and directories'),
                    success: __('"%s" plugin files and directories deleted'),
                    error:   __('Failed to delete "%s" plugin files and directories'),
                    default: true
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return explode(',', App::config()->distributedPlugins());
    }

    public function values(): array
    {
        $stack = [];
        foreach (self::getDirs(App::config()->pluginsRoot()) as $path => $count) {
            $stack[] = new ValueDescriptor(
                ns:    $path,
                count: $count
            );
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action === 'delete') {
            self::delDir(App::config()->pluginsRoot(), $ns, true);

            return true;
        }

        return false;
    }
}
