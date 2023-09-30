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
use Dotclear\Exception\AbstractException;
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
     * Parse throwable exception.
     *
     * @return  never
     */
    public static function exit(Throwable $exception): never
    {
        $code    = $exception->getCode() ?: 500;
        $label   = $exception->getMessage();
        $message = $exception->getMessage();
        $trace   = 'in ' . $exception->getFile() . '(' . $exception->getLine() . ')' . "\n" . $exception->getTraceAsString();

        if (PHP_SAPI == 'cli') {
            echo $label . ' (' . $code . ")\n";
            exit;
        }

        // Check if previous error is known
        if (($previous = $exception->getPrevious()) !== null) {
            $code    = $previous->getCode() ?: 500;
            $message = $previous->getMessage();

            // Application exceptions create new Exception instance
            if (is_a($exception, AbstractException::class) && ($from = $previous->getPrevious()) !== null) {
                $previous = $from;
            }

            $trace = 'in ' . $previous->getFile() . '(' . $previous->getLine() . ')' . "\n" . $previous->getTraceAsString();
        }

        // Be sure to have __() function
        L10n::bootstrap();

        $trace  = htmlspecialchars((!defined('DC_DEBUG') || DC_DEBUG === true) && $exception !== null ? $trace : '');
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
