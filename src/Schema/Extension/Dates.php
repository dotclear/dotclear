<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Extension;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

/**
 * @brief Dotclear dates record helpers
 *
 * This class adds new methods to database dates results.
 * You can call them on every record comming from Blog::getDates and similar
 * methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class Dates
{
    /**
     * Convert date to timestamp
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function ts(MetaRecord $rs): int
    {
        return (int) strtotime((string) $rs->dt);
    }

    /**
     * Get date year
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function year(MetaRecord $rs): string
    {
        return date('Y', (int) strtotime((string) $rs->dt));
    }

    /**
     * Get date month
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function month(MetaRecord $rs): string
    {
        return date('m', (int) strtotime((string) $rs->dt));
    }

    /**
     * Get date day
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function day(MetaRecord $rs): string
    {
        return date('d', (int) strtotime((string) $rs->dt));
    }

    /**
     * Returns date month archive full URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function url(MetaRecord $rs): string
    {
        $url = date('Y/m', (int) strtotime((string) $rs->dt));

        return App::blog()->url() . App::url()->getURLFor('archive', $url);
    }

    /**
     * Returns whether date is the first of year.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function yearHeader(MetaRecord $rs): bool
    {
        if ($rs->isStart()) {
            return true;
        }

        $y = $rs->year();
        $rs->movePrev();
        $py = $rs->year();
        $rs->moveNext();

        return $y != $py;
    }

    /**
     * Returns whether date is the last of year.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function yearFooter(MetaRecord $rs): bool
    {
        if ($rs->isEnd()) {
            return true;
        }

        $y = $rs->year();
        if ($rs->moveNext()) {
            $ny = $rs->year();
            $rs->movePrev();

            return $y != $ny;
        }

        return false;
    }
}
