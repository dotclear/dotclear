<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

header('Content-Type: text/html; charset=utf-8');
header("HTTP/1.0 " . $code . " " . $summary);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />
  <meta name="GOOGLEBOT" content="NOSNIPPET" />
  <title>Dotclear - Error</title>
  <style media="screen" type="text/css">
  <!--
  body {
    font: 62.5%/1.5em "DejaVu Sans","Lucida Grande","Lucida Sans Unicode",Arial,sans-serif;
    color : #000;
    background : #E5E3DA;
    margin : 0;
    padding : 0;
  }
  #content {
      margin: 0 25%;
      padding: 1px 1em 2em;
      background: #fff;
      font-size: 1.2em;
  }
  a, a:link, a:visited {
    color : #2373A8;
    text-decoration : none;
    border-bottom : 1px dotted #f90;
  }
  h1 {
    color: #2373A8;
    font-size: 2.5em;
    font-weight: normal;
  }

  h2 {
    font-size: 1.5em;
  }
  -->
</style>
</head>

<body>
<div id="content">
<h1>Dotclear</h1>
<h2><?php echo $summary; ?></h2>
<?php echo $message; ?></div>
</body>
</html>
