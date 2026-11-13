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
 * @brief   Cleaner for Dotclear credentials used by modules.
 * @ingroup Uninstaller
 *
 * It allows modules to delete a "credential_type"
 * of Dotclear App::credential()::CREDENTIAL_TABLE_NAME database table.
 */
class Credentials extends CleanerParent
{
    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'credentials',
            name: __('Credentials'),
            desc: __('Credentials in Dotclear credentials table'),
            actions: [
                // delete all $ns credential_type entries
                new ActionDescriptor(
                    id:      'delete_all',
                    query:   __('delete "%s" credentials'),
                    success: __('"%s" credentials deleted.'),
                    error:   __('Failed to delete "%s" credentials.'),
                    select:  __('delete selected credentials'),
                    default: true
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [
            'webauthn',
            'totp',
            'hotp',
            'oauth2_auth0connect',
            'oauth2_githubconnect',
            'oauth2_googleconnect',
            'oauth2_lwa',
            'oauth2_slackconnect',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(App::db()->con()->prefix() . App::credential()::CREDENTIAL_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
                'credential_type',
            ])
            ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
            ->order('credential_type ASC')
            ->group('credential_type');

        $record = $sql->select();
        if (is_null($record) || $record->isEmpty()) {
            return [];
        }

        $stack = [];
        while ($record->fetch()) {
            if ($record->strField('credential_type') !== '' && $record->intField('counter') > 0) {
                $stack[] = new ValueDescriptor(
                    ns:    $record->strField('credential_type'),
                    count: $record->intField('counter')
                );
            }
        }

        return $stack;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action === 'delete_all') {
            $sql = new DeleteStatement();
            $sql->from(App::db()->con()->prefix() . App::credential()::CREDENTIAL_TABLE_NAME)
                ->where('credential_type = ' . $sql->quote($ns))
                ->delete();

            return true;
        }

        return false;
    }
}
