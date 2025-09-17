<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Exception\AppException;
use Dotclear\Helper\L10n;
use Dotclear\Interface\Core\FaultInterface;
use Throwable;

/**
 * @brief   The helper to parse runtime error.
 *
 * @since   2.27, errors code returns on 3 digits
 * as some servers (ie Nginx) do not support HTTP1.0 code on 2 digits.
 * @since   2.36, Fault is a core service
 */
class Fault implements FaultInterface
{
    /**
     * Handler watchdog.
     */
    private static bool $watchdog;

    /**
     * The debug mode.
     */
    protected bool $debug_mode = false;

    /**
     * The custom error file.
     */
    protected string $error_file = '';

    /**
     * The vendor name.
     */
    protected string $vendor_name = 'Dotclear';

    public function __construct()
    {
        $this->setExceptionHandler();
    }

    public function exception(string $message = '', int $code = 0, ?Throwable $previous = null): AppException
    {
        return new AppException($message, $code, $previous);
    }

    public function setDebugMode(bool $debug_mode): void
    {
        $this->debug_mode = $debug_mode;
    }

    public function setErrorFile(string $error_file): void
    {
        $this->error_file = $error_file;
    }

    public function setVendorName(string $vendor_name): void
    {
        $this->vendor_name = $vendor_name;
    }

    public function setExceptionHandler(): bool
    {
        // Do not set twice the handler
        if (isset(self::$watchdog)) {
            return false;
        }

        // Set exception handler
        if (set_exception_handler($this->handler(...)) !== null) {
            // Keep previously defined exception handler if any
           restore_exception_handler();

           return false;
        }

        return self::$watchdog = true;
    }

    /**
     * Exception handler.
     *
     * @param   Throwable   $exception  The exception
     */
    protected function handler(Throwable $exception): void
    {
        try {
            // We may need l10n __() function (should be already loaded but hey)
            L10n::bootstrap();
        } catch (Throwable) {
            // Continue even if L10n is broken
        }

        // Parse some Exception values. And try to translate them even if they are already translated.
        $code    = $exception->getCode() ?: 500;
        $label   = htmlspecialchars(strip_tags($this->trans($exception->getMessage()))) ?: $this->trans('Site temporarily unavailable');
        $message = nl2br($this->trans($exception->getPrevious() instanceof Throwable ? $exception->getPrevious()->getMessage() : $exception->getMessage()));
        $trace   = $this->debug_mode ? htmlspecialchars($this->trace($exception)) : '';

        // Stop in CLI mode
        if (PHP_SAPI === 'cli') {
            echo $label . ' (' . $code . ")\n";
            dotclear_exit(1);
        }

        // Load custom error file if any
        if ($this->error_file !== '' && is_file($this->error_file)) {
            include $this->error_file;
        }

        // Render HTTP page
        $this->render((int) $code, $label, $message, $trace);
    }

    /**
     * Try to translate message.
     */
    protected function trans(string $str): string
    {
        try {
            return function_exists('\__') ? __($str) : $str;
        } catch (Throwable) {
            return $str;
        }
    }

    /**
     * Provide a Java style exception trace.
     *
     * Inspired from PHP online manuel comment at
     * https://www.php.net/manual/fr/exception.gettraceasstring.php#114980
     *
     * @param   Throwable   $exception  The exception
     * @param   bool        $loop       Internal loop
     *
     * @return  string  The formated trace
     */
    protected function trace(Throwable $exception, bool $loop = false): string
    {
        $starter  = $loop ? 'Caused by: ' : '';
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
            if ($trace === []) {
                break;
            }
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = implode("\n", $result);
        if ($prev instanceof Throwable) {
            $result .= "\n" . $this->trace($prev, true);
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
    protected function render(int $code, string $label, string $message, string $trace = ''): never
    {
        // Try to remove any previous buffer without notice
        if (ob_get_length()) {
            ob_clean();
        }

        $vendor = htmlspecialchars($this->vendor_name);

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
        body {
            font: 62.5%/1.5em "DejaVu Sans","Lucida Grande","Lucida Sans Unicode",Arial,sans-serif;
            color : #000;
            background : #B2B2B2;
            margin : 0;
            padding : 0;
            line-height: 2em;
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
        dotclear_exit();
    }
}
