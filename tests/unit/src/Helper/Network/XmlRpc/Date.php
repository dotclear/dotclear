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

namespace tests\unit\Dotclear\Helper\Network\XmlRpc;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;
use DateTime;
use DateTimeZone;

/*
 * @tags XmlRpc, XmlRpcDate
 */
class Date extends atoum
{
    public function testWithString()
    {
        $str_date = '+2003-08-13T00:01:42+00:00';
        $ref_date = new DateTime($str_date, new DateTimeZone('Europe/Paris'));

        $date = new \Dotclear\Helper\Network\XmlRpc\Date($str_date);

        $this
            ->string($date->getIso())
            ->isEqualTo($ref_date->format('Ymd\TH\:i\:s'))
            ->string($date->getXml())
            ->isEqualTo('<dateTime.iso8601>' . $ref_date->format('Ymd\TH\:i\:s') . '</dateTime.iso8601>')
            ->integer($date->getTimestamp())
            ->isEqualTo($ref_date->getTimestamp())
        ;
    }

    public function testWithTimestamp()
    {
        $str_date = '+2003-08-13T00:01:42+00:00';
        $ref_date = new DateTime($str_date, new DateTimeZone('Europe/Paris'));

        $date = new \Dotclear\Helper\Network\XmlRpc\Date($ref_date->getTimestamp());

        $this
            ->string($date->getIso())
            ->isEqualTo($ref_date->format('Ymd\TH\:i\:s'))
            ->string($date->getXml())
            ->isEqualTo('<dateTime.iso8601>' . $ref_date->format('Ymd\TH\:i\:s') . '</dateTime.iso8601>')
            ->integer($date->getTimestamp())
            ->isEqualTo($ref_date->getTimestamp())
        ;
    }
}
