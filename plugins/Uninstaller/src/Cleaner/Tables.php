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
use Dotclear\Database\Structure;
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
 * @brief   Cleaner for Dotclear cache directory used by modules.
 * @ingroup Uninstaller
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
        $schema = App::con()->schema();
        $tables = $schema->getTables();

        $stack = [];
        foreach ($tables as $k => $v) {
            // get only tables with dotclear prefix
            if ('' != App::con()->prefix()) {
                if (!preg_match('/^' . preg_quote(App::con()->prefix()) . '(.*?)$/', $v, $m)) { // @phpstan-ignore-line
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
        $struct = new Structure(App::con(), App::con()->prefix());
        $struct->reverse();
        $struct->getTables();
        if ($struct->tableExists($ns)) {
            if (in_array($action, ['empty', 'delete'])) {
                $sql = new DeleteStatement();
                $sql->from(App::con()->prefix() . $ns)
                    ->delete();
            }
            if ($action === 'empty') {
                return true;
            }
            if ($action === 'delete') {
                $sql = new DropStatement();
                $sql->from(App::con()->prefix() . $ns)
                    ->drop();

                return true;
            }
        }

        return false;
    }
}
