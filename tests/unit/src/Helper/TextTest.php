<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use Faker\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testIsEmail()
    {
        $faker = Factory::create();
        $text  = $faker->email();

        $this->assertTrue(
            \Dotclear\Helper\Text::isEmail($text)
        );
        $this->assertFalse(
            \Dotclear\Helper\Text::isEmail('@dotclear.org')
        );
    }

    public static function dataProviderIsEmailAll()
    {
        require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'fixtures', 'src', 'Helper', 'Text.php']);

        return array_values($emailTest);
    }

    #[DataProvider('dataProviderIsEmailAll')]
    public function testIsEmailAll($payload, $expected)
    {
        $this->assertEquals(
            $expected,
            \Dotclear\Helper\Text::isEmail($payload)
        );
    }

    public function testDeaccent()
    {
        $this->assertEquals(
            'AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss ee',
            \Dotclear\Helper\Text::deaccent('ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß éè')
        );
    }

    public function testStr2URL()
    {
        $this->assertEquals(
            'https://domaincom/AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss/eehtml',
            \Dotclear\Helper\Text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html')
        );
        $this->assertEquals(
            'https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml',
            \Dotclear\Helper\Text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html', false)
        );
    }

    public function testTidyURL()
    {
        // Keep /, no spaces
        $this->assertEquals(
            'Étrange-et-curieux/À-vous-!',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !')
        );

        // Keep /, keep spaces
        $this->assertEquals(
            'Étrange et curieux/À vous !',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', true, true)
        );

        // No /, keep spaces
        $this->assertEquals(
            'Étrange et curieux-À vous !',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', false, true)
        );

        // No /, no spaces
        $this->assertEquals(
            'Étrange-et-curieux-À-vous-!',
            \Dotclear\Helper\Text::tidyURL('Étrange et curieux/=À vous !', false, false)
        );
    }

    public function testcutString()
    {
        $faker = Factory::create();
        $text  = $faker->realText(400);

        $this->assertLessThanOrEqual(
            200,
            mb_strlen(\Dotclear\Helper\Text::cutString($text, 200))
        );
        $this->assertEquals(
            'https:-domaincom-AAA',
            \Dotclear\Helper\Text::cutString('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml', 20)
        );
        $this->assertEquals(
            'https domaincom',
            \Dotclear\Helper\Text::cutString('https domaincom AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss eehtml', 20)
        );
    }

    public function testSplitWords()
    {
        $test = \Dotclear\Helper\Text::splitWords('Étrange et curieux/=À vous !');
        $this->assertCount(
            3,
            $test
        );
        $this->assertEquals(
            'étrange',
            $test[0]
        );
        $this->assertEquals(
            'curieux',
            $test[1]
        );
        $this->assertEquals(
            'vous',
            $test[2]
        );

        $this->assertEmpty(
            \Dotclear\Helper\Text::splitWords(' ')
        );
    }

    public function testDetectEncoding()
    {
        $this->assertEquals(
            'utf-8',
            \Dotclear\Helper\Text::detectEncoding('Étrange et curieux/=À vous !')
        );

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this->assertEquals(
            'iso-8859-1',
            \Dotclear\Helper\Text::detectEncoding($test)
        );
    }

    public function testToUTF8()
    {
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::toUTF8('Étrange et curieux/=À vous !')
        );

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::toUTF8($test)
        );
    }

    public function testUtf8badFind()
    {
        $this->assertFalse(
            \Dotclear\Helper\Text::utf8badFind('Étrange et curieux/=À vous !')
        );
        $this->assertEquals(
            12,
            \Dotclear\Helper\Text::utf8badFind('Étrange et ' . chr(0xE0A0BF) . ' curieux/=À vous' . chr(0xC280) . ' !')
        );
    }

    public function testCleanUTF8()
    {
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::cleanUTF8('Étrange et curieux/=À vous !')
        );
        $this->assertEquals(
            'Étrange et ? curieux/=À vous? !',
            \Dotclear\Helper\Text::cleanUTF8('Étrange et ' . chr(0xE0A0BF) . ' curieux/=À vous' . chr(0xC280) . ' !')
        );
    }

    public function testCleanStr()
    {
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::cleanStr('Étrange et curieux/=À vous !')
        );

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this->assertEquals(
            'Étrange et curieux/=À vous !',
            \Dotclear\Helper\Text::cleanStr($test)
        );
    }

    public function testRemoveDiacritics()
    {
        $this->assertEquals(
            'AAAAEAOAUAVAYBCDDZDzEFGHIJKLLJLjMNNJNjOOEOIOOe',
            \Dotclear\Helper\Text::removeDiacritics('&#9398;&#42802;&#508;&#42804;&#42806;&#42810;&#42812;&#7682;&#262;&#393;&#497;&#453;&#518;&#65318;&#500;&#11381;&#407;&#65322;&#7728;&#319;&#455;&#456;&#7742;&#9411;&#458;&#459;&#492;&#338;&#418;&#42830;&#65349;')
        );
    }
}
