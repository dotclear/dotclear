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
 * @brief   Cleaner for Dotclear VAR directory used by modules.
 * @ingroup Uninstaller
 *
 * It allows modules to delete an entire sub folder
 * of DC_VAR directory path.
 */
class Vars extends CleanerParent
{
    use DirTrait;

    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'vars',
            name: __('Var'),
            desc: __('Folders from Dotclear VAR directory'),
            actions: [
                // delete a $ns folder and their files
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected var directories'),
                    query:   __('delete "%s" var directory'),
                    success: __('"%s" var directory deleted'),
                    error:   __('Failed to delete "%s" var directory'),
                    default: true
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [];
    }

    public function values(): array
    {
        $stack = [];
        foreach (self::getDirs(App::config()->varRoot()) as $path => $count) {
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
            self::delDir(App::config()->varRoot(), $ns, true);

            return true;
        }

        return false;
    }
}
