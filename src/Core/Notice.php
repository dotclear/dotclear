<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Exception\BadRequestException;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\DeprecatedInterface;
use Dotclear\Interface\Core\NoticeInterface;
use Throwable;

/**
 * @brief   Core notice handler.
 *
 * @since   2.28, container services have been added to constructor
 */
class Notice implements NoticeInterface
{
    /**
     * Full table name (including db prefix).
     */
    protected string $table;

    /**
     * Constructor.
     *
     * @param   BehaviorInterface       $behavior       The behavior instance
     * @param   ConnectionInterface     $con            The database connection instance
     * @param   DeprecatedInterface     $deprecated     The deprecated handler
     */
    public function __construct(
        protected BehaviorInterface $behavior,
        protected ConnectionInterface $con,
        protected DeprecatedInterface $deprecated,
    ) {
        $this->table = $this->con->prefix() . self::NOTICE_TABLE_NAME;
    }

    public function openNoticeCursor(): Cursor
    {
        return $this->con->openCursor($this->table);
    }

    /**
     * Gets the notices.
     *
     * @param      array<string, mixed>     $params      The parameters
     * @param      bool                     $count_only  The count only
     *
     * @return     MetaRecord  The notices.
     */
    public function getNotices(array $params = [], bool $count_only = false): MetaRecord
    {
        $sql = new SelectStatement();
        $sql
            ->from($this->table);

        // Return a recordset of notices
        if ($count_only) {
            $sql->column($sql->count('notice_id'));
        } else {
            $sql->columns([
                'notice_id',
                'ses_id',
                'notice_type',
                'notice_ts',
                'notice_msg',
                'notice_format',
                'notice_options',
            ]);
        }

        $session_id = isset($params['ses_id']) && $params['ses_id'] !== '' ? (string) $params['ses_id'] : (string) session_id();
        $sql->where('ses_id = ' . $sql->quote($session_id));

        if (isset($params['notice_id']) && $params['notice_id'] !== '') {
            if (is_array($params['notice_id'])) {
                array_walk($params['notice_id'], function (&$v): void { if ($v !== null) {$v = (int) $v;}});
            } else {
                $params['notice_id'] = [(int) $params['notice_id']];
            }
            $sql->and('notice_id' . $sql->in($params['notice_id']));
        }

        if (!empty($params['notice_type'])) {
            $sql->and('notice_type' . $sql->in($params['notice_type']));
        }

        if (!empty($params['notice_format'])) {
            $sql->and('notice_format' . $sql->in($params['notice_format']));
        }

        if (!empty($params['sql'])) {
            $sql->sql($params['sql']);
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('notice_ts DESC');
            }
        }

        if (!empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }

    public function addNotice(Cursor $cur): int
    {
        $this->con->writeLock($this->table);

        try {
            # Get ID
            $sql = new SelectStatement();
            $sql
                ->column($sql->max('notice_id'))
                ->from($this->table);

            if (($rs = $sql->select()) instanceof MetaRecord) {
                $cur->notice_id = (int) $rs->f(0) + 1;
                $cur->ses_id    = (string) session_id();

                $this->fillNoticeCursor($cur);

                # --BEHAVIOR-- coreBeforeNoticeCreate -- Notice, Cursor
                $this->behavior->callBehavior('coreBeforeNoticeCreate', $this, $cur);

                $cur->insert();
            }
            $this->con->unlock();
        } catch (Throwable $e) {
            $this->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- coreAfterNoticeCreate -- Notice, Cursor
        $this->behavior->callBehavior('coreAfterNoticeCreate', $this, $cur);

        return $cur->notice_id;
    }

    /**
     * Fills the notice Cursor.
     *
     * @param      Cursor     $cur        The current
     *
     * @throws     BadRequestException
     */
    private function fillNoticeCursor(Cursor $cur): void
    {
        if ($cur->notice_msg === '') {
            throw new BadRequestException(__('No notice message'));
        }

        if ($cur->notice_ts === '' || $cur->notice_ts === null) {
            $cur->notice_ts = date('Y-m-d H:i:s');
        }

        if ($cur->notice_format === '' || $cur->notice_format === null) {
            $cur->notice_format = 'text';
        }
    }

    public function delNotice(int $id): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('notice_id = ' . $id)
            ->delete();
    }

    public function delSessionNotices(): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('ses_id = ' . $sql->quote((string) session_id()))
            ->delete();
    }

    /**
     * @deprecated since 2.28 use self::delNotice() or self::delSessionNotices()
     */
    public function delNotices(?int $id, bool $all = false): void
    {
        $this->deprecated->set('App::notice()->delNotice() or App::notice()->delSessionNotices()', '2.28');

        $sql = new DeleteStatement();
        $sql
            ->from($this->table);

        if ($all) {
            $sql->where('ses_id = ' . $sql->quote((string) session_id()));
        } else {
            $sql->where('notice_id' . $sql->in($id));
        }

        $sql->delete();
    }
}
