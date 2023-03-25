<?php

# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# All rights reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****

namespace tests\unit;

use atoum;

require_once __DIR__ . '/../../../bootstrap.php';

require_once CLEARBRICKS_PATH . '/template/class.template.php';

require_once CLEARBRICKS_PATH . '/template/class.tplnode.php';
require_once CLEARBRICKS_PATH . '/template/class.tplnodeblock.php';
require_once CLEARBRICKS_PATH . '/template/class.tplnodeblockdef.php';
require_once CLEARBRICKS_PATH . '/template/class.tplnodevalue.php';
require_once CLEARBRICKS_PATH . '/template/class.tplnodevalueparent.php';
require_once CLEARBRICKS_PATH . '/template/class.tplnodetext.php';

require_once CLEARBRICKS_PATH . '/common/lib.l10n.php';

class template extends atoum
{
    protected $fixturesDir;
    /**
     * @dataProvider getTestTemplates
     */
    public function testTemplate($file)
    {
        $this->dump('being tested with : ' . $file . "\n");
        \tplNodeBlockDefinition::reset();
        $t        = $this->parse($file);
        $dir      = sys_get_temp_dir() . '/tpl';
        $cachedir = sys_get_temp_dir() . '/cbtpl';
        @mkdir($dir);
        @mkdir($cachedir);

        $basetpl = '';
        foreach ($t['templates'] as $name => $content) {
            $targetdir  = $dir . '/' . dirname($name);
            $targetfile = basename($name);
            if (!is_dir($targetdir)) {
                @mkdir($targetdir, 0777, true);
            }
            if ($basetpl == '') {
                $basetpl = $targetfile;
            }
            file_put_contents($targetdir . '/' . $targetfile, $content);
        }
        $GLOBALS['tpl']            = new \template($cachedir, '$tpl');
        $GLOBALS['tpl']->use_cache = false;
        if (empty($t['path'])) {
            $GLOBALS['tpl']->setPath($dir);
        } else {
            $path = [];
            foreach ($t['path'] as $p) {
                $path[] = $dir . '/' . trim($p);
            }
            $GLOBALS['tpl']->setPath($path);
        }

        testTpls::register($GLOBALS['tpl']);
        if ($t['exception'] === false) {
            $result = $GLOBALS['tpl']->getData($basetpl);
            $this
                ->string(testTpls::trimHereDoc($result))
                ->isEqualTo(testTpls::trimHereDoc($t['outputs'][0][1]));
        } else {
            $this
                ->exception(function () use ($basetpl) {
                    $result = $GLOBALS['tpl']->getData($basetpl);
                })
                ->hasMessage(trim($t['exception']));
        }
        foreach ($t['templates'] as $name => $content) {
            unlink($dir . '/' . $name);
        }
        unset($GLOBALS['tpl']);
    }

    protected function parse($file)
    {
        $test = file_get_contents($file);
        if (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)(?:--PATH--\s*(.*))?--EXCEPTION--\s*(.*)/s', $test, $match)) {
            $message   = $match[1];
            $condition = $match[2];
            $templates = $this->parseTemplates($match[3]);
            $path      = isset($match[4]) ? explode(';', $match[4]) : [];
            $exception = $match[5];
            //$outputs = array(array(null, $match[4], null, ''));
            $outputs = [];
        } elseif (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)(?:--PATH--\s*(.*))?--EXPECT--.*/s', $test, $match)) {
            $message   = $match[1];
            $condition = $match[2];
            $templates = $this->parseTemplates($match[3]);
            $path      = isset($match[4]) ? explode(';', $match[4]) : [];
            $exception = false;
            preg_match_all('/--EXPECT--\s*(.*?)$/s', $test, $outputs, PREG_SET_ORDER);
        } else {
            throw new \Exception(sprintf('Test "%s" is not valid.', str_replace($this->fixturesDir . '/', '', $file)));
        }

        $ret = [
            'name'      => str_replace($this->fixturesDir . '/', '', $file),
            'msg'       => $message,
            'condition' => $condition,
            'templates' => $templates,
            'exception' => $exception,
            'path'      => $path,
            'outputs'   => $outputs,
        ];

        return $ret;
    }

    protected function parseTemplates($test)
    {
        $templates = [];
        preg_match_all('/--TEMPLATE(?:\((.*?)\))?--\s*(.*?)(?=\-\-TEMPLATE|$)/s', $test, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $templates[($match[1] ? $match[1] : 'index.twig')] = $match[2];
        }

        return $templates;
    }

    public function getTestTemplates()
    {
        $this->fixturesDir = __DIR__ . '/../fixtures/templates';
        $tests             = [];

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->fixturesDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        ) as $file) {
            if (preg_match('/\.test$/', $file)) {
                $tests[] = $file->getRealpath();
            }
        }

        return $tests;
    }

    public function testGetPath()
    {
        $dir      = sys_get_temp_dir() . '/tpl';
        $cachedir = sys_get_temp_dir() . '/cbtpl';
        @mkdir($dir);
        @mkdir($cachedir);

        $GLOBALS['tpl']            = new \template($cachedir, '$tpl');
        $GLOBALS['tpl']->use_cache = false;

        $GLOBALS['tpl']->setPath($dir);
        $this
            ->array($GLOBALS['tpl']->getPath())
            ->string[0]->isEqualTo($dir);
    }
}

class testTpls
{
    public static function register($tpl)
    {
        $tpl->addValue('echo', ['tests\\unit\\testTpls', 'tplecho']);
        $tpl->addBlock('loop', ['tests\\unit\\testTpls', 'tplloop']);
        $tpl->addBlock('entity', ['tests\\unit\\testTpls', 'tplentity']);
    }

    public static function tplentity($attr, $content)
    {
        $ret = '<div';
        foreach ($attr as $k => $v) {
            $ret .= ' ' . $k . ($v ? '="' . $v . '"' : '');
        }
        $ret .= '>' . "\n" . $content . '</div>' . "\n";

        return $ret;
    }

    public static function tplecho($attr, $str)
    {
        $ret = '';
        $txt = [];
        foreach ($attr as $k => $v) {
            $txt[] = '"' . $k . '":"' . $v . '"';
        }
        if (!empty($txt)) {
            $ret .= '{' . join(',', $txt) . '}';
        }
        if (empty($attr)) {
            $ret .= '[' . $str . ']';
        }

        return $ret;
    }

    public static function tplloop($attr, $content)
    {
        $ret = '';
        if (isset($attr['times'])) {
            $times = (int) $attr['times'];
            for ($i = 0; $i < $times; $i++) {
                $ret .= $content;
            }
            unset($attr['times']);
        }
        if (!empty($attr)) {
            $ret = self::tplecho($attr) . $ret;
        }

        return $ret;
    }

    public static function trimHereDoc($t)
    {
        return trim(implode("\n", array_map('trim', explode("\n", $t))));
    }
}
