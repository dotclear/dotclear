<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public static function dataProviderTemplate(): array
    {
        $fixtures = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Html', 'Template']);

        $list = [];
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fixtures),
            RecursiveIteratorIterator::LEAVES_ONLY
        ) as $file) {
            if (preg_match('/\.test$/', $file->getFilename())) {
                //yield $file->getRealpath();
                $list[] = [$file->getRealpath()];
            }
        }

        return $list;
    }

    #[DataProvider('dataProviderTemplate')]
    public function testTemplate(string $file)
    {
        //fwrite(STDOUT, 'being tested with : ' . $file . "\n");

        \Dotclear\Helper\Html\Template\TplNodeBlockDefinition::reset();

        $t = $this->parse($file);

        $dir      = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'tpl']);
        $cachedir = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'cbtpl']);

        @mkdir($dir);
        @mkdir($cachedir);

        $basetpl = '';
        foreach ($t['templates'] as $name => $content) {
            $targetdir  = $dir . '/' . dirname($name);
            $targetfile = basename($name);
            if (!is_dir($targetdir)) {
                @mkdir($targetdir, 0o777, true);
            }
            if ($basetpl == '') {
                $basetpl = $targetfile;
            }
            file_put_contents($targetdir . '/' . $targetfile, $content);
        }
        $GLOBALS['tpl']            = new \Dotclear\Helper\Html\Template\Template($cachedir, '$tpl');
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

        mockTemplates::register($GLOBALS['tpl']);
        if ($t['exception'] === false) {
            $result = $GLOBALS['tpl']->getData($basetpl);
            $this->assertEquals(
                mockTemplates::trimHereDoc($t['outputs'][0][1]),
                mockTemplates::trimHereDoc($result)
            );
        } else {
            $this->expectException(Exception::class);
            $GLOBALS['tpl']->getData($basetpl);
            $this->expectExceptionMessage(trim($t['exception']));
        }

        foreach ($t['templates'] as $name => $content) {
            unlink($dir . '/' . $name);
        }
        unset($GLOBALS['tpl']);
    }

    protected function parse(string $file)
    {
        $fixtures = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Html', 'Template']);

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
            throw new Exception(sprintf('Test "%s" is not valid.', str_replace($fixtures . '/', '', $file)));
        }

        $ret = [
            'name'      => str_replace($fixtures . '/', '', $file),
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

    public function TestGetPath()
    {
        $dir      = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'tplpath']);
        $cachedir = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'cbtplpath']);

        @mkdir($dir);
        @mkdir($cachedir);

        $GLOBALS['tpl']            = new \Dotclear\Helper\Html\Template\Template($cachedir, '$tpl');
        $GLOBALS['tpl']->use_cache = false;
        $GLOBALS['tpl']->setPath($dir);

        $path = $GLOBALS['tpl']->getPath();
        $this->assertEquals(
            $dir,
            $path[0]
        );
    }
}

class mockTemplates
{
    public static function register($tpl)
    {
        $tpl->addValue('echo', [self::class, 'tplecho']);
        $tpl->addBlock('loop', [self::class, 'tplloop']);
        $tpl->addBlock('entity', [self::class, 'tplentity']);
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
            $ret = self::tplecho($attr, '') . $ret;
        }

        return $ret;
    }

    public static function trimHereDoc($t)
    {
        return trim(implode("\n", array_map('trim', explode("\n", $t))));
    }
}
