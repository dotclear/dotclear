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

use dcLog;
use Dotclear\Core\Core;
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
 * Cleaner for Dotclear logs used by modules.
 *
 * It allows modules to delete a "log_table"
 * of Dotclear dcLog::LOG_TABLE_NAME database table.
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
            'dcDeprecated',
            'maintenance',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(Core::con()->prefix() . dcLog::LOG_TABLE_NAME)
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
        if ($action == 'delete_all') {
            $sql = new DeleteStatement();
            $sql->from(Core::con()->prefix() . dcLog::LOG_TABLE_NAME)
                ->where('log_table = ' . $sql->quote((string) $ns))
                //->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }

        return false;
    }
}
