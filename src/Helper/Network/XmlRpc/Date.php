<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc;

/**
 * @class Date
 *
 * XLM-RPC helpers
 */
class Date
{
    /**
     * Date year part
     *
     * @var string
     */
    protected $year;

    /**
     * Date month part
     *
     * @var string
     */
    protected $month;

    /**
     * Date day part
     *
     * @var string
     */
    protected $day;

    /**
     * Date hour part
     *
     * @var string
     */
    protected $hour;

    /**
     * Date minute part
     *
     * @var string
     */
    protected $minute;

    /**
     * Date second part
     */
    protected $second;

    /**
     * Date timestamp
     *
     * @var int
     */
    protected $ts;

    /**
     * Constructor
     *
     * Creates a new instance of Date. <var>$time</var> could be a
     * timestamp or a litteral date.
     *
     * @param integer|string    $time        Timestamp (Unix) or litteral date.
     */
    public function __construct($time)
    {
        # $time can be a PHP timestamp or an ISO one
        $this->parseTimestamp(is_numeric($time) ? $time : strtotime($time));
    }

    /**
     * Timestamp parser
     *
     * @param int        $timestamp    Timestamp (Unix)
     */
    protected function parseTimestamp(int $timestamp)
    {
        $this->year   = date('Y', $timestamp);
        $this->month  = date('m', $timestamp);
        $this->day    = date('d', $timestamp);
        $this->hour   = date('H', $timestamp);
        $this->minute = date('i', $timestamp);
        $this->second = date('s', $timestamp);

        $this->ts = $timestamp;
    }

    /**
     * ISO Date
     *
     * Returns the date in ISO-8601 format.
     *
     * @return string
     */
    public function getIso(): string
    {
        return $this->year . $this->month . $this->day . 'T' . $this->hour . ':' . $this->minute . ':' . $this->second;
    }

    /**
     * XML Date
     *
     * Returns the XML fragment for XML-RPC message inclusion.
     *
     * @return string
     */
    public function getXml(): string
    {
        return '<dateTime.iso8601>' . $this->getIso() . '</dateTime.iso8601>';
    }

    /**
     * Timestamp
     *
     * Returns the date timestamp (Unix).
     *
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->ts;
    }
}
