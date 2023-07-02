<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear;

use Exception;

/**
 * Dotclear runtime error handler.
 *
 * Since 2.27, errors code returns on 3 digits
 * as some servers (ie Nginx) do not support HTTP1.0 code on 2 digits.
 */
class Fault
{
    /** @var    int     Uncoded or undefined error (before as 0) */
    public const UNDEFINED_ISSUE = 550;

    /** @var    int     Server configuration issue (before as 0) */
    public const SETUP_ISSUE = 555;

    /** @var    int     Dotclear configuration file issue (before as 10) */
    public const CONFIG_ISSUE = 560;

    /** @var    int     Database connexion issue (before as 20) */
    public const DATABASE_ISSUE = 565;

    /** @var    int     Blog definition issue (before as 30) */
    public const BLOG_ISSUE = 570;

    /** @var    int     Template file creation issue (before as 40) */
    public const TEMPLATE_CREATION_ISSUE = 575;

    /** @var    int     Theme issue (before as 50) */
    public const THEME_ISSUE = 580;

    /** @var    int     Template processing issue (before as 60) */
    public const TEMPLATE_PROCESSING_ISSUE = 585;

    /** @var    int     Blog is offline (before as 70) */
    public const BLOG_OFFLINE = 590;

    /**
     * Constructor.
     *
     * In CLI mode, only summary is returned.
     * A custom file path COULD be set in DC_ERRORFILE to serve error.
     *
     * @param   string  $summary    The short description
     * @param   string  $message    The details
     * @param   int     $code       The code (HTTP code)
     */
    public function __construct(string $summary, string $message, int $code = 550)
    {
        if (PHP_SAPI == 'cli') {
            echo $summary . "\n";
            exit;
        }
        if (defined('DC_ERRORFILE') && is_file(DC_ERRORFILE)) {
            include DC_ERRORFILE;
        } else {
            $vendor = defined('DC_VENDOR_NAME') ? htmlspecialchars(DC_VENDOR_NAME) : 'Dotclear';

            header('Content-Type: text/html; charset=utf-8');
            header('HTTP/1.0 ' . $code . ' ' . $summary);
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
<h2><?php echo $code . ' : ' . $summary; ?></h2>
<?php echo $message; ?></div>
</body>
</html>
            <?php
        }
        exit;
    }

    /**
     * Output error, the static way.
     *
     * @param   string  $summary    The short description
     * @param   string  $message    The details
     * @param   int     $code       The code (HTTP code)
     */
    public static function render(string $summary, string $message, int $code): void
    {
        new self($summary, $message, $code);
    }

    /**
     * Output error using Exception instance.
     *
     * This takes care of DC_DEBUG mode to show Exceptions stack.
     *
     * @return never
     */
    public static function throw(string $summary, Exception $e)
    {
        if (defined('DC_DEBUG') && DC_DEBUG === true) {
            throw $e;
        }
        new self($summary, $e->getMessage(), $e->getCode() ?: self::UNDEFINED_ISSUE);
        exit;
    }
}
