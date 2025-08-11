<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use Faker\Factory;
use PHPUnit\Framework\TestCase;

class L10nTest extends TestCase
{
    private string $l10n_dir;

    protected function setUp(): void
    {
        $this->l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));
    }

    public function testWithEmpty()
    {
        $this->assertEquals(
            '',
            __('')
        );
    }

    public function testWithoutTranslation()
    {
        $faker = Factory::create();
        $text  = $faker->text(50);

        $this->assertEquals(
            $text,
            __($text)
        );
    }

    public function testSimpleSingular()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','core']));

        $this->assertEquals(
            'Dotclear a été mis à jour.',
            __('Dotclear has been upgraded.')
        );
    }

    public function testZeroForCountEn()
    {
        \Dotclear\Helper\L10n::init();

        $this->assertEquals(
            'plural',
            __('singular', 'plural', 0)
        );
    }

    public function testZeroForCountFr()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','main']));

        $this->assertEquals(
            'Catégories supprimées avec succès.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 0)
        );
        $this->assertEquals(
            'Temps: %1 secondes',
            __('Time: %1 second', 'Time: %1 seconds and Next', 2)
        );
    }

    public function testZeroForCountFrUsingLang()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','main']));
        \Dotclear\Helper\L10n::lang('fr');

        $this->assertEquals(
            'Catégorie supprimée avec succès.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 0)
        );
    }

    public function testPluralWithSingularOnly()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','main']));

        $this->assertEquals(
            'Dotclear a été mis à jour (pluriel).',
            __('Dotclear has been upgraded.', 'Dotclear has been upgraded (plural).', 0)
        );
    }

    public function testCodeLang()
    {
        \Dotclear\Helper\L10n::init();

        $this->assertFalse(
            \Dotclear\Helper\L10n::isCode('xx')
        );
        $this->assertTrue(
            \Dotclear\Helper\L10n::isCode('fr')
        );
        $this->assertTrue(
            \Dotclear\Helper\L10n::isCode('ja')
        );
    }

    public function testChangeNonExistingLangShouldUseDefaultOne()
    {
        \Dotclear\Helper\L10n::init('en');

        $this->assertEquals(
            'en',
            \Dotclear\Helper\L10n::lang('xx')
        );
    }

    public function testGetLanguageName()
    {
        \Dotclear\Helper\L10n::init();

        $this->assertEquals(
            'Français',
            \Dotclear\Helper\L10n::getLanguageName('fr')
        );
    }

    public function testGetCode()
    {
        \Dotclear\Helper\L10n::init();

        $this->assertEquals(
            'fr',
            \Dotclear\Helper\L10n::getCode('Français')
        );
        $this->assertEquals(
            'es',
            \Dotclear\Helper\L10n::getCode(\Dotclear\Helper\L10n::getLanguageName('es'))
        );
        $this->assertEquals(
            'ja',
            \Dotclear\Helper\L10n::getCode(\Dotclear\Helper\L10n::getLanguageName('ja'))
        );
    }

    public function testPhpFormatSingular()
    {
        $faker = Factory::create();
        $text  = $faker->text(20);

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','php-format']));

        $this->assertEquals(
            sprintf('Message envoyé avec succès à %s.', $text),
            sprintf(__('The e-mail was sent successfully to %s.'), $text)
        );
    }

    public function testPluralWithoutTranslation()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'dummy']));

        $this->assertEquals(
            'The category has been successfully removed.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 1)
        );
        $this->assertEquals(
            'The categories have been successfully removed.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 2)
        );
    }

    public function testPluralWithEmptyTranslation()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'empty']));

        $this->assertEquals(
            'The category has been successfully removed.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 1)
        );
        $this->assertEquals(
            'The categories have been successfully removed.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 2)
        );
    }

    public function testPluralForLanguageWithoutPluralForms()
    {
        \Dotclear\Helper\L10n::init();

        $this->assertEquals(
            \Dotclear\Helper\L10n::getLanguagePluralsNumber('en'),
            \Dotclear\Helper\L10n::getLanguagePluralsNumber('aa')
        );
        $this->assertEquals(
            \Dotclear\Helper\L10n::getLanguagePluralExpression('en'),
            \Dotclear\Helper\L10n::getLanguagePluralExpression('aa')
        );
    }

    public function testSimplePlural()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','main']));

        /*
        msgid "The category has been successfully removed."
        msgid_plural "The categories have been successfully removed."
        msgstr[0] "Catégorie supprimée avec succès."
        msgstr[1] "Catégories supprimées avec succès."
         */

        $this->assertEquals(
            'Catégorie supprimée avec succès.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 1)
        );
        $this->assertEquals(
            'Catégories supprimées avec succès.',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 2)
        );
    }

    public function testSimplePluralNone()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'ja','core']));
        \Dotclear\Helper\L10n::lang('ja');

        /*
        msgid "The category has been successfully removed."
        msgstr "カテゴリを削除しました。"
        */

        $this->assertEquals(
            'カテゴリを削除しました。',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 1)
        );
        $this->assertEquals(
            'カテゴリを削除しました。',
            __('The category has been successfully removed.', 'The categories have been successfully removed.', 2)
        );
    }

    public function testNotExistingPhpAndPoFiles()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'dummy']));

        $this->assertEquals(
            'Dotclear has been upgraded.',
            __('Dotclear has been upgraded.')
        );
    }

    public function testNotExistingPoFile()
    {
        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','nopo']));

        $this->assertEquals(
            'Dotclear a été mis à jour.',
            __('Dotclear has been upgraded.')
        );
    }

    public function testGetFilePath()
    {
        \Dotclear\Helper\L10n::init();

        $this->assertEquals(
            implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr','main.po']),
            \Dotclear\Helper\L10n::getFilePath($this->l10n_dir, 'main.po', 'fr')
        );
        $this->assertFalse(
            \Dotclear\Helper\L10n::getFilePath($this->l10n_dir, 'dummy.po', 'fr')
        );
    }

    public function testMultiLineIdString()
    {
        \Dotclear\Helper\L10n::init();

        $en_str  = 'Not a real long sentence';
        $content = 'msgid ""' . "\n" . '"';
        $content .= implode('"' . "\n" . '" ', explode(' ', $en_str));
        $content .= '"' . "\n";
        $content .= 'msgstr "Pas vraiment une très longue phrase"' . "\n";

        $tmp_file = $this->tempPoFile($content);
        \Dotclear\Helper\L10n::set(str_replace('.po', '', $tmp_file));

        $this->assertEquals(
            'Pas vraiment une très longue phrase',
            __($en_str)
        );

        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }

    public function testMultiLineValueString()
    {
        \Dotclear\Helper\L10n::init();

        $en_str  = 'Not a real long sentence';
        $fr_str  = 'Pas vraiment une très longue phrase';
        $content = 'msgid "' . $en_str . '"' . "\n";
        $content .= 'msgstr ""' . "\n" . '"';
        $content .= implode('"' . "\n" . '" ', explode(' ', $fr_str));
        $content .= '"' . "\n";

        $tmp_file = $this->tempPoFile($content);
        \Dotclear\Helper\L10n::set(str_replace('.po', '', $tmp_file));

        $this->assertEquals(
            $fr_str,
            __($en_str)
        );

        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }

    public function testSimpleStringInPhpFile()
    {
        \Dotclear\Helper\L10n::init();

        $file = implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr', 'simple']);
        if (file_exists("$file.lang.php")) {
            unlink("$file.lang.php");
        }
        \Dotclear\Helper\L10n::generatePhpFileFromPo($file);
        \Dotclear\Helper\L10n::set($file);

        $this->assertEquals(
            ['Dotclear has been upgraded.' => 'Dotclear a été mis à jour.'],
            $GLOBALS['__l10n']
        );
    }

    public function testPluralStringsInPhpFile()
    {
        \Dotclear\Helper\L10n::init();

        $file = implode(DIRECTORY_SEPARATOR, [$this->l10n_dir, 'fr', 'plurals']);
        if (file_exists("$file.lang.php")) {
            unlink("$file.lang.php");
        }
        \Dotclear\Helper\L10n::generatePhpFileFromPo($file);
        \Dotclear\Helper\L10n::set($file);

        $this->assertEquals(
            ['The category has been successfully removed.' => ['Catégorie supprimée avec succès.', 'Catégories supprimées avec succès.']],
            $GLOBALS['__l10n']
        );
    }

    public function testParsePluralExpression()
    {
        $this->assertCount(
            2,
            \Dotclear\Helper\L10n::parsePluralExpression('nplurals=2; plural=(n > 1)')
        );
        $this->assertContains(
            '(n > 1)',
            \Dotclear\Helper\L10n::parsePluralExpression('nplurals=2; plural=(n > 1)')
        );
        $this->assertCount(
            2,
            \Dotclear\Helper\L10n::parsePluralExpression('nplurals=6; plural=(n == 0 ? 0 : n == 1 ? 1 : n == 2 ? 2 : n % 100 >= 3 && n % 100 <= 10 ? 3 : n % 100 >= 11 ? 4 : 5)')
        );
        $this->assertContains(
            '(n == 0  ? ( 0 ) : ( n == 1  ? ( 1 ) : ( n == 2  ? ( 2 ) : ( n % 100 >= 3 && n % 100 <= 10  ? ( 3 ) : ( n % 100 >= 11  ? ( 4 ) : ( 5))))))',
            \Dotclear\Helper\L10n::parsePluralExpression('nplurals=6; plural=(n == 0 ? 0 : n == 1 ? 1 : n == 2 ? 2 : n % 100 >= 3 && n % 100 <= 10 ? 3 : n % 100 >= 11 ? 4 : 5)')
        );
    }

    public function testGetISOcodes()
    {
        $this->assertContains(
            'Français',
            \Dotclear\Helper\L10n::getISOcodes()
        );
        $this->assertContains(
            'fr',
            \Dotclear\Helper\L10n::getISOcodes(true)
        );
        $this->assertContains(
            'fr - Français',
            \Dotclear\Helper\L10n::getISOcodes(false, true)
        );
        $this->assertContains(
            'fr',
            \Dotclear\Helper\L10n::getISOcodes(true, true)
        );
    }

    public function testGetTextDirection()
    {
        $this->assertEquals(
            'ltr',
            \Dotclear\Helper\L10n::getLanguageTextDirection('fr')
        );
        $this->assertEquals(
            'rtl',
            \Dotclear\Helper\L10n::getLanguageTextDirection('ar')
        );
    }

    public function testGetLanguagesDefinitions()
    {
        $getLangDefs = new \ReflectionMethod('\Dotclear\Helper\L10n', 'getLanguagesDefinitions');
        $getLangDefs->setAccessible(true);

        $this->assertNotEmpty(
            $getLangDefs->invokeArgs(null, [0])
        );
        $this->assertEmpty(
            $getLangDefs->invokeArgs(null, [13])
        );
        $this->assertContains(
            'fr',
            $getLangDefs->invokeArgs(null, [0])
        );
        $this->assertContains(
            'fre',
            $getLangDefs->invokeArgs(null, [1])
        );
        $this->assertContains(
            'French',
            $getLangDefs->invokeArgs(null, [2])
        );
        $this->assertContains(
            'Français',
            $getLangDefs->invokeArgs(null, [3])
        );
        $this->assertContains(
            'ltr',
            $getLangDefs->invokeArgs(null, [4])
        );
        $this->assertContains(
            2,
            $getLangDefs->invokeArgs(null, [5])
        );
        $this->assertContains(
            'n > 1',
            $getLangDefs->invokeArgs(null, [6])
        );
    }

    protected function tempPoFile($content)
    {
        $output = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.po';

        file_put_contents($output, $content);

        return $output;
    }
}
