<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
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
     *
     * @return     integer
     */
    public static function ts(MetaRecord $rs): int
    {
        return strtotime((string) $rs->dt);
    }

    /**
     * Get date year
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function year(MetaRecord $rs): string
    {
        return date('Y', strtotime((string) $rs->dt));
    }

    /**
     * Get date month
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function month(MetaRecord $rs): string
    {
        return date('m', strtotime((string) $rs->dt));
    }

    /**
     * Get date day
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function day(MetaRecord $rs): string
    {
        return date('d', strtotime((string) $rs->dt));
    }

    /**
     * Returns date month archive full URL.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string
     */
    public static function url(MetaRecord $rs): string
    {
        $url = date('Y/m', strtotime((string) $rs->dt));

        return App::blog()->url() . App::url()->getURLFor('archive', $url);
    }

    /**
     * Returns whether date is the first of year.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool
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
     *
     * @return     bool
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
