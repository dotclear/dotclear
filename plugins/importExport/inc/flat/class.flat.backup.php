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

class flatBackup
{
    protected $fp;
    private $line_cols = array();
    private $line_name;
    private $line_num;

    private $replacement = array(
        '/(?<!\\\\)(?>(\\\\\\\\)*+)(\\\\n)/u' => "\$1\n",
        '/(?<!\\\\)(?>(\\\\\\\\)*+)(\\\\r)/u' => "\$1\r",
        '/(?<!\\\\)(?>(\\\\\\\\)*+)(\\\\")/u' => '$1"',
        '/(\\\\\\\\)/'                        => '\\'
    );

    public function __construct($file)
    {
        if (file_exists($file) && is_readable($file)) {
            $this->fp       = fopen($file, 'rb');
            $this->line_num = 1;
        } else {
            throw new Exception(__('No file to read.'));
        }
    }

    public function __destruct()
    {
        if ($this->fp) {
            @fclose($this->fp);
        }
    }

    public function getLine()
    {
        if (($line = $this->nextLine()) === false) {
            return false;
        }

        if (substr($line, 0, 1) == '[') {
            $this->line_name = substr($line, 1, strpos($line, ' ') - 1);

            $line            = substr($line, strpos($line, ' ') + 1, -1);
            $this->line_cols = explode(',', $line);

            return $this->getLine();
        } elseif (substr($line, 0, 1) == '"') {
            $line = preg_replace('/^"|"$/', '', $line);
            $line = preg_split('/(^"|","|(?<!\\\)\"$)/m', $line);

            if (count($this->line_cols) != count($line)) {
                throw new Exception(sprintf('Invalid row count at line %s', $this->line_num));
            }

            $res = array();

            for ($i = 0; $i < count($line); $i++) {
                $res[$this->line_cols[$i]] =
                    preg_replace(array_keys($this->replacement), array_values($this->replacement), $line[$i]);
            }

            return new flatBackupItem($this->line_name, $res, $this->line_num);
        } else {
            return $this->getLine();
        }
    }

    private function nextLine()
    {
        if (feof($this->fp)) {
            return false;
        }
        $this->line_num++;

        $line = fgets($this->fp);
        $line = trim($line);

        return empty($line) ? $this->nextLine() : $line;
    }
}

class flatBackupItem
{
    public $__name;
    public $__line;
    private $__data = array();

    public function __construct($name, $data, $line)
    {
        $this->__name = $name;
        $this->__data = $data;
        $this->__line = $line;
    }

    public function f($name)
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', $this->__data[$name]);
    }

    public function __get($name)
    {
        return $this->f($name);
    }

    public function __set($n, $v)
    {
        $this->__data[$n] = $v;
    }

    public function exists($n)
    {
        return isset($this->__data[$n]);
    }

    public function drop()
    {
        foreach (func_get_args() as $n) {
            if (isset($this->__data[$n])) {
                unset($this->__data[$n]);
            }
        }
    }

    public function substitute($old, $new)
    {
        if (isset($this->__data[$old])) {
            $this->__data[$new] = $this->__data[$old];
            unset($this->__data[$old]);
        }
    }
}
