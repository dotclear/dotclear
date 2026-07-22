<?php

/**
 * @package Dotclear
 * @subpackage Core
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
use Dotclear\Interface\Core\NoticeInterface;
use Throwable;

/**
 * @brief   Core notice handler.
 *
 * @since   2.28, container services have been added to constructor
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Notice implements NoticeInterface
{
    /**
     * Full table name (including db prefix).
     */
    protected string $table;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core,
    ) {
        $this->table = $this->core->db()->con()->prefix() . self::NOTICE_TABLE_NAME;
    }

    public function openNoticeCursor(): Cursor
    {
        return $this->core->db()->con()->openCursor($this->table);
    }

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

        $session_id = isset($params['ses_id']) && is_string($session_id = $params['ses_id']) ? $session_id : '';
        if ($session_id === '') {
            $session_id = (string) session_id();
        }

        $sql->where('ses_id = ' . $sql->quote($session_id));

        if (isset($params['notice_id']) && $params['notice_id'] !== '') {
            $values = $sql->sanitizeIn($params['notice_id'], 'int', false);
            if ($values !== []) {
                $sql->and('notice_id' . $sql->in($values));
            }
        }

        if (!empty($params['notice_type'])) {
            $values = $sql->sanitizeIn($params['notice_type'], 'string', false);
            if ($values !== []) {
                $sql->and('notice_type' . $sql->in($values));
            }
        }

        if (!empty($params['notice_format'])) {
            $values = $sql->sanitizeIn($params['notice_format'], 'string', false);
            if ($values !== []) {
                $sql->and('notice_format' . $sql->in($values));
            }
        }

        if (!empty($params['sql']) && is_string($params['sql'])) {
            $sql->sql($params['sql']);
        }

        if (!$count_only) {
            if (!empty($params['order']) && is_string($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('notice_ts DESC');
            }
        }

        if (!empty($params['limit'])) {
            $values = is_array($params['limit']) ? array_values($params['limit']) : [$params['limit']];
            // Make $values an array of integer values
            $values = array_map(fn (mixed $v): int => is_numeric($v) ? (int) $v : 0, $values);

            /**
             * @var array{0: int, 1?: int}  $limit
             */
            $limit = [
                $values[0],
            ];
            if (isset($values[1])) {
                $limit[1] = $values[1];
            }

            $sql->limit($limit);
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }

    public function addNotice(Cursor $cur): int
    {
        $this->core->db()->con()->writeLock($this->table);

        try {
            # Get ID
            $sql = new SelectStatement();
            $sql
                ->column($sql->max('notice_id'))
                ->from($this->table);

            if (($rs = $sql->select()) instanceof MetaRecord) {
                $cur->notice_id = $rs->cardinal() + 1;
                $cur->ses_id    = (string) session_id();

                $this->fillNoticeCursor($cur);

                # --BEHAVIOR-- coreBeforeNoticeCreate -- Notice, Cursor
                $this->core->behavior()->callBehavior('coreBeforeNoticeCreate', $this, $cur);

                $cur->insert();
            }

            $this->core->db()->con()->unlock();
        } catch (Throwable $throwable) {
            $this->core->db()->con()->unlock();

            throw $throwable;
        }

        # --BEHAVIOR-- coreAfterNoticeCreate -- Notice, Cursor
        $this->core->behavior()->callBehavior('coreAfterNoticeCreate', $this, $cur);

        return is_numeric($notice_id = $cur->notice_id) ? (int) $notice_id : 0;
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
            throw new BadRequestException(__('No notice message.'));
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
        $this->core->deprecated()->set('App::notice()->delNotice() or App::notice()->delSessionNotices()', '2.28');

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
