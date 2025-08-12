<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\CredentialInterface;
use Exception;

/**
 * @brief   User credentials handler.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Credential implements CredentialInterface
{
    private string $credential_table;

    /**
     * Load services from Core container.
     *
     * @param   BehaviorInterface       $behavior   The behavior instance
     * @param   BlogInterface           $blog       The blog instance
     * @param   ConnectionInterface     $con        The database connection instance
     */
    public function __construct(
        protected BehaviorInterface $behavior,
        protected BlogInterface $blog,
        protected ConnectionInterface $con
    ) {
        $this->credential_table = $this->con->prefix() . self::CREDENTIAL_TABLE_NAME;
    }

    public function openCredentialCursor(): Cursor
    {
        return $this->con->openCursor($this->credential_table);
    }

    public function getCredentials(array|ArrayObject $params = [], bool $count_only = false): MetaRecord
    {
        $sql = new SelectStatement();

        if ($count_only) {
            $sql
                ->column($sql->count('K.user_id'))
                ->from($sql->as($this->credential_table, 'K'))
                ->where('NULL IS NULL');
        } else {
            $sql
                ->columns([
                    'K.user_id',
                    //'credential_dt',
                    'credential_id',
                    'credential_type',
                    'credential_data'
                ])
                ->from($sql->as($this->credential_table, 'K'));

            if (!empty($params['columns'])) {
                $sql->columns($params['columns']);
            }
            $sql
                ->join(
                    (new JoinStatement())
                        ->left()
                        ->from($sql->as($this->con->prefix() . $this->blog->auth()::USER_TABLE_NAME, 'U'))
                        ->on('K.user_id = U.user_id')
                        ->statement()
                )
                ->where('NULL IS NULL');
        }

        if (empty($params['credential_type'])) {
            $params['credential_type'] = 'webauthn';
        }
        $sql->where('credential_type =' . $sql->quote($params['credential_type']));

        if (!empty($params['user_id'])) {
            $sql->and('K.user_id =' . $sql->quote($params['user_id']));
        }

        if (!empty($params['credential_id'])) {
            if (!is_array($params['credential_id'])) {
                $params['credential_id'] = [$params['credential_id']];
            }
            $sql->and('credential_id' . $sql->in($params['credential_id']));
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('K.user_id ASC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }

    public function setCredential(string $user_id, Cursor $cur): void
    {
        if ('' == $cur->getField('credential_id')) {
            throw new Exception('Invalid credential id');
        }

        if (null === $cur->getField('credential_type')) {
            $cur->setField('credential_type', 'webauthn');
        }

        if (null === $cur->getField('user_id')) {
            $cur->setField('user_id', $this->blog->auth()->userID());
        }

        if ('' == $cur->getField('user_id')) {
            throw new Exception('Invalid user id');
        }

        // todo: delete previous record with same credential id

        $cur->insert();
    }

    public function delCredential(string $credential_type, string $credential_id): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->credential_table)
            ->where('credential_type = ' . $sql->quote($credential_type))
            ->and('credential_id = ' . $sql->quote($credential_id))
            ->delete();
    }
}