<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcLog
{
    // Constants

    /**
     * Table name
     *
     * @var        string
     */
    public const LOG_TABLE_NAME = 'log';

    // Properties

    /**
     * Full log table name (including db prefix)
     *
     * @var        string
     */
    protected $log_table;

    /**
     * Full user table name (including db prefix)
     *
     * @var        string
     */
    protected $user_table;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->log_table  = dcCore::app()->prefix . self::LOG_TABLE_NAME;
        $this->user_table = dcCore::app()->prefix . dcAuth::USER_TABLE_NAME;
    }

    /**
     * Retrieves logs.
     *
     * <b>$params</b> is an array taking the following optionnal parameters:
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
     * @return     dcRecord  The logs.
     */
    public function getLogs(array $params = [], bool $count_only = false): dcRecord
    {
        $sql = new dcSelectStatement();

        if ($count_only) {
            $sql->column($sql->count('log_id'));
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

        $sql->from($sql->alias($this->log_table, 'L'));

        if (!$count_only) {
            $sql->join(
                (new dcJoinStatement())
                ->left()
                ->from($sql->alias($this->user_table, 'U'))
                ->on('U.user_id = L.user_id')
                ->statement()
            );
        }

        if (!empty($params['blog_id'])) {
            if ($params['blog_id'] === '*') {
                // Nothing to add here
            } else {
                $sql->where('L.blog_id = ' . $sql->quote($params['blog_id']));
            }
        } else {
            $sql->where('L.blog_id = ' . $sql->quote(dcCore::app()->blog->id));
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
    public function addLog(cursor $cur): int
    {
        dcCore::app()->con->writeLock($this->log_table);

        try {
            # Get ID
            $sql = new dcSelectStatement();
            $sql
                ->column($sql->max('log_id'))
                ->from($this->log_table);

            $rs = $sql->select();

            $cur->log_id  = (int) $rs->f(0) + 1;
            $cur->blog_id = (string) dcCore::app()->blog->id;
            $cur->log_dt  = date('Y-m-d H:i:s');

            $this->fillLogCursor($cur);

            # --BEHAVIOR-- coreBeforeLogCreate
            dcCore::app()->callBehavior('coreBeforeLogCreate', $this, $cur);

            $cur->insert();
            dcCore::app()->con->unlock();
        } catch (Exception $e) {
            dcCore::app()->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- coreAfterLogCreate
        dcCore::app()->callBehavior('coreAfterLogCreate', $this, $cur);

        return $cur->log_id;
    }

    /**
     * Fills the log cursor.
     *
     * @param      cursor   $cur     The current
     *
     * @throws     Exception
     */
    private function fillLogCursor(cursor $cur)
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
    }

    /**
     * Deletes a log.
     *
     * @param      mixed    $id     The identifier
     * @param      bool     $all    Remove all logs
     */
    public function delLogs($id, bool $all = false)
    {
        if ($all) {
            $sql = new dcTruncateStatement();
            $sql
                ->from($this->log_table);
        } else {
            $sql = new dcDeleteStatement();
            $sql
                ->from($this->log_table)
                ->where('log_id ' . $sql->in($id));
        }

        $sql->run();
    }
}

/**
 * Extent log record class.
 */
class rsExtLog
{
    /**
     * Gets the user common name.
     *
     * @param      dcRecord  $rs     Invisible parameter
     *
     * @return     string  The user common name.
     */
    public static function getUserCN(dcRecord $rs): string
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
