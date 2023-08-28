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

use Dotclear\Core\Core;
use Dotclear\Database\{
    AbstractSchema,
    Structure
};
use Dotclear\Database\Statement\{
    DeleteStatement,
    DropStatement,
    SelectStatement
};
use Dotclear\Plugin\Uninstaller\{
    ActionDescriptor,
    CleanerDescriptor,
    CleanerParent,
    ValueDescriptor
};

/**
 * Cleaner for Dotclear cache directory used by modules.
 *
 * It allows modules to delete or truncate a database table.
 */
class Tables extends CleanerParent
{
    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'tables',
            name: __('Tables'),
            desc: __('All database tables of Dotclear'),
            actions: [
                // delete $ns database table
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected tables'),
                    query:   __('delete "%s" table'),
                    success: __('"%s" table deleted'),
                    error:   __('Failed to delete "%s" table'),
                    default: false
                ),
                // truncate (empty) $ns database table
                new ActionDescriptor(
                    id:      'empty',
                    select:  __('empty selected tables'),
                    query:   __('empty "%s" table'),
                    success: __('"%s" table emptied'),
                    error:   __('Failed to empty "%s" table'),
                    default: false
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [
            'blog',
            'category',
            'comment',
            'link',
            'log',
            'media',
            'meta',
            'permissions',
            'ping',
            'post',
            'post_media',
            'pref',
            'session',
            'setting',
            'spamrule',
            'user',
            'version',
        ];
    }

    public function values(): array
    {
        $schema = AbstractSchema::init(Core::con());
        $tables = $schema->getTables();

        $stack = [];
        foreach ($tables as $k => $v) {
            // get only tables with dotclear prefix
            if ('' != Core::con()->prefix()) {
                if (!preg_match('/^' . preg_quote(Core::con()->prefix()) . '(.*?)$/', $v, $m)) {
                    continue;
                }
                $v = $m[1];
            }

            $sql   = new SelectStatement();
            $count = $sql->from($tables[$k])->fields([$sql->count('*')])->select()?->f(0);

            $stack[] = new ValueDescriptor(
                ns:    (string) $v,
                count: is_numeric($count) ? (int) $count : 0
            );
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        if (in_array($action, ['empty', 'delete'])) {
            $sql = new DeleteStatement();
            $sql->from(Core::con()->prefix() . $ns)
                ->delete();
        }
        if ($action == 'empty') {
            return true;
        }
        if ($action == 'delete') {
            $struct = new Structure(Core::con(), Core::con()->prefix());
            if ($struct->tableExists($ns)) {
                $sql = new DropStatement();
                $sql->from(Core::con()->prefix() . $ns)
                    ->drop();
            }

            return true;
        }

        return false;
    }
}
