<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear;

use Dotclear\Helper\L10n;
use Throwable;

/**
 * @brief   The helper to parse runtime error.
 *
 * @since   2.27, errors code returns on 3 digits
 * as some servers (ie Nginx) do not support HTTP1.0 code on 2 digits.
 */
class Fault
{
    /**
     * jTraceEx() - provide a Java style exception trace.
     *
     * @param   $e      The exception
     * @param   $seen   Internal loop
     *
     * @return  string  The formated trace
     */
    private static function jTraceEx($e, $seen = null)
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result  = [];
        if (!$seen) {
            $seen = [];
        }
        $trace    = $e->getTrace();
        $prev     = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file     = $e->getFile();
        $line     = $e->getLine();
        while (true) {
            $result[] = sprintf(
                ' at %s%s%s (%s%s%s)',
                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                $line === null ? $file : basename($file),
                $line === null ? '' : ':',
                $line === null ? '' : $line
            );
            if (is_array($seen)) {
                $seen[] = "$file:$line";
            }
            if (!count($trace)) {
                break;
            }
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = implode("\n", $result);
        if ($prev) {
            $result .= "\n" . self::jTraceEx($prev, $seen);
        }

        return $result;
    }

    /**
     * Parse throwable exception.
     *
     * @return  never
     */
    public static function exit(Throwable $exception): never
    {
        $code    = $exception->getCode() ?: 500;
        $label   = $exception->getMessage();
        $message = $exception->getMessage();

        if (PHP_SAPI == 'cli') {
            echo $label . ' (' . $code . ")\n";
            exit;
        }

        // Check if previous error is known
        if (($previous = $exception->getPrevious()) !== null) {
            $message = $previous->getMessage();
        }

        // Be sure to have __() function
        L10n::bootstrap();

        $trace  = htmlspecialchars((!defined('DC_DEBUG') || DC_DEBUG === true) && $exception !== null ? self::jTraceEx($exception) : '');
        $vendor = htmlspecialchars(defined('DC_VENDOR_NAME') ? DC_VENDOR_NAME : 'Dotclear');

        if (defined('DC_ERRORFILE') && is_file(DC_ERRORFILE)) {
            include DC_ERRORFILE;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('HTTP/1.0 ' . $code . ' ' . $label);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />
  <meta name="GOOGLEBOT" content="NOSNIPPET" />
  <title><?php echo $vendor; ?> - Error</title>
  <style media="screen" type="text/css">
  <!--
  body {
    font: 62.5%/1.5em "DejaVu Sans","Lucida Grande","Lucida Sans Unicode",Arial,sans-serif;
    color : #000;
    background : #B2B2B2;
    margin : 0;
    padding : 0;
  }
  #content {
      margin: 10px 25%;
      padding: 1px 1em 2em;
      background: #ECECEC;
      font-size: 1.4em;
  }
  a, a:link, a:visited {
    color : #137BBB;
    text-decoration : none;
    border-bottom : 1px dotted #C44D58;
  }
  h1 {
    color: #137BBB;
    font-size: 2.5em;
    font-weight: normal;
  }

  h2 {
    color: #C44D58;
    font-size: 1.5em;
  }
  -->
</style>
</head>

<body>
<div id="content">
<h1><?php echo $vendor; ?></h1>
<h2><?php echo $code . ' : ' . __($label); ?></h2>
<?php echo nl2br($message); ?>
<pre><?php echo $trace; ?></pre>
</div>
</body>
</html>
        <?php
        exit;
    }
}
