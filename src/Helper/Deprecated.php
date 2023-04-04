<?php
/**
 * @class Deprecated
 *
 * @since 2.26
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper;

class Deprecated
{
    /** @var    int     Error level */
    public const DEPRECATED_ERROR_LEVEL = E_USER_DEPRECATED;

    /** @var    Deprecated  Logger instance */
    private static $logger;

    // log contents parser
    public static string $bloc = '<pre>%s<ul>%s</ul></pre>';
    public static string $head = '<p>%s</p>';
    public static string $line = '<li>%s</li>';

    /**
     * Set a custom logger.
     * 
     * The custom logger has priority on this class or child class logger.
     * 
     * @param   String  $logger  Logger class name
     */
    public static function setLogger(string $logger): void
    {
        // chek and set only once an external logger
        if (!(self::$logger instanceof self) || !is_subclass_of($logger, 'Dotclear\Helper\Deprecated', true)) {
            return;
        }

        self::$logger = new $logger();
    }

    /**
     * Set a deprecated log.
     * 
     * @param   null|string     $replacement    Function to use in replacement of deprecated one
     * @param   null|string     $since          Version from which this is deprecated
     * @param   null|string     $upto           Version where this is removed
     */
    public static function set(?string $replacement = null, ?string $since = null, ?string $upto = null): void
    {
        // get backtrace
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // remove call to this method
        array_shift($traces);

        // clean trace
        $title  = '';
        $lines  = [];
        foreach ($traces as $line) {
            $class = !empty($line['class']) ? $line['class'] . '::' : '';
            $func  = !empty($line['function']) ? $line['function'] . '() ' : '';
            $file  = !empty($line['file']) ? $line['file'] . ':' : '';
            $line  = !empty($line['line']) ? $line['line'] : '';

            if ($replacement !== null && empty($lines)) {
                $title = $class . $func . ' is deprecated' .
                    ($since !== null ? ' since version ' . $since : '') .
                    ($upto !== null ? ' and wil be removed in version ' . $upto : '') .
                    (!empty($replacement) ? ', use ' . $replacement . ' as replacement' : '') .
                    '.';
            }

            $lines[] = $class . $func . $file . $line;
        }

        // call log method. First external method, or second child method, or third class method
        if (!(self::$logger instanceof self)) {
            static::log($title, $lines);
        } else {
            self::$logger::log($title, $lines);
        }
    }

    /**
     * Log deprecated function.
     * 
     * Child class must implement this method to log traces.
     * Note this method trigger low level error.
     * 
     * @param   string  $title  The title
     * @param   array   $lines  The cleaned trace lines
     */
    protected static function log(string $title, array $lines): void
    {
        array_walk($lines, function (&$item) { $item = sprintf(self::$line, $item); });

        trigger_error(
            sprintf(
                self::$bloc,
                empty($title) ? '' : sprintf(self::$head, $title),
                implode($lines)
            ),
            self::DEPRECATED_ERROR_LEVEL
        );
    }
}
