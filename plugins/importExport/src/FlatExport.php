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
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Exception;
use Dotclear\Database\AbstractSchema;
use Dotclear\Database\MetaRecord;

class FlatExport
{
    private $con;
    private $prefix;

    private array $line_reg = ['/\\\\/u', '/\n/u', '/\r/u', '/"/u'];
    private array $line_rep = ['\\\\\\\\', '\n', '\r', '\"'];

    public $fp;

    public function __construct($con, $out = 'php://output', $prefix = null)
    {
        $this->con    = &$con;
        $this->prefix = $prefix;

        if (($this->fp = fopen($out, 'w')) === false) {
            throw new Exception(__('Unable to create output file.'));
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
        $rs = new MetaRecord($this->con->select($sql));

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
        $schema    = AbstractSchema::init($this->con);
        $db_tables = $schema->getTables();

        $tables = [];
        foreach ($db_tables as $t) {
            if ($this->prefix) {
                if (str_starts_with($t, (string) $this->prefix)) {
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
        $l    = [];
        $cols = $rs->columns();
        foreach ($cols as $i => &$c) {
            $s     = $rs->f($c);
            $s     = preg_replace($this->line_reg, $this->line_rep, (string) $s);
            $s     = '"' . $s . '"';
            $l[$i] = $s;
        }

        return implode(',', $l) . "\n";
    }
}
