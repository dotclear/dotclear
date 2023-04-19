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

/**
 * @tags Date
 */
class Date extends atoum
{
    /**
     * Normal way. The result must be as the PHP function.
     */
    public function testStrNormal()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::str('%d%m%Y'))
            // Avoid deprecated notice until PHP 9 should be supported or a correct strftime() replacement
            ->isEqualTo(@strftime('%d%m%Y'));
    }

    /**
     * Timestamp  is set to 1 which is 1 second after Janurary, 1th 1970
     */
    public function testStrTimestamp()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::str('%d%m%Y', 1))
            ->isEqualTo('01011970');
    }

    /**
     * Difference between two time zones. Europe/Paris is GMT+1 and Indian/Reunion is
     * GMT+4. The difference might be 3.
     * The timestamp is forced due to the summer or winter time.
     */
    public function testStrWithTimestampAndTimezone()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->integer((int) \Dotclear\Helper\Date::str('%H', 1, 'Indian/Reunion') - (int) \Dotclear\Helper\Date::str('%H', 1, 'Europe/Paris'))
            ->isEqualTo(3);
    }

    /**
     * dt2str is a wrapper for dt::str but convert the human readable time
     * into a computer understandable time
     */
    public function testDt2Str()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::dt2str('%Y', '1970-01-01'))
            ->isEqualTo(\Dotclear\Helper\Date::str('%Y', 1));
    }

    /**
     *
     */
    public function testSetGetTZ()
    {
        \Dotclear\Helper\Date::setTZ('Indian/Reunion');
        $this->string(\Dotclear\Helper\Date::getTZ())->isEqualTo('Indian/Reunion');
    }

    /**
     * dtstr with anything but the time. We don't test strtodate,
     * we test dt1str will always have the same behaviour.
     */
    public function testDt2DummyStr()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::dt2str('%Y', 'Everything but a time'))
            ->isEqualTo(\Dotclear\Helper\Date::str('%Y'));
    }

    /*
     * Convert timestamp to ISO8601 date
     */
    public function testISO8601()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::iso8601(1, 'UTC'))
            ->isEqualTo('1970-01-01T00:00:01+00:00');
    }

    /*
     * Convert timestamp to ISO8601 date but not UTC.
     */
    public function testISO8601WithAnotherTimezone()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::iso8601(1, 'Indian/Reunion'))
            ->isEqualTo('1970-01-01T00:00:01+04:00');
    }

    public function testRfc822()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::rfc822(1, 'Indian/Reunion'))
            ->isEqualTo('Thu, 01 Jan 1970 00:00:01 +0400');
    }

    public function testGetTimeOffset()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->integer(\Dotclear\Helper\Date::getTimeOffset('Indian/Reunion'))
            ->isEqualTo(4 * 3600);
    }

    public function testToUTC()
    {
        \Dotclear\Helper\Date::setTZ('Indian/Reunion'); // UTC + 4
        $this->integer(\Dotclear\Helper\Date::toUTC(4 * 3600))
            ->isEqualTo(0);
    }

    /*
     * AddTimezone implies getZones but I prefer testing both of them separatly
     */
    public function testAddTimezone()
    {
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->integer(\Dotclear\Helper\Date::addTimeZone('Indian/Reunion', 0))
            ->isEqualTo(4 * 3600);

        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->integer(\Dotclear\Helper\Date::addTimeZone('Indian/Reunion') - time() - \Dotclear\Helper\Date::getTimeOffset('Indian/Reunion'))
            ->isEqualTo(0);
    }

    /*
     * There's many different time zone. Basicly, dt::getZone call a PHP function.
     * Ensure that the key is the value array('time/zone' => 'time/zone')
     */
    public function testGetZones()
    {
        $tzs = \Dotclear\Helper\Date::getZones();

        $this
            ->array($tzs)
            ->isNotNull();

        $this
            ->string($tzs['Europe/Paris'])
            ->isEqualTo('Europe/Paris');

        // Test another call
        $tzs = \Dotclear\Helper\Date::getZones();

        $this
            ->array($tzs)
            ->isNotNull();

        $this
            ->string($tzs['Indian/Reunion'])
            ->isEqualTo('Indian/Reunion');
    }

    public function testGetZonesFlip()
    {
        $tzs = \Dotclear\Helper\Date::getZones(true, false);

        $this
            ->array($tzs)
            ->isNotNull();

        $this
            ->string($tzs['Europe/Paris'])
            ->isEqualTo('Europe/Paris');
    }

    public function testGetZonesGroup()
    {
        $tzs = \Dotclear\Helper\Date::getZones(true, true);

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
        \Dotclear\Helper\Date::setTZ('UTC');
        $this
            ->string(\Dotclear\Helper\Date::str('%a %A %b %B', 1))
            ->isEqualTo('_Thu Thursday _Jan January');
    }
}
