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
 * @brief   Cleaner for Dotclear blog settings.
 * @ingroup Uninstaller
 *
 * It allows modules to delete for blogs or global a settings namespace.
 * It also allows to pick-up specific setting id by using delete_related action.
 */
class Settings extends CleanerParent
{
    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'settings',
            name: __('Settings'),
            desc: __('Namespaces registered in dcSettings'),
            actions: [
                // delete global $ns settings namespace
                new ActionDescriptor(
                    id:      'delete_global',
                    select:  __('delete selected global settings namespaces'),
                    query:   __('delete "%s" global settings namespace'),
                    success: __('"%s" global settings namespace deleted'),
                    error:   __('Failed to delete "%s" global settings namespace'),
                    default: false
                ),
                // delete blogs $ns settings namespace
                new ActionDescriptor(
                    id:      'delete_local',
                    select:  __('delete selected blog settings namespaces'),
                    query:   __('delete "%s" blog settings namespace'),
                    success: __('"%s" blog settings namespace deleted'),
                    error:   __('Failed to delete "%s" blog settings namespace'),
                    default: false
                ),
                // delete blogs and global settings namespace
                new ActionDescriptor(
                    id:      'delete_all',
                    select:  __('delete selected settings namespaces'),
                    query:   __('delete "%s" settings namespace'),
                    success: __('"%s" settings namespace deleted'),
                    error:   __('Failed to delete "%s" settings namespace'),
                    default: false
                ),
                // delete blogs and globals specific $ns:$id settings using 'setting_ns:setting_id;setting_ns:setting_id;' as $ns
                new ActionDescriptor(
                    id:      'delete_related',
                    query:   __('delete related settings'),
                    success: __('related settings deleted'),
                    error:   __('Failed to delete related settings'),
                    default: false
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [
            'akismet',
            'antispam',
            'breadcrumb',
            'dcckeditor',
            'dclegacyeditor',
            'maintenance',
            'pages',
            'pings',
            'system',
            'themes',
            'Uninstaller',
            'widgets',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
                'setting_ns',
            ])
            ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
            ->order('setting_ns ASC')
            ->group('setting_ns');

        $record = $sql->select();
        if (is_null($record) || $record->isEmpty()) {
            return [];
        }

        $stack = [];
        while ($record->fetch()) {
            if (is_string($record->f('setting_ns')) && is_numeric($record->f('counter'))) {
                $stack[] = new ValueDescriptor(
                    ns:    $record->f('setting_ns'),
                    count: (int) $record->f('counter')
                );
            }
        }

        return $stack;
    }

    public function related(string $ns): array
    {
        $sql = new SelectStatement();
        $sql->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
                'setting_id',
            ])
            ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
            ->and('setting_ns = ' . $sql->quote($ns))
            ->group('setting_id');

        $record = $sql->select();
        if (is_null($record) || $record->isEmpty()) {
            return [];
        }

        $stack = [];
        while ($record->fetch()) {
            if (is_string($record->f('setting_id')) && is_numeric($record->f('counter'))) {
                $stack[] = new ValueDescriptor(
                    id:    $record->f('setting_id'),
                    count: (int) $record->f('counter')
                );
            }
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        $sql = new DeleteStatement();

        if ($action === 'delete_global' && $this->checkNs($ns)) {
            $sql->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
                ->where('blog_id IS NULL')
                ->and('setting_ns = ' . $sql->quote($ns))
                ->delete();

            return true;
        }
        if ($action === 'delete_local' && $this->checkNs($ns)) {
            $sql->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
                ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                ->and('setting_ns = ' . $sql->quote($ns))
                ->delete();

            return true;
        }
        if ($action === 'delete_all' && $this->checkNs($ns)) {
            $sql->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
                ->where('setting_ns = ' . $sql->quote($ns))
                ->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }
        if ($action === 'delete_related') {
            // check ns match ns:id;
            $reg_ws = substr(App::blogWorkspace()::NS_NAME_SCHEMA, 2, -2);
            $reg_id = substr(App::blogWorkspace()::NS_ID_SCHEMA, 2, -2);
            if (!preg_match_all('#((' . $reg_ws . '):(' . $reg_id . ');?)#', $ns, $matches)) {
                return false;
            }

            // build ws/id requests
            $or = [];
            foreach ($matches[2] as $key => $name) {
                $or[] = $sql->andGroup(['setting_ns = ' . $sql->quote($name), 'setting_id = ' . $sql->quote($matches[3][$key])]);
            }
            if ($or === []) {
                return false;
            }

            $sql->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
                ->where($sql->orGroup($or))
                ->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }

        return false;
    }

    /**
     * Check well formed ns.
     *
     * @param   string  $ns     The ns to check
     *
     * @return  bool    True on well formed
     */
    private function checkNs(string $ns): bool
    {
        return (bool) preg_match(App::blogWorkspace()::NS_NAME_SCHEMA, $ns);
    }
}
