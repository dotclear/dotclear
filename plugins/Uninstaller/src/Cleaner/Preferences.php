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
 * @brief   Cleaner for Dotclear user preferences.
 * @ingroup Uninstaller
 *
 * It allows modules to delete for users or global a preference workspace.
 * It also allows to pick-up specific preference id by using delete_related action.
 */
class Preferences extends CleanerParent
{
    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'preferences',
            name: __('Preferences'),
            desc: __('Users preferences workspaces'),
            actions: [
                // delete global $ns preferences workspace
                new ActionDescriptor(
                    id:      'delete_global',
                    select:  __('delete selected global preferences workspaces'),
                    query:   __('delete "%s" global preferences workspace'),
                    success: __('"%s" global preferences workspace deleted'),
                    error:   __('Failed to delete "%s" global preferences workspace'),
                    default: false
                ),
                // delete users $ns preferences workspace
                new ActionDescriptor(
                    id:      'delete_local',
                    select:  __('delete selected users preferences workspaces'),
                    query:   __('delete "%s" users preferences workspace'),
                    success: __('"%s" users preferences workspace deleted'),
                    error:   __('Failed to delete "%s" users preferences workspace'),
                    default: false
                ),
                // delete user and global $ns preferences workspace
                new ActionDescriptor(
                    id:      'delete_all',
                    select:  __('delete selected preferences workspaces'),
                    query:   __('delete "%s" preferences workspace'),
                    success: __('"%s" preferences workspace deleted'),
                    error:   __('Failed to delete "%s" preferences workspace'),
                    default: false
                ),
                // delete users and globals specific $ws:$id settings using 'pref_ws:pref_id;pref_ws:pref_id;' as $ns
                new ActionDescriptor(
                    id:      'delete_related',
                    query:   __('delete related preferences'),
                    success: __('related preferences deleted'),
                    error:   __('Failed to delete related preferences'),
                    default: false
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [
            'accessibility',
            'interface',
            'maintenance',
            'profile',
            'dashboard',
            'favorites',
            'toggles',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
                'pref_ws',
            ])
            ->where($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
            ->order('pref_ws ASC')
            ->group('pref_ws');

        $record = $sql->select();
        if (is_null($record) || $record->isEmpty()) {
            return [];
        }

        $stack = [];
        while ($record->fetch()) {
            if (is_string($record->f('pref_ws')) && is_numeric($record->f('counter'))) {
                $stack[] = new ValueDescriptor(
                    ns:    $record->f('pref_ws'),
                    count: (int) $record->f('counter')
                );
            }
        }

        return $stack;
    }

    public function related(string $ns): array
    {
        $sql = new SelectStatement();
        $sql->from(App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
                'pref_id',
            ])
            ->where($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
            ->and('pref_ws = ' . $sql->quote($ns))
            ->group('pref_id');

        $record = $sql->select();
        if (is_null($record) || $record->isEmpty()) {
            return [];
        }

        $stack = [];
        while ($record->fetch()) {
            if (is_string($record->f('pref_id')) && is_numeric($record->f('counter'))) {
                $stack[] = new ValueDescriptor(
                    id:    $record->f('pref_id'),
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
            $sql->from(App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
                ->where('user_id IS NULL')
                ->and('pref_ws = ' . $sql->quote($ns))
                ->delete();

            return true;
        }
        if ($action === 'delete_local' && $this->checkNs($ns)) {
            $sql->from(App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
                ->where('user_id = ' . $sql->quote(App::blog()->id()))
                ->and('pref_ws = ' . $sql->quote($ns))
                ->delete();

            return true;
        }
        if ($action === 'delete_all' && $this->checkNs($ns)) {
            $sql->from(App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
                ->where('pref_ws = ' . $sql->quote($ns))
                ->and($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
                ->delete();

            return true;
        }
        if ($action === 'delete_related') {
            // check ns match ws:id;
            $reg_ws = substr(App::userWorkspace()::WS_NAME_SCHEMA, 2, -2);
            $reg_id = substr(App::userWorkspace()::WS_ID_SCHEMA, 2, -2);
            if (!preg_match_all('#((' . $reg_ws . '):(' . $reg_id . ');?)#', $ns, $matches)) {
                return false;
            }

            // build ws/id requests
            $or = [];
            foreach ($matches[2] as $key => $name) {
                $or[] = $sql->andGroup(['pref_ws = ' . $sql->quote($name), 'pref_id = ' . $sql->quote($matches[3][$key])]);
            }
            if ($or === []) {
                return false;
            }

            $sql->from(App::con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
                ->where($sql->orGroup($or))
                ->and($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
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
        return (bool) preg_match(App::userWorkspace()::WS_NAME_SCHEMA, $ns);
    }
}
