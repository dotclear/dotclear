<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * dcNotices -- Backend notices handling facilities
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcNotices
{
    // Constants

    /**
     * Table name
     *
     * @var        string
     */
    protected const NOTICE_TABLE_NAME = 'notice';

    // Properties

    /**
     * Full table name (including db prefix)
     *
     * @var        string
     */
    protected $table;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->table = dcCore::app()->prefix . self::NOTICE_TABLE_NAME;
    }

    /**
     * Gets the table name
     *
     * @return     string
     */
    public function getTable(): string
    {
        return self::NOTICE_TABLE_NAME;
    }

    /* Get notices */

    /**
     * Gets the notices.
     *
     * @param      array              $params      The parameters
     * @param      bool               $count_only  The count only
     *
     * @return     dcRecord  The notices.
     */
    public function getNotices(array $params = [], bool $count_only = false): dcRecord
    {
        $sql = new dcSelectStatement();
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
                array_walk($params['notice_id'], function (&$v) { if ($v !== null) {$v = (int) $v;}});
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

        return $sql->select();
    }

    /**
     * Adds a notice.
     *
     * @param      cursor  $cur    The cursor
     *
     * @return     int     The notice id
     */
    public function addNotice(cursor $cur): int
    {
        dcCore::app()->con->writeLock($this->table);

        try {
            # Get ID
            $sql = new dcSelectStatement();
            $sql
                ->column($sql->max('notice_id'))
                ->from($this->table);

            $rs = $sql->select();

            $cur->notice_id = (int) $rs->f(0) + 1;
            $cur->ses_id    = (string) session_id();

            $this->fillNoticeCursor($cur, $cur->notice_id);

            # --BEHAVIOR-- coreBeforeNoticeCreate
            dcCore::app()->callBehavior('coreBeforeNoticeCreate', $this, $cur);

            $cur->insert();
            dcCore::app()->con->unlock();
        } catch (Exception $e) {
            dcCore::app()->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- coreAfterNoticeCreate
        dcCore::app()->callBehavior('coreAfterNoticeCreate', $this, $cur);

        return $cur->notice_id;
    }

    /**
     * Fills the notice cursor.
     *
     * @param      cursor     $cur        The current
     * @param      int        $notice_id  The notice identifier
     *
     * @throws     Exception
     */
    private function fillNoticeCursor(cursor $cur, ?int $notice_id = null): void
    {
        if ($cur->notice_msg === '') {
            throw new Exception(__('No notice message'));
        }

        if ($cur->notice_ts === '' || $cur->notice_ts === null) {
            $cur->notice_ts = date('Y-m-d H:i:s');
        }

        if ($cur->notice_format === '' || $cur->notice_format === null) {
            $cur->notice_format = 'text';
        }

        $notice_id = is_int($notice_id) ? $notice_id : $cur->notice_id;
    }

    /**
     * Delete notice(s)
     *
     * @param      int|null  $id     The identifier
     * @param      bool      $all    All
     */
    public function delNotices(?int $id, bool $all = false): void
    {
        $sql = new dcDeleteStatement();
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
