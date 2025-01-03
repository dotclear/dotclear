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
use Dotclear\Database\Statement\{
    DeleteStatement,
    SelectStatement
};
use Dotclear\Plugin\Uninstaller\{
    ActionDescriptor,
    CleanerDescriptor,
    CleanerParent,
    ValueDescriptor
};

/**
 * @brief   Cleaner for Dotclear logs used by modules.
 * @ingroup Uninstaller
 *
 * It allows modules to delete a "log_table"
 * of Dotclear App::log()::LOG_TABLE_NAME database table.
 */
class Logs extends CleanerParent
{
    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'logs',
            name: __('Logs'),
            desc: __('Logs in Dotclear logs table'),
            actions: [
                // delete all $ns log_table entries
                new ActionDescriptor(
                    id:      'delete_all',
                    select:  __('delete selected logs tables'),
                    query:   __('delete "%s" logs table'),
                    success: __('"%s" logs table deleted'),
                    error:   __('Failed to delete "%s" logs table'),
                    default: true
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [
            'deprecated',
            'maintenance',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(App::con()->prefix() . App::log()::LOG_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
                'log_table',
            ])
            ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
            ->order('log_table ASC')
            ->group('log_table');

        $record = $sql->select();
        if (is_null($record) || $record->isEmpty()) {
            return [];
        }

        $stack = [];
        while ($record->fetch()) {
            if (is_string($record->f('log_table')) && is_numeric($record->f('counter'))) {
                $stack[] = new ValueDescriptor(
                    ns:    $record->f('log_table'),
                    count: (int) $record->f('counter')
                );
            }
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action === 'delete_all') {
            $sql = new DeleteStatement();
            $sql->from(App::con()->prefix() . App::log()::LOG_TABLE_NAME)
                ->where('log_table = ' . $sql->quote($ns))
                //->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }

        return false;
    }
}
