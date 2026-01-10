<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Dotclear\Helper\Date::class)]
class HelperDateTest extends TestCase
{
    protected function setUp(): void
    {
        date_default_timezone_set('UTC');
        setlocale(LC_TIME, 'C');
    }

    // strftime()

    public function testStrftimeWithNullTimestamp(): void
    {
        $result = \Dotclear\Helper\Date::strftime('%Y', null);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $result);
    }

    public function testStrftimeWithIntegerTimestamp(): void
    {
        $this->assertSame(
            '1970-01-01',
            \Dotclear\Helper\Date::strftime('%Y-%m-%d', 0)
        );
    }

    public function testStrftimeWithDateString(): void
    {
        $this->assertSame(
            '2022',
            \Dotclear\Helper\Date::strftime('%Y', '2022-01-01')
        );
    }

    public function testStrftimeRejectsInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        \Dotclear\Helper\Date::strftime('%Y', 'not-a-date');
    }

    public function testStrftimeBasicFormats(): void
    {
        $ts = strtotime('2021-12-31 23:59:58 UTC');

        $this->assertSame('31', \Dotclear\Helper\Date::strftime('%d', $ts));
        $this->assertSame('12', \Dotclear\Helper\Date::strftime('%m', $ts));
        $this->assertSame('2021', \Dotclear\Helper\Date::strftime('%Y', $ts));
        $this->assertSame('23', \Dotclear\Helper\Date::strftime('%H', $ts));
        $this->assertSame('59', \Dotclear\Helper\Date::strftime('%M', $ts));
        $this->assertSame('58', \Dotclear\Helper\Date::strftime('%S', $ts));
        $this->assertSame('52', \Dotclear\Helper\Date::strftime('%W', $ts));
        $this->assertSame('52', \Dotclear\Helper\Date::strftime('%U', $ts));
    }

    public function testStrftimePercentEscaping(): void
    {
        $this->assertSame('%Y', \Dotclear\Helper\Date::strftime('%%Y', 0));
    }

    public function testStrftimeTabAndNewLine(): void
    {
        $this->assertSame("\n", \Dotclear\Helper\Date::strftime('%n', 0));
        $this->assertSame("\t", \Dotclear\Helper\Date::strftime('%t', 0));
    }

    public function testStrftimePrefixUnderscore(): void
    {
        $ts = strtotime('2021-01-01 01:02:03 UTC');
        $this->assertSame(' 1', \Dotclear\Helper\Date::strftime('%_d', $ts));
    }

    public function testStrftimePrefixDash(): void
    {
        $ts = strtotime('2021-01-01 01:02:03 UTC');
        $this->assertSame('1', \Dotclear\Helper\Date::strftime('%-d', $ts));
    }

    public function testStrftimePrefixHash(): void
    {
        $ts = strtotime('2021-01-01 01:02:03 UTC');
        $this->assertSame('1', \Dotclear\Helper\Date::strftime('%#d', $ts));
    }

    public function testStrftimeLocalizedFormats(): void
    {
        $ts = strtotime('2021-09-28 12:00:00 UTC');

        $this->assertNotEmpty(\Dotclear\Helper\Date::strftime('%A', $ts, 'en_US'));
        $this->assertNotEmpty(\Dotclear\Helper\Date::strftime('%B', $ts, 'en_US'));
        $this->assertNotEmpty(\Dotclear\Helper\Date::strftime('%c', $ts, 'en_US'));
        $this->assertNotEmpty(\Dotclear\Helper\Date::strftime('%x', $ts, 'en_US'));
        $this->assertNotEmpty(\Dotclear\Helper\Date::strftime('%X', $ts, 'en_US'));
    }

    public function testStrftimeUnknownSpecifierThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        \Dotclear\Helper\Date::strftime('%Q', time());
    }

    // str()

    public function testStrUsesCurrentTimeWhenNull(): void
    {
        $result = \Dotclear\Helper\Date::str('%Y');
        $this->assertMatchesRegularExpression('/^\d{4}$/', $result);
    }

    public function testStrWithFalseTimestamp(): void
    {
        $result = \Dotclear\Helper\Date::str('%Y', false);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $result);
    }

    public function testStrTimezoneSwitch(): void
    {
        $ts = strtotime('2021-12-01 12:00:00 UTC');

        $this->assertSame(
            '13',
            \Dotclear\Helper\Date::str('%H', $ts, 'Europe/Paris')
        );
    }

    /**
     * Normal way. The result must be as the PHP function.
     */
    public function testStrNormal(): void
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
    public function testStrTimestamp(): void
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
    public function testStrWithTimestampAndTimezone(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            3,
            (int) \Dotclear\Helper\Date::str('%H', 1, 'Indian/Reunion') - (int) \Dotclear\Helper\Date::str('%H', 1, 'Europe/Paris')
        );
    }

    // dt2str()

    /**
     * dt2str is a wrapper for dt::str but convert the human readable time
     * into a computer understandable time
     */
    public function testDt2Str(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            \Dotclear\Helper\Date::str('%Y', 1),
            \Dotclear\Helper\Date::dt2str('%Y', '1970-01-01')
        );
    }

    public function testDt2StrWorks(): void
    {
        $this->assertSame(
            '2022-03-10',
            \Dotclear\Helper\Date::dt2str('%Y-%m-%d', '2022-03-10 10:00:00')
        );
    }

    public function testSetGetTZ(): void
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
    public function testDt2DummyStr(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            \Dotclear\Helper\Date::str('%Y'),
            \Dotclear\Helper\Date::dt2str('%Y', 'Everything but a time')
        );
    }

    // iso8601()

    /*
     * Convert timestamp to ISO8601 date
     */
    public function testISO8601(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            '1970-01-01T00:00:01+00:00',
            \Dotclear\Helper\Date::iso8601(1, 'UTC')
        );
    }

    public function testIso8601PositiveOffset(): void
    {
        $ts = strtotime('2021-06-01 12:00:00 UTC');
        $this->assertSame(
            '2021-06-01T12:00:00+02:00',
            \Dotclear\Helper\Date::iso8601($ts, 'Europe/Paris')
        );
    }

    public function testIso8601NegativeOffset(): void
    {
        $ts = strtotime('2021-06-01 12:00:00 UTC');
        $this->assertSame(
            '2021-06-01T12:00:00-04:00',
            \Dotclear\Helper\Date::iso8601($ts, 'America/New_York')
        );
    }

    /*
     * Convert timestamp to ISO8601 date but not UTC.
     */
    public function testISO8601WithAnotherTimezone(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            '1970-01-01T00:00:01+04:00',
            \Dotclear\Helper\Date::iso8601(1, 'Indian/Reunion')
        );
    }

    // rfc822()

    public function testRfc822Format(): void
    {
        $ts  = strtotime('2021-06-01 12:00:00 UTC');
        $res = \Dotclear\Helper\Date::rfc822($ts, 'UTC');

        $this->assertMatchesRegularExpression(
            '/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} \+0000$/',
            $res
        );
    }

    public function testRfc822(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            'Thu, 01 Jan 1970 00:00:01 +0400',
            \Dotclear\Helper\Date::rfc822(1, 'Indian/Reunion')
        );
    }

    public function testGetTimeOffsetIndian(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            4 * 3600,
            \Dotclear\Helper\Date::getTimeOffset('Indian/Reunion')
        );
    }

    public function testToUTC(): void
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
    public function testGetAndSetTimezone(): void
    {
        \Dotclear\Helper\Date::setTZ('Europe/Paris');
        $this->assertSame('Europe/Paris', \Dotclear\Helper\Date::getTZ());

        \Dotclear\Helper\Date::setTZ('UTC');
    }

    public function testGetTimeOffset(): void
    {
        $ts = strtotime('2021-06-01 12:00:00 UTC');
        $this->assertSame(
            7200,
            \Dotclear\Helper\Date::getTimeOffset('Europe/Paris', $ts)
        );
    }

    public function testToUtcFrance(): void
    {
        $ts = strtotime('2021-06-01 14:00:00 Europe/Paris');
        $this->assertSame(
            strtotime('2021-06-01 12:00:00 UTC'),
            \Dotclear\Helper\Date::toUTC($ts)
        );
    }

    public function testAddTimeZone(): void
    {
        $ts = strtotime('2021-06-01 12:00:00 UTC');
        $this->assertSame(
            strtotime('2021-06-01 14:00:00 UTC'),
            \Dotclear\Helper\Date::addTimeZone('Europe/Paris', $ts)
        );
    }

    public function testAddTimezoneIndian(): void
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
    public function testGetZones(): void
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

    public function testGetZonesFlip(): void
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

    public function testGetZonesGroup(): void
    {
        $tzs = \Dotclear\Helper\Date::getZones(true, true);

        $this->assertNotEmpty(
            $tzs
        );
        $this->assertNotEmpty(
            $tzs['Europe']
        );
        $this->assertNotEmpty(
            // @phpstan-ignore offsetAccess.notFound
            $tzs['Europe']['Europe/Paris']
        );
    }

    public function testGetZonesDefault(): void
    {
        $zones = \Dotclear\Helper\Date::getZones();
        $this->assertArrayHasKey('UTC', $zones);
    }

    public function testGetZonesFlipUTC(): void
    {
        $zones = \Dotclear\Helper\Date::getZones(true);
        $this->assertContains('UTC', $zones);
    }

    public function testGetZonesGrouped(): void
    {
        $zones = \Dotclear\Helper\Date::getZones(true, true);
        $this->assertArrayHasKey('Europe', $zones);
    }

    public function testGetZonesCacheIsUsed(): void
    {
        $first  = \Dotclear\Helper\Date::getZones();
        $second = \Dotclear\Helper\Date::getZones();

        $this->assertSame($first, $second);
    }

    public function testStr(): void
    {
        \Dotclear\Helper\Date::setTZ('UTC');

        $this->assertEquals(
            '_Thu Thursday _Jan January',
            \Dotclear\Helper\Date::str('%a %A %b %B', 1)
        );
    }
}
