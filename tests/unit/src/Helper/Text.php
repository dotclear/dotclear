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

/**
 * @tags Text
 */
class Text extends atoum
{
    public function testIsEmail()
    {
        $faker = Factory::create();
        $text  = $faker->email();

        $this
            ->boolean(\Dotclear\Helper\Text::isEmail($text))
            ->isTrue()
        ;

        $this
            ->boolean(\Dotclear\Helper\Text::isEmail('@dotclear.org'))
            ->isFalse()
        ;
    }

    /**
     * @dataProvider testIsEmailDataProvider
     */
    protected function testIsEmailAllDataProvider()
    {
        require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'Text.php']);

        return array_values($emailTest);
    }

    public function testIsEmailAll($payload, $expected)
    {
        $this
            ->boolean(\Dotclear\Helper\Text::isEmail($payload))
            ->isEqualTo($expected)
        ;
    }

    public function testDeaccent()
    {
        $this
            ->string(\Dotclear\Helper\Text::deaccent('ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß éè'))
            ->isEqualTo('AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss ee')
        ;
    }

    public function teststr2URL()
    {
        $this
            ->string(\Dotclear\Helper\Text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html'))
            ->isEqualTo('https://domaincom/AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss/eehtml')
        ;

        $this
            ->string(\Dotclear\Helper\Text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html', false))
            ->isEqualTo('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml')
        ;
    }

    public function testTidyURL()
    {
        // Keep /, no spaces
        $this
            ->string(\Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange-et-curieux/À-vous-!')
        ;

        // Keep /, keep spaces
        $this
            ->string(\Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', true, true))
            ->isEqualTo('Étrange et curieux/À vous !')
        ;

        // No /, keep spaces
        $this
            ->string(\Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', false, true))
            ->isEqualTo('Étrange et curieux-À vous !')
        ;

        // No /, no spaces
        $this
            ->string(\Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', false, false))
            ->isEqualTo('Étrange-et-curieux-À-vous-!')
        ;
    }

    public function testcutString()
    {
        $faker = Factory::create();
        $text  = $faker->realText(400);
        $this
            ->string(\Dotclear\Helper\Text::cutString($text, 200))
            ->hasLengthLessThan(201)
        ;

        $this
            ->string(\Dotclear\Helper\Text::cutString('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml', 20))
            ->isIdenticalTo('https:-domaincom-AAA')
        ;

        $this
            ->string(\Dotclear\Helper\Text::cutString('https domaincom AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss eehtml', 20))
            ->isIdenticalTo('https domaincom')
        ;
    }

    public function testSplitWords()
    {
        $this
            ->array(\Dotclear\Helper\Text::splitWords('Étrange et curieux/=À vous !'))
            ->hasSize(3)
            ->string[0]->isEqualTo('étrange')
            ->string[1]->isEqualTo('curieux')
            ->string[2]->isEqualTo('vous')
        ;

        $this
            ->array(\Dotclear\Helper\Text::splitWords(' '))
            ->hasSize(0)
        ;
    }

    public function testDetectEncoding()
    {
        $this
            ->string(\Dotclear\Helper\Text::detectEncoding('Étrange et curieux/=À vous !'))
            ->isEqualTo('utf-8')
        ;

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this
            ->string(\Dotclear\Helper\Text::detectEncoding($test))
            ->isEqualTo('iso-8859-1')
        ;
    }

    public function testToUTF8()
    {
        $this
            ->string(\Dotclear\Helper\Text::toUTF8('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange et curieux/=À vous !')
        ;

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this
            ->string(\Dotclear\Helper\Text::toUTF8($test))
            ->isEqualTo('Étrange et curieux/=À vous !')
        ;
    }

    public function testUtf8badFind()
    {
        $this
            ->variable(\Dotclear\Helper\Text::utf8badFind('Étrange et curieux/=À vous !'))
            ->isEqualTo(false)
        ;

        $this
            ->variable(\Dotclear\Helper\Text::utf8badFind('Étrange et ' . chr(0xE0A0BF) . ' curieux/=À vous' . chr(0xC280) . ' !'))
            ->isEqualTo(12)
        ;
    }

    public function testCleanUTF8()
    {
        $this
            ->string(\Dotclear\Helper\Text::cleanUTF8('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange et curieux/=À vous !')
        ;

        $this
            ->string(\Dotclear\Helper\Text::cleanUTF8('Étrange et ' . chr(0xE0A0BF) . ' curieux/=À vous' . chr(0xC280) . ' !'))
            ->isEqualTo('Étrange et ? curieux/=À vous? !')
        ;
    }

    public function testCleanStr()
    {
        $this
            ->string(\Dotclear\Helper\Text::cleanStr('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange et curieux/=À vous !')
        ;

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this
            ->string(\Dotclear\Helper\Text::cleanStr($test))
            ->isEqualTo('Étrange et curieux/=À vous !')
        ;
    }
}
