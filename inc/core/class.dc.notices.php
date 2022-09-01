<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

/**
 * dcNotices -- Backend notices handling facilities
 */
class dcNotices
{
    protected $table_name = 'notice';
    protected $table;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->table = dcCore::app()->prefix . $this->table_name;
    }

    public function getTable()
    {
        return $this->table_name;
    }

    /* Get notices */

    public function getNotices($params = [], $count_only = false)
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

        $rs = $sql->select();

        return $rs;
    }

    public function addNotice($cur)
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

            $this->getNoticeCursor($cur, $cur->notice_id);

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

    public function delNotices($id, $all = false)
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

    private function getNoticeCursor($cur, $notice_id = null)
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
}
