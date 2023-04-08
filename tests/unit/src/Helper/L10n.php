<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

use atoum;
use Faker\Factory;

class l10n extends atoum
{
    public function testWithEmpty()
    {
        $this
            ->string(__(''))
            ->isEqualTo('');
    }

    public function testWithoutTranslation()
    {
        $faker = Factory::create();
        $text  = $faker->text(50);

        $this
            ->string(__($text))
            ->isEqualTo($text);
    }

    public function testSimpleSingular()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','core']));

        $this
            ->string(__('Dotclear has been upgraded.'))
            ->isEqualTo('Dotclear a été mis à jour.');
    }

    public function testZeroForCountEn()
    {
        \Dotclear\Helper\L10n::init();

        $this
            ->string(__('singular', 'plural', 0))
            ->isEqualTo('plural');
    }

    public function testZeroForCountFr()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','main']));

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 0))
            ->isEqualTo('Catégories supprimées avec succès.');

        $this
            ->string(__('Time: %1 second', 'Time: %1 seconds and Next', 2))
            ->isEqualTo('Temps: %1 secondes');
    }

    public function testZeroForCountFrUsingLang()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','main']));
        \Dotclear\Helper\L10n::lang('fr');

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 0))
            ->isEqualTo('Catégorie supprimée avec succès.');
    }

    public function testPluralWithSingularOnly()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','main']));

        $this
            ->string(__('Dotclear has been upgraded.', 'Dotclear has been upgraded (plural).', 0))
            ->isEqualTo('Dotclear a été mis à jour (pluriel).');
    }

    public function testCodeLang()
    {
        \Dotclear\Helper\L10n::init();

        $this
            ->boolean(\Dotclear\Helper\L10n::isCode('xx'))
            ->isEqualTo(false);

        $this
            ->boolean(\Dotclear\Helper\L10n::isCode('fr'))
            ->isEqualTo(true);
    }

    public function testChangeNonExistingLangShouldUseDefaultOne()
    {
        \Dotclear\Helper\L10n::init('en');

        $this
            ->string(\Dotclear\Helper\L10n::lang('xx'))
            ->isEqualTo('en');
    }

    public function testgetLanguageName()
    {
        \Dotclear\Helper\L10n::init();

        $this
            ->string(\Dotclear\Helper\L10n::getLanguageName('fr'))
            ->isEqualTo('Français');
    }

    public function testgetCode()
    {
        \Dotclear\Helper\L10n::init();

        $this
            ->string(\Dotclear\Helper\L10n::getCode('Français'))
            ->isEqualTo('fr');

        $this
            ->string(\Dotclear\Helper\L10n::getCode(\Dotclear\Helper\L10n::getLanguageName('es')))
            ->isEqualTo('es');
    }

    public function testPhpFormatSingular()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        $faker = Factory::create();
        $text  = $faker->text(20);

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','php-format']));

        $this
            ->string(sprintf(__('The e-mail was sent successfully to %s.'), $text))
            ->isEqualTo(sprintf('Message envoyé avec succès à %s.', $text));
    }

    public function testPluralWithoutTranslation()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'dummy']));

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 1))
            ->isEqualTo('The category has been successfully removed.');

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 2))
            ->isEqualTo('The categories have been successfully removed.');
    }

    public function testPluralWithEmptyTranslation()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'empty']));

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 1))
            ->isEqualTo('The category has been successfully removed.');

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 2))
            ->isEqualTo('The categories have been successfully removed.');
    }

    public function testPluralForLanguageWithoutPluralForms()
    {
        \Dotclear\Helper\L10n::init();

        $this
            ->integer(\Dotclear\Helper\L10n::getLanguagePluralsNumber('aa'))
            ->isEqualTo(\Dotclear\Helper\L10n::getLanguagePluralsNumber('en'));

        $this
            ->string(\Dotclear\Helper\L10n::getLanguagePluralExpression('aa'))
            ->isEqualTo(\Dotclear\Helper\L10n::getLanguagePluralExpression('en'));
    }

    public function testSimplePlural()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','main']));

        /*
        msgid "The category has been successfully removed."
        msgid_plural "The categories have been successfully removed."
        msgstr[0] "Catégorie supprimée avec succès."
        msgstr[1] "Catégories supprimées avec succès."
         */

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 1))
            ->isEqualTo('Catégorie supprimée avec succès.');

        $this
            ->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 2))
            ->isEqualTo('Catégories supprimées avec succès.');
    }

    public function testNotExistingPhpAndPoFiles()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'dummy']));

        $this
            ->string(__('Dotclear has been upgraded.'))
            ->isEqualTo('Dotclear has been upgraded.');
    }

    public function testNotExistingPoFile()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();
        \Dotclear\Helper\L10n::set(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','nopo']));

        $this
            ->string(__('Dotclear has been upgraded.'))
            ->isEqualTo('Dotclear a été mis à jour.');
    }

    public function testGetFilePath()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();

        $this
            ->dump($l10n_dir)
            ->string(\Dotclear\Helper\L10n::getFilePath($l10n_dir, 'main.po', 'fr'))
            ->isEqualTo(implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr','main.po']));

        $this
            ->boolean(\Dotclear\Helper\L10n::getFilePath($l10n_dir, 'dummy.po', 'fr'))
            ->isEqualTo(false);
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

        $this
            ->string(__($en_str))
            ->isEqualTo('Pas vraiment une très longue phrase');

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

        $this
            ->string(__($en_str))
            ->isEqualTo($fr_str);

        if (file_exists($tmp_file)) {
            unlink($tmp_file);
        }
    }

    public function testSimpleStringInPhpFile()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();

        $file = implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr', 'simple']);
        if (file_exists("$file.lang.php")) {
            unlink("$file.lang.php");
        }
        \Dotclear\Helper\L10n::generatePhpFileFromPo($file);
        \Dotclear\Helper\L10n::set($file);

        $this
            ->array($GLOBALS['__l10n'])
            ->isIdenticalTo(['Dotclear has been upgraded.' => 'Dotclear a été mis à jour.']);
    }

    public function testPluralStringsInPhpFile()
    {
        $l10n_dir = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'L10n']));

        \Dotclear\Helper\L10n::init();

        $file = implode(DIRECTORY_SEPARATOR, [$l10n_dir, 'fr', 'plurals']);
        if (file_exists("$file.lang.php")) {
            unlink("$file.lang.php");
        }
        \Dotclear\Helper\L10n::generatePhpFileFromPo($file);
        \Dotclear\Helper\L10n::set($file);

        $this
            ->array($GLOBALS['__l10n'])
            ->isIdenticalTo(['The category has been successfully removed.' => ['Catégorie supprimée avec succès.', 'Catégories supprimées avec succès.']]);
    }

    public function testParsePluralExpression()
    {
        $this
            ->array(\Dotclear\Helper\L10n::parsePluralExpression('nplurals=2; plural=(n > 1)'))
            ->hasSize(2)
            ->containsValues([2, '(n > 1)']);

        $this
            ->array(\Dotclear\Helper\L10n::parsePluralExpression('nplurals=6; plural=(n == 0 ? 0 : n == 1 ? 1 : n == 2 ? 2 : n % 100 >= 3 && n % 100 <= 10 ? 3 : n % 100 >= 11 ? 4 : 5)'))
            ->hasSize(2)
            ->containsValues([6, '(n == 0  ? ( 0 ) : ( n == 1  ? ( 1 ) : ( n == 2  ? ( 2 ) : ( n % 100 >= 3 && n % 100 <= 10  ? ( 3 ) : ( n % 100 >= 11  ? ( 4 ) : ( 5))))))']);
    }

    public function testGetISOcodes()
    {
        $this
            ->array(\Dotclear\Helper\L10n::getISOcodes())
            ->string['fr']->isEqualTo('Français');

        $this
            ->array(\Dotclear\Helper\L10n::getISOcodes(true))
            ->string['Français']->isEqualTo('fr');

        $this
            ->array(\Dotclear\Helper\L10n::getISOcodes(false, true))
            ->string['fr']->isEqualTo('fr - Français');

        $this
            ->array(\Dotclear\Helper\L10n::getISOcodes(true, true))
            ->string['fr - Français']->isEqualTo('fr');
    }

    public function testGetTextDirection()
    {
        $this
            ->string(\Dotclear\Helper\L10n::getLanguageTextDirection('fr'))
            ->isEqualTo('ltr');

        $this
            ->string(\Dotclear\Helper\L10n::getLanguageTextDirection('ar'))
            ->isEqualTo('rtl');
    }

    public function testGetLanguagesDefinitions()
    {
        $getLangDefs = new \ReflectionMethod('\Dotclear\Helper\L10n', 'getLanguagesDefinitions');
        $getLangDefs->setAccessible(true);

        $this
            ->array($getLangDefs->invokeArgs(null, [0]))
            ->isNotEmpty();

        $this
            ->array($getLangDefs->invokeArgs(null, [13]))
            ->isEmpty();

        $this
            ->array($getLangDefs->invokeArgs(null, [0]))
            ->string['fr']->isEqualTo('fr');

        $this
            ->array($getLangDefs->invokeArgs(null, [1]))
            ->string['fr']->isEqualTo('fre');

        $this
            ->array($getLangDefs->invokeArgs(null, [2]))
            ->string['fr']->isEqualTo('French');

        $this
            ->array($getLangDefs->invokeArgs(null, [3]))
            ->string['fr']->isEqualTo('Français');

        $this
            ->array($getLangDefs->invokeArgs(null, [4]))
            ->string['fr']->isEqualTo('ltr');

        $this
            ->array($getLangDefs->invokeArgs(null, [5]))
            ->integer['fr']->isEqualTo(2);

        $this
            ->array($getLangDefs->invokeArgs(null, [6]))
            ->string['fr']->isEqualTo('n > 1');
    }

    protected function tempPoFile($content)
    {
        $output = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.po';

        file_put_contents($output, $content);

        return $output;
    }
}
