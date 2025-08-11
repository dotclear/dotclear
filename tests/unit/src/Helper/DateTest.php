<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Dotclear\Helper\Date::class)]
class HelperDateTest extends TestCase
{
    /**
     * Normal way. The result must be as the PHP function.
     */
    public function testStrNormal()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            // Avoid deprecated notice until PHP 9 should be supported or a correct strftime() replacement
            @strftime('%d%m%Y'),
            \Dotclear\Helper\Date::str('%d%m%Y')
        );
    }

    /**
     * Timestamp  is set to 1 which is 1 second after Janurary, 1th 1970
     */
    public function testStrTimestamp()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            '01011970',
            \Dotclear\Helper\Date::str('%d%m%Y', 1)
        );
    }

    /**
     * Difference between two time zones. Europe/Paris is GMT+1 and Indian/Reunion is
     * GMT+4. The difference might be 3.
     * The timestamp is forced due to the summer or winter time.
     */
    public function testStrWithTimestampAndTimezone()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            3,
            (int) \Dotclear\Helper\Date::str('%H', 1, 'Indian/Reunion') - (int) \Dotclear\Helper\Date::str('%H', 1, 'Europe/Paris')
        );
    }

    /**
     * dt2str is a wrapper for dt::str but convert the human readable time
     * into a computer understandable time
     */
    public function testDt2Str()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            \Dotclear\Helper\Date::str('%Y', 1),
            \Dotclear\Helper\Date::dt2str('%Y', '1970-01-01')
        );
    }

    /**
     *
     */
    public function testSetGetTZ()
    {
        \Dotclear\Helper\Date::setTZ('Indian/Reunion');

        $this->assertNotEmpty(
            \Dotclear\Helper\Date::getTZ()
        );
    }

    /**
     * dtstr with anything but the time. We don't test strtodate,
     * we test dt1str will always have the same behaviour.
     */
    public function testDt2DummyStr()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            \Dotclear\Helper\Date::str('%Y'),
            \Dotclear\Helper\Date::dt2str('%Y', 'Everything but a time')
        );
    }

    /*
     * Convert timestamp to ISO8601 date
     */
    public function testISO8601()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            '1970-01-01T00:00:01+00:00',
            \Dotclear\Helper\Date::iso8601(1, 'UTC')
        );
    }

    /*
     * Convert timestamp to ISO8601 date but not UTC.
     */
    public function testISO8601WithAnotherTimezone()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            '1970-01-01T00:00:01+04:00',
            \Dotclear\Helper\Date::iso8601(1, 'Indian/Reunion')
        );
    }

    public function testRfc822()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            'Thu, 01 Jan 1970 00:00:01 +0400',
            \Dotclear\Helper\Date::rfc822(1, 'Indian/Reunion')
        );
    }

    public function testGetTimeOffset()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            4 * 3600,
            \Dotclear\Helper\Date::getTimeOffset('Indian/Reunion')
        );
    }

    public function testToUTC()
    {
        \Dotclear\Helper\Date::setTZ('Indian/Reunion'); // UTC + 4

        $this->assertEquals(
            0,
            \Dotclear\Helper\Date::toUTC(4 * 3600)
        );
    }

    /*
     * AddTimezone implies getZones but I prefer testing both of them separatly
     */
    public function testAddTimezone()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            4 * 3600,
            \Dotclear\Helper\Date::addTimeZone('Indian/Reunion', 0)
        );

        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            0,
            \Dotclear\Helper\Date::addTimeZone('Indian/Reunion') - time() - \Dotclear\Helper\Date::getTimeOffset('Indian/Reunion')
        );
    }

    /*
     * There's many different time zone. Basicly, dt::getZone call a PHP function.
     * Ensure that the key is the value array('time/zone' => 'time/zone')
     */
    public function testGetZones()
    {
        $tzs = \Dotclear\Helper\Date::getZones();
        $this->assertNotEmpty(
            $tzs
        );
        $this->assertEquals(
            'Europe/Paris',
            $tzs['Europe/Paris']
        );

        // Test another call
        $tzs = \Dotclear\Helper\Date::getZones();
        $this->assertNotEmpty(
            $tzs
        );
        $this->assertEquals(
            'Indian/Reunion',
            $tzs['Indian/Reunion']
        );
    }

    public function testGetZonesFlip()
    {
        $tzs = \Dotclear\Helper\Date::getZones(true, false);

        $this->assertNotEmpty(
            $tzs
        );
        $this->assertEquals(
            'Europe/Paris',
            $tzs['Europe/Paris']
        );
    }

    public function testGetZonesGroup()
    {
        $tzs = \Dotclear\Helper\Date::getZones(true, true);

        $this->assertNotEmpty(
            $tzs
        );
        $this->assertNotEmpty(
            $tzs['Europe']
        );
        $this->assertNotEmpty(
            $tzs['Europe']['Europe/Paris']
        );
    }

    public function testStr()
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            '_Thu Thursday _Jan January',
            \Dotclear\Helper\Date::str('%a %A %b %B', 1)
        );
    }
}
