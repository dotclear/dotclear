<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcLog
{
    protected $core;
    protected $con;
    protected $log_table;
    protected $user_table;

    /**
     * Constructs a new instance.
     *
     * @param      dcCore  $core   The core
     */
    public function __construct(dcCore $core)
    {
        $this->core       = &$core;
        $this->con        = &$core->con;
        $this->log_table  = $core->prefix . 'log';
        $this->user_table = $core->prefix . 'user';
    }

    /**
     * Retrieves logs. <b>$params</b> is an array taking the following
     * optionnal parameters:
     *
     * - blog_id: Get logs belonging to given blog ID
     * - user_id: Get logs belonging to given user ID
     * - log_ip: Get logs belonging to given IP address
     * - log_table: Get logs belonging to given log table
     * - order: Order of results (default "ORDER BY log_dt DESC")
     * - limit: Limit parameter
     *
     * @param      array   $params      The parameters
     * @param      bool    $count_only  Count only resultats
     *
     * @return     record  The logs.
     */
    public function getLogs($params = [], $count_only = false)
    {
        $sql = new dcSelectStatement($this->core, 'dcLogGetLogs');

        if ($count_only) {
            $sql->column('COUNT(log_id)');
        } else {
            $sql->columns([
                'L.log_id',
                'L.user_id',
                'L.log_table',
                'L.log_dt',
                'L.log_ip',
                'L.log_msg',
                'L.blog_id',
                'U.user_name',
                'U.user_firstname',
                'U.user_displayname',
                'U.user_url',
            ]);
        }

        $sql->from($this->log_table . ' L');

        if (!$count_only) {
            $sql->join(
                (new dcJoinStatement($this->core, 'dcLogGetLogs'))
                ->type('LEFT')
                ->from($this->user_table . ' U')
                ->on('U.user_id = L.user_id')
                ->statement()
            );
        }

        if (!empty($params['blog_id'])) {
            if ($params['blog_id'] === '*') {
            } else {
                $sql->where('L.blog_id = ' . $sql->quote($params['blog_id']));
            }
        } else {
            $sql->where('L.blog_id = ' . $sql->quote($this->core->blog->id));
        }

        if (!empty($params['user_id'])) {
            $sql->and('L.user_id' . $sql->in($params['user_id']));
        }
        if (!empty($params['log_ip'])) {
            $sql->and('log_ip' . $sql->in($params['log_ip']));
        }
        if (!empty($params['log_table'])) {
            $sql->and('log_table' . $sql->in($params['log_table']));
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('log_dt DESC');
            }
        }

        if (!empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        $rs = $sql->select();
        $rs->extend('rsExtLog');

        return $rs;
    }

    /**
     * Creates a new log. Takes a cursor as input and returns the new log ID.
     *
     * @param      cursor  $cur    The current
     *
     * @return     integer
     */
    public function addLog($cur)
    {
        $this->con->writeLock($this->log_table);

        try {
            # Get ID
            $sql = new dcSelectStatement($this->core, 'dcLogAddLog');
            $sql
                ->column('MAX(log_id)')
                ->from($this->log_table);

            $rs = $sql->select();

            $cur->log_id  = (int) $rs->f(0) + 1;
            $cur->blog_id = (string) $this->core->blog->id;
            $cur->log_dt  = date('Y-m-d H:i:s');

            $this->getLogCursor($cur, $cur->log_id);

            # --BEHAVIOR-- coreBeforeLogCreate
            $this->core->callBehavior('coreBeforeLogCreate', $this, $cur);

            $cur->insert();
            $this->con->unlock();
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- coreAfterLogCreate
        $this->core->callBehavior('coreAfterLogCreate', $this, $cur);

        return $cur->log_id;
    }

    /**
     * Deletes a log.
     *
     * @param      mixed    $id     The identifier
     * @param      bool     $all    Remove all logs
     */
    public function delLogs($id, $all = false)
    {
        if ($all) {
            $sql = new dcTruncateStatement($this->core, 'dcLogDelLogs');
            $sql
                ->from($this->log_table);
        } else {
            $sql = new dcDeleteStatement($this->core, 'dcLogDelLogs');
            $sql
                ->from($this->log_table)
                ->where('log_id ' . $sql->in($id));
        }

        $sql->run();
    }

    /**
     * Gets the log cursor.
     *
     * @param      cursor     $cur     The current
     * @param      mixed      $log_id  The log identifier
     *
     * @throws     Exception
     */
    private function getLogCursor($cur, $log_id = null)
    {
        if ($cur->log_msg === '') {
            throw new Exception(__('No log message'));
        }

        if ($cur->log_table === null) {
            $cur->log_table = 'none';
        }

        if ($cur->user_id === null) {
            $cur->user_id = 'unknown';
        }

        if ($cur->log_dt === '' || $cur->log_dt === null) {
            $cur->log_dt = date('Y-m-d H:i:s');
        }

        if ($cur->log_ip === null) {
            $cur->log_ip = http::realIP();
        }

        $log_id = is_int($log_id) ? $log_id : $cur->log_id;
    }
}

/**
 * Extent log record class.
 */
class rsExtLog
{
    /**
     * Gets the user cn.
     *
     * @param      record  $rs     Invisible parameter
     *
     * @return     string  The user cn.
     */
    public static function getUserCN($rs)
    {
        $user = dcUtils::getUserCN(
            $rs->user_id,
            $rs->user_name,
            $rs->user_firstname,
            $rs->user_displayname
        );

        if ($user === 'unknown') {
            $user = __('unknown');
        }

        return $user;
    }
}
