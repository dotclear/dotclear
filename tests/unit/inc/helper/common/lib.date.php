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

require_once __DIR__ . '/../../../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.l10n.php';
require_once CLEARBRICKS_PATH . '/common/lib.date.php';

use atoum;

/**
 * Test clearbrick dt (date) class.
 */
class dt extends atoum
{
    /**
     * Normal way. The result must be as the PHP function.
     */
    public function testStrNormal()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::str('%d%m%Y'))
            // Avoid deprecated notice until PHP 9 should be supported or a correct strftime() replacement
            ->isEqualTo(@strftime('%d%m%Y'));
    }

    /**
     * Timestamp  is set to 1 which is 1 second after Janurary, 1th 1970
     */
    public function testStrTimestamp()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::str('%d%m%Y', 1))
            ->isEqualTo('01011970');
    }

    /**
     * Difference between two time zones. Europe/Paris is GMT+1 and Indian/Reunion is
     * GMT+4. The difference might be 3.
     * The timestamp is forced due to the summer or winter time.
     */
    public function testStrWithTimestampAndTimezone()
    {
        \dt::setTZ('UTC');
        $this
            ->integer((int) \dt::str('%H', 1, 'Indian/Reunion') - (int) \dt::str('%H', 1, 'Europe/Paris'))
            ->isEqualTo(3);
    }

    /**
     * dt2str is a wrapper for dt::str but convert the human readable time
     * into a computer understandable time
     */
    public function testDt2Str()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::dt2str('%Y', '1970-01-01'))
            ->isEqualTo(\dt::str('%Y', 1));
    }

    /**
     *
     */
    public function testSetGetTZ()
    {
        \dt::setTZ('Indian/Reunion');
        $this->string(\dt::getTZ())->isEqualTo('Indian/Reunion');
    }

    /**
     * dtstr with anything but the time. We don't test strtodate,
     * we test dt1str will always have the same behaviour.
     */
    public function testDt2DummyStr()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::dt2str('%Y', 'Everything but a time'))
            ->isEqualTo(\dt::str('%Y'));
    }

    /*
     * Convert timestamp to ISO8601 date
     */
    public function testISO8601()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::iso8601(1, 'UTC'))
            ->isEqualTo('1970-01-01T00:00:01+00:00');
    }

    /*
     * Convert timestamp to ISO8601 date but not UTC.
     */
    public function testISO8601WithAnotherTimezone()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::iso8601(1, 'Indian/Reunion'))
            ->isEqualTo('1970-01-01T00:00:01+04:00');
    }

    public function testRfc822()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::rfc822(1, 'Indian/Reunion'))
            ->isEqualTo('Thu, 01 Jan 1970 00:00:01 +0400');
    }

    public function testGetTimeOffset()
    {
        \dt::setTZ('UTC');
        $this
            ->integer(\dt::getTimeOffset('Indian/Reunion'))
            ->isEqualTo(4 * 3600);
    }

    public function testToUTC()
    {
        \dt::setTZ('Indian/Reunion'); // UTC + 4
        $this->integer(\dt::toUTC(4 * 3600))
            ->isEqualTo(0);
    }

    /*
     * AddTimezone implies getZones but I prefer testing both of them separatly
     */
    public function testAddTimezone()
    {
        \dt::setTZ('UTC');
        $this
            ->integer(\dt::addTimeZone('Indian/Reunion', 0))
            ->isEqualTo(4 * 3600);

        \dt::setTZ('UTC');
        $this
            ->integer(\dt::addTimeZone('Indian/Reunion') - time() - \dt::getTimeOffset('Indian/Reunion'))
            ->isEqualTo(0);
    }

    /*
     * There's many different time zone. Basicly, dt::getZone call a PHP function.
     * Ensure that the key is the value array('time/zone' => 'time/zone')
     */
    public function testGetZones()
    {
        $tzs = \dt::getZones();

        $this
            ->array($tzs)
            ->isNotNull();

        $this
            ->string($tzs['Europe/Paris'])
            ->isEqualTo('Europe/Paris');

        // Test another call
        $tzs = \dt::getZones();

        $this
            ->array($tzs)
            ->isNotNull();

        $this
            ->string($tzs['Indian/Reunion'])
            ->isEqualTo('Indian/Reunion');
    }

    public function testGetZonesFlip()
    {
        $tzs = \dt::getZones(true, false);

        $this
            ->array($tzs)
            ->isNotNull();

        $this
            ->string($tzs['Europe/Paris'])
            ->isEqualTo('Europe/Paris');
    }

    public function testGetZonesGroup()
    {
        $tzs = \dt::getZones(true, true);

        $this
            ->array($tzs)
            ->isNotNull();

        $this
            ->array($tzs['Europe'])
            ->isNotNull()
            ->string($tzs['Europe']['Europe/Paris'])
            ->isEqualTo('Europe/Paris');
    }

    public function testStr()
    {
        \dt::setTZ('UTC');
        $this
            ->string(\dt::str('%a %A %b %B', 1))
            ->isEqualTo('_Thu Thursday _Jan January');
    }
}
