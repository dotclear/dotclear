<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.text.php';
require_once CLEARBRICKS_PATH . '/common/lib.html.php';

use atoum;
use Faker;

/**
 * Test the form class
 */
class text extends atoum
{
    public function testIsEmail()
    {
        $faker = Faker\Factory::create();
        $text  = $faker->email();

        $this
            ->boolean(\text::isEmail($text))
            ->isTrue();

        $this
            ->boolean(\text::isEmail('@dotclear.org'))
            ->isFalse();
    }

    /**
     * @dataProvider testIsEmailDataProvider
     */
    protected function testIsEmailAllDataProvider()
    {
        require_once __DIR__ . '/../fixtures/data/lib.text.php';

        return array_values($emailTest);
    }

    public function testIsEmailAll($payload, $expected)
    {
        $this
            ->boolean(\text::isEmail($payload))
            ->isEqualTo($expected);
    }

    public function testDeaccent()
    {
        $this
            ->string(\text::deaccent('ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß éè'))
            ->isEqualTo('AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss ee');
    }

    public function teststr2URL()
    {
        $this
            ->string(\text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html'))
            ->isEqualTo('https://domaincom/AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss/eehtml');

        $this
            ->string(\text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html', false))
            ->isEqualTo('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml');
    }

    public function testTidyURL()
    {
        // Keep /, no spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange-et-curieux/À-vous-!');

        // Keep /, keep spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !', true, true))
            ->isEqualTo('Étrange et curieux/À vous !');

        // No /, keep spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !', false, true))
            ->isEqualTo('Étrange et curieux-À vous !');

        // No /, no spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !', false, false))
            ->isEqualTo('Étrange-et-curieux-À-vous-!');
    }

    public function testcutString()
    {
        $faker = Faker\Factory::create();
        $text  = $faker->realText(400);
        $this
            ->string(\text::cutString($text, 200))
            ->hasLengthLessThan(201);

        $this
            ->string(\text::cutString('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml', 20))
            ->isIdenticalTo('https:-domaincom-AAA');

        $this
            ->string(\text::cutString('https domaincom AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss eehtml', 20))
            ->isIdenticalTo('https domaincom');
    }

    public function testSplitWords()
    {
        $this
            ->array(\text::splitWords('Étrange et curieux/=À vous !'))
            ->hasSize(3)
            ->string[0]->isEqualTo('étrange')
            ->string[1]->isEqualTo('curieux')
            ->string[2]->isEqualTo('vous');

        $this
            ->array(\text::splitWords(' '))
            ->hasSize(0);
    }

    public function testDetectEncoding()
    {
        $this
            ->string(\text::detectEncoding('Étrange et curieux/=À vous !'))
            ->isEqualTo('utf-8');

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this
            ->string(\text::detectEncoding($test))
            ->isEqualTo('iso-8859-1');
    }

    public function testToUTF8()
    {
        $this
            ->string(\text::toUTF8('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange et curieux/=À vous !');

        $test = mb_convert_encoding('Étrange et curieux/=À vous !', 'ISO-8859-1');
        $this
            ->string(\text::toUTF8($test))
            ->isEqualTo('Étrange et curieux/=À vous !');
    }

    public function testUtf8badFind()
    {
        $this
            ->variable(\text::utf8badFind('Étrange et curieux/=À vous !'))
            ->isEqualTo(false);

        $this
            ->variable(\text::utf8badFind('Étrange et ' . chr(0xE0A0BF) . ' curieux/=À vous' . chr(0xC280) . ' !'))
            ->isEqualTo(12);
    }

    public function testCleanUTF8()
    {
        $this
            ->string(\text::cleanUTF8('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange et curieux/=À vous !');

        $this
            ->string(\text::cleanUTF8('Étrange et ' . chr(0xE0A0BF) . ' curieux/=À vous' . chr(0xC280) . ' !'))
            ->isEqualTo('Étrange et ? curieux/=À vous? !');
    }

    public function testRemoveBOM()
    {
        $this
            ->string(\text::removeBOM('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange et curieux/=À vous !');

        $this
            ->string(\text::removeBOM('﻿' . 'Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange et curieux/=À vous !');
    }

    public function testQPEncode()
    {
        $this
            ->string(\text::QPEncode('Étrange et curieux/=À vous !'))
            ->isEqualTo('=C3=89trange et curieux/=3D=C3=80 vous !' . "\r\n");
    }
}
