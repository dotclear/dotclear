<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class flatExport
{
    private $con;
    private $prefix;

    private $line_reg = array('/\\\\/u', '/\n/u', '/\r/u', '/"/u');
    private $line_rep = array('\\\\\\\\', '\n', '\r', '\"');

    public $fp;

    public function __construct($con, $out = 'php://output', $prefix = null)
    {
        $this->con    = &$con;
        $this->prefix = $prefix;

        if (($this->fp = fopen($out, 'w')) === false) {
            return false;
        }
        @set_time_limit(300);
    }

    public function __destruct()
    {
        if (is_resource($this->fp)) {
            fclose($this->fp);
        }
    }

    public function export($name, $sql)
    {
        $rs = $this->con->select($sql);

        if (!$rs->isEmpty()) {
            fwrite($this->fp, "\n[" . $name . ' ' . implode(',', $rs->columns()) . "]\n");
            while ($rs->fetch()) {
                fwrite($this->fp, $this->getLine($rs));
            }
            fflush($this->fp);
        }
    }

    public function exportAll()
    {
        $tables = $this->getTables();

        foreach ($tables as $table) {
            $this->exportTable($table);
        }
    }

    public function exportTable($table)
    {
        $req = 'SELECT * FROM ' . $this->con->escapeSystem($this->prefix . $table);

        $this->export($table, $req);
    }

    public function getTables()
    {
        $schema    = dbSchema::init($this->con);
        $db_tables = $schema->getTables();

        $tables = array();
        foreach ($db_tables as $t) {
            if ($this->prefix) {
                if (strpos($t, $this->prefix) === 0) {
                    $tables[] = $t;
                }
            } else {
                $tables[] = $t;
            }
        }

        return $tables;
    }

    public function getLine($rs)
    {
        $l    = array();
        $cols = $rs->columns();
        foreach ($cols as $i => &$c) {
            $s     = $rs->f($c);
            $s     = preg_replace($this->line_reg, $this->line_rep, $s);
            $s     = '"' . $s . '"';
            $l[$i] = $s;
        }
        return implode(',', $l) . "\n";
    }
}
