<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Template;

use ArrayObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    /**
     * @return list<list<string>>
     */
    public static function dataProviderTemplate(): array
    {
        $fixtures = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Html', 'Template']);

        $list = [];
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fixtures),
            \RecursiveIteratorIterator::LEAVES_ONLY
        ) as $file) {
            if (preg_match('/\.test$/', $file->getFilename())) {
                //yield $file->getRealpath();
                $list[] = [$file->getRealpath()];
            }
        }

        return $list;
    }

    #[DataProvider('dataProviderTemplate')]
    public function testTemplate(string $file): void
    {
        //fwrite(STDOUT, 'being tested with : ' . $file . "\n");

        \Dotclear\Helper\Html\Template\TplNodeBlockDefinition::reset();

        $t = $this->parse($file);

        $dir      = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'tpl']);
        $cachedir = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'cbtpl']);

        if (!is_dir($dir)) {
            @mkdir($dir);
        }
        if (!is_dir($cachedir)) {
            @mkdir($cachedir);
        }

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
            $this->expectException(\Exception::class);
            $GLOBALS['tpl']->getData($basetpl);
            $this->expectExceptionMessage(trim($t['exception']));
        }

        foreach ($t['templates'] as $name => $content) {
            unlink($dir . '/' . $name);
        }
        unset($GLOBALS['tpl']);

        if (is_dir($dir)) {
            \Dotclear\Helper\File\Files::deltree($dir);
        }
        if (is_dir($cachedir)) {
            \Dotclear\Helper\File\Files::deltree($cachedir);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function parse(string $file): array
    {
        $fixtures = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Html', 'Template']);

        $test = (string) file_get_contents($file);
        if (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)(?:--PATH--\s*(.*))?--EXCEPTION--\s*(.*)/s', $test, $match)) {
            $message   = $match[1];
            $condition = $match[2];
            $templates = $this->parseTemplates($match[3]);
            // @phpstan-ignore isset.offset
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
            throw new \Exception(sprintf('Test "%s" is not valid.', str_replace($fixtures . '/', '', $file)));
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

    /**
     * @return array<string, string>
     */
    protected function parseTemplates(string $test): array
    {
        $templates = [];
        preg_match_all('/--TEMPLATE(?:\((.*?)\))?--\s*(.*?)(?=\-\-TEMPLATE|$)/s', $test, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $templates[($match[1] ? $match[1] : 'index.twig')] = $match[2];
        }

        return $templates;
    }

    public function testGetPath(): void
    {
        $dir      = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'tplpath']);
        $cachedir = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), 'cbtplpath']);

        if (!is_dir($dir)) {
            @mkdir($dir);
        }
        if (!is_dir($cachedir)) {
            @mkdir($cachedir);
        }

        $GLOBALS['tpl']            = new \Dotclear\Helper\Html\Template\Template($cachedir, '$tpl');
        $GLOBALS['tpl']->use_cache = false;
        $GLOBALS['tpl']->setPath($dir);

        $path = $GLOBALS['tpl']->getPath();
        $this->assertEquals(
            $dir,
            $path[0]
        );

        if (is_dir($dir)) {
            \Dotclear\Helper\File\Files::deltree($dir);
        }
        if (is_dir($cachedir)) {
            \Dotclear\Helper\File\Files::deltree($cachedir);
        }
    }

    public function testTemplatePathAndCallbackHelpers(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'template-unit-' . bin2hex(random_bytes(6));
        $first = $root . DIRECTORY_SEPARATOR . 'first';
        $second = $root . DIRECTORY_SEPARATOR . 'second';
        mkdir($first, 0o777, true);
        mkdir($second, 0o777, true);
        file_put_contents($first . DIRECTORY_SEPARATOR . 'included.tpl', 'included');

        try {
            $tpl = new \Dotclear\Helper\Html\Template\Template($root, '$tpl');
            $tpl->setPath($first, [$second, 42, $first]);
            $this->assertSame([$first, $second], $tpl->getPath());

            $tpl->prependPath($second);
            $tpl->appendPath($first);
            $this->assertSame([$second, $first], $tpl->getPath());
            $this->assertSame($first . '/included.tpl', $tpl->getFilePath('included.tpl'));
            $this->assertSame($first . '/included.tpl', $tpl->getParentFilePath($second, 'included.tpl'));
            $this->assertFalse($tpl->getParentFilePath('/missing', 'included.tpl'));
            $this->assertSame('', $tpl->includeFile([]));
            $this->assertSame('', $tpl->includeFile(['src' => '']));
            $this->assertSame('', $tpl->includeFile(['src' => 'missing.tpl']));
            $this->assertStringContainsString("getData('included.tpl')", $tpl->includeFile(['src' => 'included.tpl']));
            $this->assertSame('content', $tpl->blockSection([], 'content'));

            $block = static fn (array $attr, string $content): string => $content . ' block';
            $value = static fn (array $attr, string $str): string => $str;
            $tpl->addBlocks([0 => $block, 'known' => $block]);
            $tpl->addValues([0 => $value, 'value' => $value]);

            $this->assertTrue($tpl->blockExists('known'));
            $this->assertTrue($tpl->valueExists('value'));
            $this->assertTrue($tpl->tagExists('known'));
            $this->assertTrue($tpl->tagExists('value'));
            $this->assertFalse($tpl->tagExists('missing'));
            $this->assertSame(['Block', 'known'], $tpl->getBlocksList());
            $this->assertSame(['include', 'value'], $tpl->getValuesList());
            $this->assertSame($block, $tpl->getBlockCallback('known'));
            $this->assertFalse($tpl->getBlockCallback('missing'));
            $this->assertSame($value, $tpl->getValueCallback('value'));
            $this->assertFalse($tpl->getValueCallback('missing'));
            $this->assertSame('content block', $tpl->compileBlockNode('known', [], 'content'));
            $this->assertSame('', $tpl->compileBlockNode('missing', [], 'content'));
            $this->assertSame('value="ok"', $tpl->compileValueNode('value', [], ' value="ok"'));
            $this->assertSame('', $tpl->compileValueNode('missing', [], 'value'));

            $tpl->setUnknownBlockHandler(static fn (string $tag, array $attr, string $content): string => $tag . ':' . $content);
            $tpl->setUnknownValueHandler(static fn (string $tag, array $attr, string $content): string => $tag . ':' . $content);
            $this->assertSame('unknown:content', $tpl->compileBlockNode('unknown', [], 'content'));
            $this->assertSame('unknown:value', $tpl->compileValueNode('unknown', [], 'value'));

            try {
                $tpl->addBlock('invalid', false);
                $this->fail('Expected invalid block callback to be rejected');
            } catch (\Exception $exception) {
                $this->assertSame('No valid callback for invalid', $exception->getMessage());
            }

            try {
                $tpl->addValue('invalid', false);
                $this->fail('Expected invalid value callback to be rejected');
            } catch (\Exception $exception) {
                $this->assertSame('No valid callback for invalid', $exception->getMessage());
            }

            try {
                $tpl->setCacheDir($root . '/missing');
                $this->fail('Expected invalid cache directory to be rejected');
            } catch (\Exception $exception) {
                $this->assertSame($root . '/missing is not a valid directory.', $exception->getMessage());
            }

            try {
                $tpl->getFile('missing.tpl');
                $this->fail('Expected missing template file to be rejected');
            } catch (\Exception $exception) {
                $this->assertSame('No template found for missing.tpl', $exception->getMessage());
            }
        } finally {
            \Dotclear\Helper\File\Files::deltree($root);
        }
    }

    public function testCachedTemplateAndSuppressedInclude(): void
    {
        $root      = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'template-cache-unit-' . bin2hex(random_bytes(6));
        $templateDir = $root . DIRECTORY_SEPARATOR . 'templates';
        $cacheDir  = $root . DIRECTORY_SEPARATOR . 'cache';
        mkdir($templateDir, 0o777, true);
        mkdir($cacheDir, 0o777, true);
        file_put_contents($templateDir . DIRECTORY_SEPARATOR . 'main.tpl', 'cached output');
        $displayErrors = ini_get('display_errors');

        try {
            $tpl = new \Dotclear\Helper\Html\Template\Template($cacheDir, '$tpl');
            $tpl->setPath($templateDir);

            $compiled = $tpl->getFile('main.tpl');
            clearstatcache(true, $compiled);
            $this->assertSame($compiled, $tpl->getFile('main.tpl'));

            ini_set('display_errors', '0');
            $this->assertSame('cached output', $tpl->getData('main.tpl'));
        } finally {
            if ($displayErrors !== false) {
                ini_set('display_errors', $displayErrors);
            }
            \Dotclear\Helper\File\Files::deltree($root);
        }
    }
}

