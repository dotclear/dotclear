<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
     * The application configuration (if loaded).
     *
     * Class can work without Config for early exception.
     *
     * @var     Config  $config
     */
    public static ?Config $config = null;

    /**
     * Constructor parse throwable exception or error.
     *
     * @param   Throwable   $exception  The exception
     */
    public function __construct(Throwable $exception)
    {
        // We may need l10n __() function (should be already loaded but hey)
        L10n::bootstrap();

        // Parse some Exception values. And try to translate them even if they are already translated.
        $code    = $exception->getCode() ?: 500;
        $label   = htmlspecialchars(__($exception->getMessage()));
        $message = nl2br(__($exception->getPrevious() === null ? $exception->getMessage() : $exception->getPrevious()->getMessage()));
        $trace   = htmlspecialchars(self::$config?->debugMode() !== false ? self::trace($exception) : '');

        // Stop in CLI mode
        if (PHP_SAPI == 'cli') {
            echo $label . ' (' . $code . ")\n";
            exit;
        }

        // Load custom error file if any
        if (isset(self::$config) && is_file(self::$config->errorFile())) {
            include self::$config->errorFile();
        }

        // Render HTTP page
        self::render((int) $code, $label, $message, $trace);
    }

    /**
     * Set exception handler.
     *
     * Set Fault as exception handler if another one is not set.
     */
    public static function setExceptionHandler(): void
    {
        // Set exception handler
        if (set_exception_handler(function (Throwable $exception) { new self($exception); }) !== null) {
            // Keep previously defined exception handler if any
            restore_exception_handler();
        }
    }

    /**
     * Provide a Java style exception trace.
     *
     * Inspired from PHP online manuel comment at
     * https://www.php.net/manual/fr/exception.gettraceasstring.php#114980
     *
     * @param   Throwable           $exception  The exception
     * @param   array<int,string>   $seen       Internal loop
     *
     * @return  string  The formated trace
     */
    public static function trace(Throwable $exception, ?array $seen = null)
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result  = [];
        if (!$seen) {
            $seen = [];
        }
        $trace    = $exception->getTrace();
        $prev     = $exception->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, $exception::class, $exception->getMessage());
        $file     = $exception->getFile();
        $line     = $exception->getLine();
        while (true) {
            $result[] = sprintf(
                ' at %s%s%s (%s%s%s)',
                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                count($trace) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                $line === null ? $file : basename($file),
                $line === null ? '' : ':',
                $line ?? ''
            );
            $seen[] = "$file:$line";
            if (!count($trace)) {
                break;
            }
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = implode("\n", $result);
        if ($prev) {
            $result .= "\n" . self::trace($prev, $seen);
        }

        return $result;
    }

    /**
     * Render HTML error page.
     *
     * @param   int     $code       The exception code
     * @param   string  $label      The exception label (page title)
     * @param   string  $message    The exception message
     * @param   string  $trace      THe xecption trace
     */
    public static function render(int $code, string $label, string $message, string $trace = ''): never
    {
        $vendor = htmlspecialchars(self::$config?->vendorName() ?: 'Dotclear');

        // HTTP header
        header('Content-Type: text/html; charset=utf-8');
        header('HTTP/1.0 ' . $code . ' ' . $label);

        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW">
  <meta name="GOOGLEBOT" content="NOSNIPPET">
  <title><?= $vendor ?> - Error</title>
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
<h1><?= $vendor ?></h1>
<h2><?= $code ?> : <?= $label ?></h2>
<?= $message ?>
<pre><?= $trace ?></pre>
</div>
</body>
</html>
        <?php
        exit;
    }
}
