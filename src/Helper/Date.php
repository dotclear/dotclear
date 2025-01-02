<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use IntlGregorianCalendar;
use InvalidArgumentException;

/**
 * @class Date
 *
 * @brief Date/time utilities
 */
class Date
{
    /**
     * @var array<string, string>
     */
    private static array $timezones;

    /**
     * strftime() replacement when PHP version ≥ PHP 8.1
     *
     * Adapted from: <https://github.com/alphp/strftime>
     *
     * Locale-formatted strftime using IntlDateFormatter (PHP 8.1 compatible)
     * This provides a cross-platform alternative to strftime() for when it will be removed from PHP.
     * Note that output can be slightly different between libc sprintf and this function as it is using ICU.
     *
     * Usage:
     * use function \PHP81_BC\strftime;
     * echo strftime('%A %e %B %Y %X', new \DateTime('2021-09-28 00:00:00'), 'fr_FR');
     *
     * Original use:
     * \setlocale(LC_TIME, 'fr_FR.UTF-8');
     * echo \strftime('%A %e %B %Y %X', strtotime('2021-09-28 00:00:00'));
     *
     * @param  string                   $format         Date format
     * @param  integer|string|DateTime  $timestamp      Timestamp
     *
     * @author BohwaZ <https://bohwaz.net/>
     */
    public static function strftime(string $format, $timestamp = null, ?string $locale = null): string
    {
        if (!($timestamp instanceof DateTimeInterface)) {
            $timestamp = is_int($timestamp) ? '@' . $timestamp : (string) $timestamp;

            try {
                $timestamp = new DateTime($timestamp);
            } catch (Exception $e) {
                throw new InvalidArgumentException('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.', 0, $e);
            }
        }

        $timestamp->setTimezone(new DateTimeZone(date_default_timezone_get()));

        if ($locale === null || $locale === '') {
            // get current locale
            $locale = setlocale(LC_TIME, '0');
        }
        if ($locale === false) {
            $locale = 'en';
        }
        // remove trailing part not supported by ext-intl locale
        $locale = preg_replace('/[^\w-].*$/', '', $locale);

        $intl_formats = [
            '%a' => 'EEE',    // An abbreviated textual representation of the day Sun through Sat
            '%A' => 'EEEE',   // A full textual representation of the day Sunday through Saturday
            '%b' => 'MMM',    // Abbreviated month name, based on the locale  Jan through Dec
            '%B' => 'MMMM',   // Full month name, based on the locale January through December
            '%h' => 'MMM',    // Abbreviated month name, based on the locale (an alias of %b) Jan through Dec
        ];

        $intl_formatter = function (DateTimeInterface $timestamp, string $format) use ($intl_formats, $locale): string|false {
            $tz        = $timestamp->getTimezone();
            $date_type = IntlDateFormatter::FULL;
            $time_type = IntlDateFormatter::FULL;
            $pattern   = '';

            switch ($format) {
                // %c = Preferred date and time stamp based on locale
                // Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
                case '%c':
                    $date_type = IntlDateFormatter::LONG;
                    $time_type = IntlDateFormatter::SHORT;

                    break;

                    // %x = Preferred date representation based on locale, without the time
                    // Example: 02/05/09 for February 5, 2009
                case '%x':
                    $date_type = IntlDateFormatter::SHORT;
                    $time_type = IntlDateFormatter::NONE;

                    break;

                    // Localized time format
                case '%X':
                    $date_type = IntlDateFormatter::NONE;
                    $time_type = IntlDateFormatter::MEDIUM;

                    break;

                default:
                    $pattern = $intl_formats[$format];
            }

            // In October 1582, the Gregorian calendar replaced the Julian in much of Europe, and
            //  the 4th October was followed by the 15th October.
            // ICU (including IntlDateFormattter) interprets and formats dates based on this cutover.
            // Posix (including strftime) and timelib (including DateTimeImmutable) instead use
            //  a "proleptic Gregorian calendar" - they pretend the Gregorian calendar has existed forever.
            // This leads to the same instants in time, as expressed in Unix time, having different representations
            //  in formatted strings.
            // To adjust for this, a custom calendar can be supplied with a cutover date arbitrarily far in the past.
            $calendar = IntlGregorianCalendar::createInstance();
            $calendar->setGregorianChange(PHP_INT_MIN);

            return (new IntlDateFormatter($locale, $date_type, $time_type, $tz, $calendar, $pattern))->format($timestamp);
        };

        // Same order as https://www.php.net/manual/en/function.strftime.php
        $translation_table = [
            // Day
            '%a' => $intl_formatter,
            '%A' => $intl_formatter,
            '%d' => 'd',
            '%e' => fn ($timestamp): string => sprintf('% 2u', $timestamp->format('j')),
            '%j' => fn ($timestamp): string => sprintf('%03d', $timestamp->format('z') + 1), // Day number in year, 001 to 366
            '%u' => 'N',
            '%w' => 'w',

            // Week
            '%U' => function ($timestamp): string {
                // Number of weeks between date and first Sunday of year
                $day = new DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));

                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },
            '%V' => 'W',
            '%W' => function ($timestamp): string {
                // Number of weeks between date and first Monday of year
                $day = new DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));

                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },

            // Month
            '%b' => $intl_formatter,
            '%B' => $intl_formatter,
            '%h' => $intl_formatter,
            '%m' => 'm',

            // Year
            '%C' => fn ($timestamp): float => floor($timestamp->format('Y') / 100),    // Century (-1): 19 for 20th century
            '%g' => fn ($timestamp): string => substr((string) $timestamp->format('o'), -2),
            '%G' => 'o',
            '%y' => 'y',
            '%Y' => 'Y',

            // Time
            '%H' => 'H',
            '%k' => fn ($timestamp): string => sprintf('% 2u', $timestamp->format('G')),
            '%I' => 'h',
            '%l' => fn ($timestamp): string => sprintf('% 2u', $timestamp->format('g')),
            '%M' => 'i',
            '%p' => 'A', // AM PM (this is reversed on purpose!)
            '%P' => 'a', // am pm
            '%r' => 'h:i:s A', // %I:%M:%S %p
            '%R' => 'H:i', // %H:%M
            '%S' => 's',
            '%T' => 'H:i:s', // %H:%M:%S
            '%X' => $intl_formatter, // Preferred time representation based on locale, without the date

            // Timezone
            '%z' => 'O',
            '%Z' => 'T',

            // Time and Date Stamps
            '%c' => $intl_formatter,
            '%D' => 'm/d/Y',
            '%F' => 'Y-m-d',
            '%s' => 'U',
            '%x' => $intl_formatter,
        ];

        /* @phpstan-ignore-next-line */
        $out = (string) preg_replace_callback('/(?<!%)%([_#-]?)([a-zA-Z])/', function (array $match) use ($translation_table, $timestamp) {
            $prefix  = $match[1];
            $char    = $match[2];
            $pattern = '%' . $char;
            if ($pattern === '%n') {
                return "\n";
            } elseif ($pattern === '%t') {
                return "\t";
            }

            if (!isset($translation_table[$pattern])) {
                throw new InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $pattern));
            }

            $replace = $translation_table[$pattern];

            if (is_string($replace)) {
                $result = $timestamp->format($replace);
            } else {
                $result = $replace($timestamp, $pattern);   // @phpstan-ignore-line
            }

            return match ($prefix) {
                // replace leading zeros with spaces but keep last char if also zero
                '_' => preg_replace('/\G0(?=.)/', ' ', $result),        // @phpstan-ignore-line
                // remove leading zeros but keep last char if also zero
                '#', '-' => preg_replace('/^0+(?=.)/', '', $result),    // @phpstan-ignore-line
                default => $result,
            };
        }, $format);

        return str_replace('%%', '%', $out);
    }

    /**
     * Timestamp formating
     *
     * Returns a date formated like PHP <a href="http://www.php.net/manual/en/function.strftime.php">strftime</a>
     * function.
     * Special cases %a, %A, %b and %B are handled by {@link l10n} library.
     *
     * @param   string           $pattern        Format pattern
     * @param   int|false        $timestamp      Timestamp
     * @param   null|string      $timezone       Timezone
     */
    public static function str(string $pattern, $timestamp = null, ?string $timezone = null): string
    {
        if ($timestamp === null || $timestamp === false) {
            $timestamp = time();
        }

        $hash    = '799b4e471dc78154865706469d23d512';
        $pattern = (string) preg_replace('/(?<!%)%(a|A)/', '{{' . $hash . '__$1%w__}}', $pattern);
        $pattern = (string) preg_replace('/(?<!%)%(b|B)/', '{{' . $hash . '__$1%m__}}', $pattern);

        if ($timezone) {
            $current_timezone = self::getTZ();
            self::setTZ($timezone);
        }

        $res = self::strftime($pattern, (int) $timestamp);

        if ($timezone) {
            self::setTZ($current_timezone);
        }

        return (string) preg_replace_callback(
            '/{{' . $hash . '__(a|A|b|B)([0-9]{1,2})__}}/',
            function (array $args): string {
                $b = [
                    1  => '_Jan',
                    2  => '_Feb',
                    3  => '_Mar',
                    4  => '_Apr',
                    5  => '_May',
                    6  => '_Jun',
                    7  => '_Jul',
                    8  => '_Aug',
                    9  => '_Sep',
                    10 => '_Oct',
                    11 => '_Nov',
                    12 => '_Dec', ];

                $B = [
                    1  => 'January',
                    2  => 'February',
                    3  => 'March',
                    4  => 'April',
                    5  => 'May',
                    6  => 'June',
                    7  => 'July',
                    8  => 'August',
                    9  => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December', ];

                $a = [
                    1 => '_Mon',
                    2 => '_Tue',
                    3 => '_Wed',
                    4 => '_Thu',
                    5 => '_Fri',
                    6 => '_Sat',
                    0 => '_Sun', ];

                $A = [
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                    0 => 'Sunday', ];

                return __(${$args[1]}[(int) $args[2]]);
            },
            $res
        );
    }

    /**
     * Date to date
     *
     * Format a literal date to another literal date.
     *
     * @param string    $pattern         Format pattern
     * @param string    $datetime        Date
     * @param string    $timezone        Timezone
     */
    public static function dt2str(string $pattern, string $datetime, ?string $timezone = null): string
    {
        return self::str($pattern, strtotime($datetime), $timezone);
    }

    /**
     * ISO-8601 formatting
     *
     * Returns a timestamp converted to ISO-8601 format.
     *
     * @param integer    $timestamp        Timestamp
     * @param string     $timezone         Timezone
     */
    public static function iso8601(int $timestamp, string $timezone = 'UTC'): string
    {
        $offset         = self::getTimeOffset($timezone, $timestamp);
        $printed_offset = sprintf('%02u:%02u', abs($offset) / 3600, (abs($offset) % 3600) / 60);

        return date('Y-m-d\\TH:i:s', $timestamp) . ($offset < 0 ? '-' : '+') . $printed_offset;
    }

    /**
     * RFC-822 formatting
     *
     * Returns a timestamp converted to RFC-822 format.
     *
     * @param integer    $timestamp        Timestamp
     * @param string     $timezone         Timezone
     */
    public static function rfc822(int $timestamp, string $timezone = 'UTC'): string
    {
        # Get offset
        $offset         = self::getTimeOffset($timezone, $timestamp);
        $printed_offset = sprintf('%02u%02u', abs($offset) / 3600, (abs($offset) % 3600) / 60);

        // Avoid deprecated notice until PHP 9 should be supported or a correct strftime() replacement
        return (string) @strftime('%a, %d %b %Y %H:%M:%S ' . ($offset < 0 ? '-' : '+') . $printed_offset, $timestamp);
    }

    /**
     * Timezone set
     *
     * Set timezone during script execution.
     *
     * @param    string    $timezone        Timezone
     */
    public static function setTZ(string $timezone): void
    {
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set($timezone);

            return;
        }

        putenv('TZ=' . $timezone);
    }

    /**
     * Current timezone
     *
     * Returns current timezone.
     */
    public static function getTZ(): string
    {
        if (function_exists('date_default_timezone_get')) {
            return date_default_timezone_get();
        }

        return date('T');
    }

    /**
     * Time offset
     *
     * Get time offset for a timezone and an optionnal $ts timestamp.
     *
     * @param string            $timezone        Timezone
     * @param int|false         $timestamp       Timestamp
     */
    public static function getTimeOffset(string $timezone, $timestamp = false): int
    {
        if (!$timestamp) {
            $timestamp = time();
        }

        $server_timezone = self::getTZ();
        $server_offset   = (int) date('Z', (int) $timestamp);

        self::setTZ($timezone);
        $current_offset = (int) date('Z', (int) $timestamp);

        self::setTZ($server_timezone);

        return $current_offset - $server_offset;
    }

    /**
     * UTC conversion
     *
     * Returns any timestamp from current timezone to UTC timestamp.
     *
     * @param integer    $timestamp        Timestamp
     */
    public static function toUTC(int $timestamp): int
    {
        return $timestamp + self::getTimeOffset('UTC', $timestamp);
    }

    /**
     * Add timezone
     *
     * Returns a timestamp with its timezone offset.
     *
     * @param string             $timezone         Timezone
     * @param integer|boolean    $timestamp        Timestamp
     */
    public static function addTimeZone(string $timezone, $timestamp = false): int
    {
        if ($timestamp === false) {
            $timestamp = time();
        }

        return $timestamp + self::getTimeOffset($timezone, (int) $timestamp);
    }

    /**
     * Timzones
     *
     * Returns an array of supported timezones, codes are keys and names are values.
     *
     * @param boolean    $flip      Names are keys and codes are values
     * @param boolean    $groups    Return timezones in arrays of continents
     *
     * @return array<string, string>
     */
    public static function getZones(bool $flip = false, bool $groups = false): array
    {
        if (self::$timezones === []) {
            $timezones = DateTimeZone::listIdentifiers();
            $res       = [];
            if ($timezones) {
                foreach ($timezones as $timezone) {
                    $timezone = trim($timezone);
                    if ($timezone !== '') {
                        $res[$timezone] = str_replace('_', ' ', $timezone);
                    }
                }
            }
            // Store timezones for further accesses
            self::$timezones = $res;
        } else {
            // Timezones already set
            $res = self::$timezones;
        }

        if ($flip) {
            $res = array_flip($res);
            if ($groups) {
                $tmp = [];
                foreach ($res as $code => $timezone) {
                    $group                 = explode('/', $code);
                    $tmp[$group[0]][$code] = $timezone;
                }
                $res = $tmp;
            }
        }

        return $res;    // @phpstan-ignore-line
    }
}
