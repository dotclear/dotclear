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

use dcCore;
use Dotclear\App;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Plugin\Uninstaller\{
    CleanerParent,
    ActionDescriptor,
    CleanerDescriptor,
    ValueDescriptor
};

/**
 * Cleaner for Dotclear modules versions.
 *
 * It allows modules to delete their versions
 * from Dotclear dcCore::VERSION_TABLE_NAME database table.
 */
class Versions extends CleanerParent
{
    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'versions',
            name: __('Versions'),
            desc: __('Versions registered in table "version" of Dotclear'),
            actions: [
                // delete $ns version
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected versions numbers'),
                    query:   __('delete "%s" version number'),
                    success: __('"%s" version number deleted'),
                    error:   __('Failed to delete "%s" version number'),
                    default: true
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return array_merge(
            ['core'],
            explode(',', DC_DISTRIB_THEMES),
            explode(',', DC_DISTRIB_PLUGINS)
        );
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->from(App::con()->prefix() . dcCore::VERSION_TABLE_NAME)
            ->columns(['module', 'version'])
            ->order('module ASC')
            ->select();

        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $stack = [];
        while ($rs->fetch()) {
            $stack[] = new ValueDescriptor(
                ns:    (string) $rs->f('module'),
                id:    (string) $rs->f('version'),
                count: 1
            );
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'delete') {
            App::con()->execute(
                'DELETE FROM  ' . App::con()->prefix() . dcCore::VERSION_TABLE_NAME . ' ' .
                "WHERE module = '" . App::con()->escapeStr((string) $ns) . "' "
            );

            return true;
        }

        return false;
    }
}