class mockTemplates
{
    public static function register(\Dotclear\Helper\Html\Template\Template $tpl): void
    {
        $tpl->addValue('echo', [self::class, 'tplecho']);
        $tpl->addBlock('loop', [self::class, 'tplloop']);
        $tpl->addBlock('entity', [self::class, 'tplentity']);
    }

    /**
     * @param  array<string, mixed>|ArrayObject<string, mixed> $attr
     */
    public static function tplentity(array|ArrayObject $attr, string $content): string
    {
        $ret = '<div';
        foreach ($attr as $k => $v) {
            $ret .= ' ' . $k . ($v ? '="' . $v . '"' : '');
        }
        $ret .= '>' . "\n" . $content . '</div>' . "\n";

        return $ret;
    }

    /**
     * @param  array<string, mixed>|ArrayObject<string, mixed> $attr
     */
    public static function tplecho(array|ArrayObject $attr, string $str): string
    {
        if (!$attr instanceof ArrayObject) {
            $attr = new ArrayObject($attr);
        }

        $ret = '';
        $txt = [];
        foreach ($attr as $k => $v) {
            $txt[] = '"' . $k . '":"' . $v . '"';
        }
        if (!empty($txt)) {
            $ret .= '{' . join(',', $txt) . '}';
        }
        if (!$attr->count()) {
            $ret .= '[' . $str . ']';
        }

        return $ret;
    }

    /**
     * @param  array<string, mixed>|ArrayObject<string, mixed> $attr
     */
    public static function tplloop(array|ArrayObject $attr, string $content): string
    {
        if (!$attr instanceof ArrayObject) {
            $attr = new ArrayObject($attr);
        }

        $ret = '';
        if (isset($attr['times'])) {
            $times = (int) $attr['times'];
            for ($i = 0; $i < $times; $i++) {
                $ret .= $content;
            }
            unset($attr['times']);
        }
        if ($attr->count()) {
            $ret = self::tplecho($attr, '') . $ret;
        }

        return $ret;
    }

    public static function trimHereDoc(string $t): string
    {
        return trim(implode("\n", array_map('trim', explode("\n", $t))));
    }
}
