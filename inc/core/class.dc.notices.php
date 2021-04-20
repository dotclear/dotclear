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
    /** @var dcCore dotclear core instance */
    protected $core;
    protected $prefix;
    protected $table = 'notice';

    /**
     * Class constructor
     *
     * @param mixed  $core   dotclear core
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function __construct($core)
    {
        $this->core   = &$core;
        $this->prefix = $core->prefix;
    }

    public function getTable()
    {
        return $this->table;
    }

    /* Get notices */

    public function getNotices($params = [], $count_only = false)
    {
        // Return a recordset of notices
        if ($count_only) {
            $f = 'COUNT(notice_id)';
        } else {
            $f = 'notice_id, ses_id, notice_type, notice_ts, notice_msg, notice_format, notice_options';
        }

        $strReq = 'SELECT ' . $f . ' FROM ' . $this->prefix . $this->table . ' ';

        $strReq .= "WHERE ses_id = '";
        if (isset($params['ses_id']) && $params['ses_id'] !== '') {
            $strReq .= (string) $params['ses_id'];
        } else {
            $strReq .= (string) session_id();
        }
        $strReq .= "' ";

        if (isset($params['notice_id']) && $params['notice_id'] !== '') {
            if (is_array($params['notice_id'])) {
                array_walk($params['notice_id'], function (&$v, $k) { if ($v !== null) {$v = (integer) $v;}});
            } else {
                $params['notice_id'] = [(integer) $params['notice_id']];
            }
            $strReq .= 'AND notice_id' . $this->core->con->in($params['notice_id']);
        }

        if (!empty($params['notice_type'])) {
            $strReq .= 'AND notice_type' . $this->core->con->in($params['notice_type']);
        }

        if (!empty($params['notice_format'])) {
            $strReq .= 'AND notice_type' . $this->core->con->in($params['notice_format']);
        }

        if (!empty($params['sql'])) {
            $strReq .= ' ' . $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . $this->core->con->escape($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY notice_ts DESC ';
            }
        }

        if (!empty($params['limit'])) {
            $strReq .= $this->core->con->limit($params['limit']);
        }

        $rs = $this->core->con->select($strReq);

        return $rs;
    }

    public function addNotice($cur)
    {
        $this->core->con->writeLock($this->prefix . $this->table);

        try {
            # Get ID
            $rs = $this->core->con->select(
                'SELECT MAX(notice_id) ' .
                'FROM ' . $this->prefix . $this->table
            );

            $cur->notice_id = (integer) $rs->f(0) + 1;
            $cur->ses_id    = (string) session_id();

            $this->getNoticeCursor($cur, $cur->notice_id);

            # --BEHAVIOR-- coreBeforeNoticeCreate
            $this->core->callBehavior('coreBeforeNoticeCreate', $this, $cur);

            $cur->insert();
            $this->core->con->unlock();
        } catch (Exception $e) {
            $this->core->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- coreAfterNoticeCreate
        $this->core->callBehavior('coreAfterNoticeCreate', $this, $cur);

        return $cur->notice_id;
    }

    public function delNotices($id, $all = false)
    {
        $strReq = $all ?
        'DELETE FROM ' . $this->prefix . $this->table . " WHERE ses_id = '" . (string) session_id() . "'" :
        'DELETE FROM ' . $this->prefix . $this->table . ' WHERE notice_id' . $this->core->con->in($id);

        $this->core->con->execute($strReq);
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
