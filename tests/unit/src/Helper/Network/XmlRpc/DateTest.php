<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
    public function testWithString()
    {
        $str_date = '+2003-08-13T00:01:42+00:00';
        $ref_date = new DateTime($str_date, new DateTimeZone('Europe/Paris'));

        $date = new \Dotclear\Helper\Network\XmlRpc\Date($str_date);

        $this->assertEquals(
            $ref_date->format('Ymd\TH\:i\:s'),
            $date->getIso()
        );
        $this->assertEquals(
            '<dateTime.iso8601>' . $ref_date->format('Ymd\TH\:i\:s') . '</dateTime.iso8601>',
            $date->getXml()
        );
        $this->assertEquals(
            $ref_date->getTimestamp(),
            $date->getTimestamp()
        );
    }

    public function testWithTimestamp()
    {
        $str_date = '+2003-08-13T00:01:42+00:00';
        $ref_date = new DateTime($str_date, new DateTimeZone('Europe/Paris'));

        $date = new \Dotclear\Helper\Network\XmlRpc\Date($ref_date->getTimestamp());

        $this->assertEquals(
            $ref_date->format('Ymd\TH\:i\:s'),
            $date->getIso()
        );
        $this->assertEquals(
            '<dateTime.iso8601>' . $ref_date->format('Ymd\TH\:i\:s') . '</dateTime.iso8601>',
            $date->getXml()
        );
        $this->assertEquals(
            $ref_date->getTimestamp(),
            $date->getTimestamp()
        );
    }
}
