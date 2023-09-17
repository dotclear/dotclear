<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear;

use Exception;

/**
 * @brief   The helper to parse runtime error.
 *
 * @since   2.27, errors code returns on 3 digits
 * as some servers (ie Nginx) do not support HTTP1.0 code on 2 digits.
 */
class Fault
{
    /**
     * Uncoded or undefined error (before as 0).
     *
     * @var     int     UNDEFINED_ISSUE
     */
    public const UNDEFINED_ISSUE = 550;

    /**
     * Server configuration issue (before as 0).
     *
     * @var     int     SETUP_ISSUE
     */
    public const SETUP_ISSUE = 555;

    /**
     * Dotclear configuration file issue (before as 10).
     *
     * @var     int     CONFIG_ISSUE
     */
    public const CONFIG_ISSUE = 560;

    /**
     * Database connexion issue (before as 20).
     *
     * @var     int     DATABASE_ISSUE
     */
    public const DATABASE_ISSUE = 565;

    /**
     * Blog definition issue (before as 30).
     *
     * @var     int     BLOG_ISSUE
     */
    public const BLOG_ISSUE = 570;

    /**
     * Template file creation issue (before as 40).
     *
     * @var     int     TEMPLATE_CREATION_ISSUE
     */
    public const TEMPLATE_CREATION_ISSUE = 575;

    /**
     * Theme issue (before as 50).
     *
     * @var     int     THEME_ISSUE
     */
    public const THEME_ISSUE = 580;

    /**
     * Template processing issue (before as 60).
     *
     * @var     int     TEMPLATE_PROCESSING_ISSUE
     */
    public const TEMPLATE_PROCESSING_ISSUE = 585;

    /**
     * Blog is offline (before as 70).
     *
     * @var     int     BLOG_OFFLINE
     */
    public const BLOG_OFFLINE = 590;

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
}
