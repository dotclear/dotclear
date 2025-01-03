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
 * @brief   Cleaner for Dotclear themes.
 * @ingroup Uninstaller
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
        return explode(',', App::config()->distributedThemes());
    }

    public function values(): array
    {
        if (($path = App::blog()->themesPath()) === '') {
            return [];
        }

        $stack = [];
        foreach (self::getDirs($path) as $path => $count) {
            $stack[] = new ValueDescriptor(
                ns:    $path,
                count: $count
            );
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action !== 'delete' || ($path = App::blog()->themesPath()) === '') {
            return false;
        }

        self::delDir($path, $ns, true);

        return true;
    }
}
