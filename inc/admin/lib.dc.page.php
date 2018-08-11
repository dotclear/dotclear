<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

define('DC_AUTH_PAGE', 'auth.php');

class dcPage
{
    private static $loaded_js     = array();
    private static $loaded_css    = array();
    private static $xframe_loaded = false;
    private static $N_TYPES       = array(
        "success" => "success",
        "warning" => "warning-msg",
        "error"   => "error",
        "message" => "message",
        "static"  => "static-msg");

    # Auth check
    public static function check($permissions)
    {
        global $core;

        if ($core->blog && $core->auth->check($permissions, $core->blog->id)) {
            return;
        }

        if (session_id()) {
            $core->session->destroy();
        }
        http::redirect(DC_AUTH_PAGE);
    }

    # Check super admin
    public static function checkSuper()
    {
        global $core;

        if (!$core->auth->isSuperAdmin()) {
            if (session_id()) {
                $core->session->destroy();
            }
            http::redirect(DC_AUTH_PAGE);
        }
    }

    # Top of admin page
    public static function open($title = '', $head = '', $breadcrumb = '', $options = array())
    {
        global $core;

        # List of user's blogs
        if ($core->auth->getBlogCount() == 1 || $core->auth->getBlogCount() > 20) {
            $blog_box =
            '<p>' . __('Blog:') . ' <strong title="' . html::escapeHTML($core->blog->url) . '">' .
            html::escapeHTML($core->blog->name) . '</strong>';

            if ($core->auth->getBlogCount() > 20) {
                $blog_box .= ' - <a href="' . $core->adminurl->get("admin.blogs") . '">' . __('Change blog') . '</a>';
            }
            $blog_box .= '</p>';
        } else {
            $rs_blogs = $core->getBlogs(array('order' => 'LOWER(blog_name)', 'limit' => 20));
            $blogs    = array();
            while ($rs_blogs->fetch()) {
                $blogs[html::escapeHTML($rs_blogs->blog_name . ' - ' . $rs_blogs->blog_url)] = $rs_blogs->blog_id;
            }
            $blog_box =
            '<p><label for="switchblog" class="classic">' .
            __('Blogs:') . '</label> ' .
            $core->formNonce() .
            form::combo('switchblog', $blogs, $core->blog->id) .
            '<input type="submit" value="' . __('ok') . '" class="hidden-if-js" /></p>';
        }

        $safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

        # Display
        $headers = new ArrayObject(array());

        # Content-Type
        $headers['content-type'] = 'Content-Type: text/html; charset=UTF-8';

        # Referrer Policy for admin pages
        $headers['referrer'] = 'Referrer-Policy: strict-origin';

        # Prevents Clickjacking as far as possible
        if (isset($options['x-frame-allow'])) {
            self::setXFrameOptions($headers, $options['x-frame-allow']);
        } else {
            self::setXFrameOptions($headers);
        }

        # Content-Security-Policy (only if safe mode if not active, it may help)
        if (!$safe_mode && $core->blog->settings->system->csp_admin_on) {
            // Get directives from settings if exist, else set defaults
            $csp = new ArrayObject(array());

                                                                                // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
                                                                                // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
            $csp_prefix = $core->con->driver() == 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver
            $csp_suffix = $core->con->driver() == 'sqlite' ? ' 127.0.0.1' : ''; // Hack for SQlite Clearbricks driver

            $csp['default-src'] = $core->blog->settings->system->csp_admin_default ?:
            $csp_prefix . "'self'" . $csp_suffix;
            $csp['script-src'] = $core->blog->settings->system->csp_admin_script ?:
            $csp_prefix . "'self' 'unsafe-inline' 'unsafe-eval'" . $csp_suffix;
            $csp['style-src'] = $core->blog->settings->system->csp_admin_style ?:
            $csp_prefix . "'self' 'unsafe-inline'" . $csp_suffix;
            $csp['img-src'] = $core->blog->settings->system->csp_admin_img ?:
            $csp_prefix . "'self' data: http://media.dotaddict.org blob:";

            # Cope with blog post preview (via public URL in iframe)
            if (!is_null($core->blog->host)) {
                $csp['default-src'] .= ' ' . parse_url($core->blog->host, PHP_URL_HOST);
                $csp['script-src'] .= ' ' . parse_url($core->blog->host, PHP_URL_HOST);
                $csp['style-src'] .= ' ' . parse_url($core->blog->host, PHP_URL_HOST);
            }
            # Cope with media display in media manager (via public URL)
            if (!is_null($core->media)) {
                $csp['img-src'] .= ' ' . parse_url($core->media->root_url, PHP_URL_HOST);
            }
            # Allow everything in iframe (used by editors to preview public content)
            $csp['child-src'] = "*";

            # --BEHAVIOR-- adminPageHTTPHeaderCSP
            $core->callBehavior('adminPageHTTPHeaderCSP', $csp);

            // Construct CSP header
            $directives = array();
            foreach ($csp as $key => $value) {
                if ($value) {
                    $directives[] = $key . ' ' . $value;
                }
            }
            if (count($directives)) {
                if (version_compare(phpversion(), '5.4', '>=')) {
                    // csp_report.php needs PHP ≥ 5.4
                    $directives[] = "report-uri " . DC_ADMIN_URL . "csp_report.php";
                }
                $report_only    = ($core->blog->settings->system->csp_admin_report_only) ? '-Report-Only' : '';
                $headers['csp'] = "Content-Security-Policy" . $report_only . ": " . implode(" ; ", $directives);
            }
        }

        # --BEHAVIOR-- adminPageHTTPHeaders
        $core->callBehavior('adminPageHTTPHeaders', $headers);
        foreach ($headers as $key => $value) {
            header($value);
        }

        echo
        '<!DOCTYPE html>' .
        '<html lang="' . $core->auth->getInfo('user_lang') . '">' . "\n" .
        "<head>\n" .
        '  <meta charset="UTF-8" />' . "\n" .
        '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
        '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
        '  <title>' . $title . ' - ' . html::escapeHTML($core->blog->name) . ' - ' . html::escapeHTML(DC_VENDOR_NAME) . ' - ' . DC_VERSION . '</title>' . "\n";

        if ($core->auth->user_prefs->interface->darkmode) {
            echo self::cssLoad('style/default-dark.css');
        } else {
            echo self::cssLoad('style/default.css');
        }
        if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
            echo self::cssLoad('style/default-rtl.css');
        }

        $core->auth->user_prefs->addWorkspace('interface');
        if (!$core->auth->user_prefs->interface->hide_std_favicon) {
            echo
                '<link rel="icon" type="image/png" href="images/favicon96-login.png" />' . "\n" .
                '<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />' . "\n";
        }

        if ($core->auth->user_prefs->interface->htmlfontsize) {
            echo
            '<script type="text/javascript">' . "\n" .
            self::jsVar('dotclear_htmlFontSize', $core->auth->user_prefs->interface->htmlfontsize) . "\n" .
                "</script>\n";
        }

        echo
        self::jsCommon() .
        self::jsToggles() .
            $head;

        if ($core->auth->user_prefs->interface->hidemoreinfo) {
            echo
                '<script type="text/javascript">' . "\n" .
                'dotclear.hideMoreInfo = true;' . "\n" .
                "</script>\n";
        }
        if ($core->auth->user_prefs->interface->showajaxloader) {
            echo
                '<script type="text/javascript">' . "\n" .
                'dotclear.showAjaxLoader = true;' . "\n" .
                "</script>\n";
        }

        # --BEHAVIOR-- adminPageHTMLHead
        $core->callBehavior('adminPageHTMLHead');

        echo
        "</head>\n" .
        '<body id="dotclear-admin' .
        ($safe_mode ? ' safe-mode' : '') . '" class="no-js' .
        ($core->auth->user_prefs->interface->dynfontsize ? ' responsive-font' : '') . '">' . "\n" .

        '<ul id="prelude">' .
        '<li><a href="#content">' . __('Go to the content') . '</a></li>' .
        '<li><a href="#main-menu">' . __('Go to the menu') . '</a></li>' .
        '<li><a href="#qx">' . __('Go to search') . '</a></li>' .
        '<li><a href="#help">' . __('Go to help') . '</a></li>' .
        '</ul>' . "\n" .
        '<div id="header" role="banner">' .
        '<h1><a href="' . $core->adminurl->get("admin.home") . '"><span class="hidden">' . DC_VENDOR_NAME . '</span></a></h1>' . "\n";

        echo
        '<form action="' . $core->adminurl->get("admin.home") . '" method="post" id="top-info-blog">' .
        $blog_box .
        '<p><a href="' . $core->blog->url . '" class="outgoing" title="' . __('Go to site') .
        '">' . __('Go to site') . '<img src="images/outgoing-link.svg" alt="" /></a>' .
        '</p></form>' .
        '<ul id="top-info-user">' .
        '<li><a class="' . (preg_match('/' . preg_quote($core->adminurl->get('admin.home')) . '$/', $_SERVER['REQUEST_URI']) ? ' active' : '') . '" href="' . $core->adminurl->get("admin.home") . '">' . __('My dashboard') . '</a></li>' .
        '<li><a class="smallscreen' . (preg_match('/' . preg_quote($core->adminurl->get('admin.user.preferences')) . '(\?.*)?$/', $_SERVER['REQUEST_URI']) ? ' active' : '') .
        '" href="' . $core->adminurl->get("admin.user.preferences") . '">' . __('My preferences') . '</a></li>' .
        '<li><a href="' . $core->adminurl->get("admin.home", array('logout' => 1)) . '" class="logout"><span class="nomobile">' . sprintf(__('Logout %s'), $core->auth->userID()) .
            '</span><img src="images/logout.png" alt="" /></a></li>' .
            '</ul>' .
            '</div>'; // end header

        echo
        '<div id="wrapper" class="clearfix">' . "\n" .
        '<div class="hidden-if-no-js collapser-box"><button type="button" id="collapser" class="void-btn">' .
        '<img class="collapse-mm visually-hidden" src="images/collapser-hide.png" alt="' . __('Hide main menu') . '" />' .
        '<img class="expand-mm visually-hidden" src="images/collapser-show.png" alt="' . __('Show main menu') . '" />' .
            '</button></div>' .
            '<div id="main" role="main">' . "\n" .
            '<div id="content" class="clearfix">' . "\n";

        # Safe mode
        if ($safe_mode) {
            echo
            '<div class="warning" role="alert"><h3>' . __('Safe mode') . '</h3>' .
            '<p>' . __('You are in safe mode. All plugins have been temporarily disabled. Remind to log out then log in again normally to get back all functionalities') . '</p>' .
                '</div>';
        }

        // Display breadcrumb (if given) before any error messages
        echo $breadcrumb;

        // Display notices and errors
        echo self::notices();
    }

    public static function notices()
    {
        global $core;
        static $error_displayed = false;

        $res = '';

        // return error messages if any
        if ($core->error->flag() && !$error_displayed) {

            # --BEHAVIOR-- adminPageNotificationError
            $notice_error = $core->callBehavior('adminPageNotificationError', $core, $core->error);

            if (isset($notice_error) && !empty($notice_error)) {
                $res .= $notice_error;
            } else {
                $res .= '<div class="error"><p>' .
                '<strong>' . (count($core->error->getErrors()) > 1 ? __('Errors:') : __('Error:')) . '</strong>' .
                '</p>' . $core->error->toHTML() . '</div>';
            }
            $error_displayed = true;
        }

        // return notices if any
        if (isset($_SESSION['notifications'])) {
            foreach ($_SESSION['notifications'] as $notification) {

                # --BEHAVIOR-- adminPageNotification
                $notice = $core->callBehavior('adminPageNotification', $core, $notification);

                $res .= (isset($notice) && !empty($notice) ? $notice : self::getNotification($notification));
            }
            unset($_SESSION['notifications']);
        }
        return $res;
    }

    public static function addNotice($type, $message, $options = array())
    {
        if (isset(self::$N_TYPES[$type])) {
            $class = self::$N_TYPES[$type];
        } else {
            $class = $type;
        }
        if (isset($_SESSION['notifications']) && is_array($_SESSION['notifications'])) {
            $notifications = $_SESSION['notifications'];
        } else {
            $notifications = array();
        }

        $n = array_merge($options, array('class' => $class, 'ts' => time(), 'text' => $message));
        if ($type != "static") {
            $notifications[] = $n;
        } else {
            array_unshift($notifications, $n);
        }
        $_SESSION['notifications'] = $notifications;
    }

    public static function addSuccessNotice($message, $options = array())
    {
        self::addNotice("success", $message, $options);
    }

    public static function addWarningNotice($message, $options = array())
    {
        self::addNotice("warning", $message, $options);
    }

    public static function addErrorNotice($message, $options = array())
    {
        self::addNotice("error", $message, $options);
    }

    protected static function getNotification($n)
    {
        global $core;
        $tag = (isset($n['divtag']) && $n['divtag']) ? 'div' : 'p';
        $ts  = '';
        if (!isset($n['with_ts']) || ($n['with_ts'] == true)) {
            $ts = dt::str(__('[%H:%M:%S]'), $n['ts'], $core->auth->getInfo('user_tz')) . ' ';
        }
        $res = '<' . $tag . ' class="' . $n['class'] . '" role="alert">' . $ts . $n['text'] . '</' . $tag . '>';
        return $res;
    }

    public static function close()
    {
        global $core;

        if (!$GLOBALS['__resources']['ctxhelp']) {
            if (!$core->auth->user_prefs->interface->hidehelpbutton) {
                echo
                '<p id="help-button"><a href="' . $core->adminurl->get("admin.help") . '" class="outgoing" title="' .
                __('Global help') . '">' . __('Global help') . '</a></p>';
            }
        }

        $menu = &$GLOBALS['_menu'];

        echo
        "</div>\n" . // End of #content
        "</div>\n" . // End of #main

        '<div id="main-menu" role="navigation">' . "\n" .

        '<form id="search-menu" action="' . $core->adminurl->get("admin.search") . '" method="get" role="search">' .
        '<p><label for="qx" class="hidden">' . __('Search:') . ' </label>' . form::field('qx', 30, 255, '') .
        '<input type="submit" value="' . __('OK') . '" /></p>' .
            '</form>';

        foreach ($menu as $k => $v) {
            echo $menu[$k]->draw();
        }

        $text = sprintf(__('Thank you for using %s.'), 'Dotclear ' . DC_VERSION);

        # --BEHAVIOR-- adminPageFooter
        $textAlt = $core->callBehavior('adminPageFooter', $core, $text);
        if ($textAlt != '') {
            $text = $textAlt;
        }
        $text = html::escapeHTML($text);

        echo
        '</div>' . "\n" . // End of #main-menu
        "</div>\n";       // End of #wrapper

        echo '<p id="gototop"><a href="#wrapper">' . __('Page top') . '</a></p>' . "\n";

        $figure = "
  ♥‿♥
              |    |    |
             )_)  )_)  )_)
            )___))___))___)\
           )____)____)_____)\\
         _____|____|____|____\\\__
---------\                   /---------
  ^^^^^ ^^^^^^^^^^^^^^^^^^^^^
    ^^^^      ^^^^     ^^^    ^^
         ^^^^      ^^^
  ";

        echo
            '<div id="footer" role="contentinfo">' .
            '<a href="http://dotclear.org/" title="' . $text . '">' .
            '<img src="style/dc_logos/w-dotclear90.png" alt="' . $text . '" /></a></div>' . "\n" .
            "<!-- " . "\n" .
            $figure .
            " -->" . "\n";

        if (defined('DC_DEV') && DC_DEV === true) {
            echo self::debugInfo();
        }

        echo
            '</body></html>';
    }

    public static function openPopup($title = '', $head = '', $breadcrumb = '')
    {
        global $core;

        # Display
        header('Content-Type: text/html; charset=UTF-8');

        # Referrer Policy for admin pages
        header('Referrer-Policy: strict-origin');

        # Prevents Clickjacking as far as possible
        header('X-Frame-Options: SAMEORIGIN'); // FF 3.6.9+ Chrome 4.1+ IE 8+ Safari 4+ Opera 10.5+

        echo
        '<!DOCTYPE html>' .
        '<html lang="' . $core->auth->getInfo('user_lang') . '">' . "\n" .
        "<head>\n" .
        '  <meta charset="UTF-8" />' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
        '  <title>' . $title . ' - ' . html::escapeHTML($core->blog->name) . ' - ' . html::escapeHTML(DC_VENDOR_NAME) . ' - ' . DC_VERSION . '</title>' . "\n" .
        '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
        '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n";

        if ($core->auth->user_prefs->interface->darkmode) {
            echo self::cssLoad('style/default-dark.css');
        } else {
            echo self::cssLoad('style/default.css');
        }
        if (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl') {
            echo self::cssLoad('style/default-rtl.css');
        }

        $core->auth->user_prefs->addWorkspace('interface');
        if ($core->auth->user_prefs->interface->htmlfontsize) {
            echo
            '<script type="text/javascript">' . "\n" .
            self::jsVar('dotclear_htmlFontSize', $core->auth->user_prefs->interface->htmlfontsize) . "\n" .
                "</script>\n";
        }

        echo
        self::jsCommon() .
        self::jsToggles() .
            $head;

        if ($core->auth->user_prefs->interface->hidemoreinfo) {
            echo
                '<script type="text/javascript">' . "\n" .
                'dotclear.hideMoreInfo = true;' . "\n" .
                "</script>\n";
        }
        if ($core->auth->user_prefs->interface->showajaxloader) {
            echo
                '<script type="text/javascript">' . "\n" .
                'dotclear.showAjaxLoader = true;' . "\n" .
                "</script>\n";
        }

        # --BEHAVIOR-- adminPageHTMLHead
        $core->callBehavior('adminPageHTMLHead');

        echo
            "</head>\n" .
            '<body id="dotclear-admin" class="popup' .
            ($core->auth->user_prefs->interface->dynfontsize ? ' responsive-font' : '') . '">' . "\n" .

            '<h1>' . DC_VENDOR_NAME . '</h1>' . "\n";

        echo
            '<div id="wrapper">' . "\n" .
            '<div id="main" role="main">' . "\n" .
            '<div id="content">' . "\n";

        // display breadcrumb if given
        echo $breadcrumb;

        // Display notices and errors
        echo self::notices();
    }

    public static function closePopup()
    {
        echo
        "</div>\n" . // End of #content
        "</div>\n" . // End of #main
        "</div>\n" . // End of #wrapper

        '<p id="gototop"><a href="#wrapper">' . __('Page top') . '</a></p>' . "\n" .

            '<div id="footer" role="contentinfo"><p>&nbsp;</p></div>' . "\n" .
            '</body></html>';
    }

    public static function breadcrumb($elements = null, $options = array())
    {
        global $core;
        $with_home_link = isset($options['home_link']) ? $options['home_link'] : true;
        $hl             = isset($options['hl']) ? $options['hl'] : true;
        $hl_pos         = isset($options['hl_pos']) ? $options['hl_pos'] : -1;
        // First item of array elements should be blog's name, System or Plugins
        $res = '<h2>' . ($with_home_link ?
            '<a class="go_home" href="' . $core->adminurl->get("admin.home") . '"><img src="style/dashboard.png" alt="' . __('Go to dashboard') . '" /></a>' :
            '<img src="style/dashboard-alt.png" alt="" />');
        $index = 0;
        if ($hl_pos < 0) {
            $hl_pos = count($elements) + $hl_pos;
        }
        foreach ($elements as $element => $url) {
            if ($hl && $index == $hl_pos) {
                $element = sprintf('<span class="page-title">%s</span>', $element);
            }
            $res .= ($with_home_link ? ($index == 1 ? ' : ' : ' &rsaquo; ') : ($index == 0 ? ' ' : ' &rsaquo; ')) .
                ($url ? '<a href="' . $url . '">' : '') . $element . ($url ? '</a>' : '');
            $index++;
        }
        $res .= '</h2>';
        return $res;
    }

    public static function message($msg, $timestamp = true, $div = false, $echo = true, $class = 'message')
    {
        global $core;

        $res = '';
        if ($msg != '') {
            $res = ($div ? '<div class="' . $class . '">' : '') . '<p' . ($div ? '' : ' class="' . $class . '"') . '>' .
                ($timestamp ? dt::str(__('[%H:%M:%S]'), null, $core->auth->getInfo('user_tz')) . ' ' : '') . $msg .
                '</p>' . ($div ? '</div>' : '');
            if ($echo) {
                echo $res;
            }
        }
        return $res;
    }

    public static function success($msg, $timestamp = true, $div = false, $echo = true)
    {
        return self::message($msg, $timestamp, $div, $echo, "success");
    }

    public static function warning($msg, $timestamp = true, $div = false, $echo = true)
    {
        return self::message($msg, $timestamp, $div, $echo, "warning-msg");
    }

    private static function debugInfo()
    {
        $global_vars = implode(', ', array_keys($GLOBALS));

        $res =
        '<div id="debug"><div>' .
        '<p>memory usage: ' . memory_get_usage() . ' (' . files::size(memory_get_usage()) . ')</p>';

        if (function_exists('xdebug_get_profiler_filename')) {
            $res .= '<p>Elapsed time: ' . xdebug_time_index() . ' seconds</p>';

            $prof_file = xdebug_get_profiler_filename();
            if ($prof_file) {
                $res .= '<p>Profiler file : ' . xdebug_get_profiler_filename() . '</p>';
            } else {
                $prof_url = http::getSelfURI();
                $prof_url .= (strpos($prof_url, '?') === false) ? '?' : '&';
                $prof_url .= 'XDEBUG_PROFILE';
                $res .= '<p><a href="' . html::escapeURL($prof_url) . '">Trigger profiler</a></p>';
            }

            /* xdebug configuration:
        zend_extension = /.../xdebug.so
        xdebug.auto_trace = On
        xdebug.trace_format = 0
        xdebug.trace_options = 1
        xdebug.show_mem_delta = On
        xdebug.profiler_enable = 0
        xdebug.profiler_enable_trigger = 1
        xdebug.profiler_output_dir = /tmp
        xdebug.profiler_append = 0
        xdebug.profiler_output_name = timestamp
         */
        }

        $res .=
            '<p>Global vars: ' . $global_vars . '</p>' .
            '</div></div>';

        return $res;
    }

    public static function help($page, $index = '')
    {
        # Deprecated but we keep this for plugins.
    }

    public static function helpBlock()
    {
        global $core;

        if ($core->auth->user_prefs->interface->hidehelpbutton) {
            return;
        }

        $args = func_get_args();
        $args = new ArrayObject($args);

        # --BEHAVIOR-- adminPageHelpBlock
        $GLOBALS['core']->callBehavior('adminPageHelpBlock', $args);

        if (empty($args)) {
            return;
        };

        global $__resources;
        if (empty($__resources['help'])) {
            return;
        }

        $content = '';
        foreach ($args as $v) {
            if (is_object($v) && isset($v->content)) {
                $content .= $v->content;
                continue;
            }

            if (!isset($__resources['help'][$v])) {
                continue;
            }
            $f = $__resources['help'][$v];
            if (!file_exists($f) || !is_readable($f)) {
                continue;
            }

            $fc = file_get_contents($f);
            if (preg_match('|<body[^>]*?>(.*?)</body>|ms', $fc, $matches)) {
                $content .= $matches[1];
            } else {
                $content .= $fc;
            }
        }

        if (trim($content) == '') {
            return;
        }

        // Set contextual help global flag
        $GLOBALS['__resources']['ctxhelp'] = true;

        echo
        '<div id="help"><hr /><div class="help-content clear"><h3>' . __('Help about this page') . '</h3>' .
        $content .
        '</div>' .
        '<div id="helplink"><hr />' .
        '<p>' .
        sprintf(__('See also %s'), sprintf('<a href="' . $core->adminurl->get("admin.help") . '">%s</a>', __('the global help'))) .
            '.</p>' .
            '</div></div>';
    }

    public static function cssLoad($src, $media = 'screen', $v = '')
    {
        $escaped_src = html::escapeHTML($src);
        if (!isset(self::$loaded_css[$escaped_src])) {
            self::$loaded_css[$escaped_src] = true;
            $escaped_src                    = self::appendVersion($escaped_src, $v);

            return '<link rel="stylesheet" href="' . $escaped_src . '" type="text/css" media="' . $media . '" />' . "\n";
        }
    }

    public static function jsLoad($src, $v = '')
    {
        $escaped_src = html::escapeHTML($src);
        if (!isset(self::$loaded_js[$escaped_src])) {
            self::$loaded_js[$escaped_src] = true;
            $escaped_src                   = self::appendVersion($escaped_src, $v);
            return '<script type="text/javascript" src="' . $escaped_src . '"></script>' . "\n";
        }
    }

    private static function appendVersion($src, $v = '')
    {
        $src .= (strpos($src, '?') === false ? '?' : '&amp;') . 'v=';
        if (defined('DC_DEV') && DC_DEV === true) {
            $src .= md5(uniqid());
        } else {
            $src .= ($v === '' ? DC_VERSION : $v);
        }
        return $src;
    }

    public static function jsVar($n, $v)
    {
        return $n . " = '" . html::escapeJS($v) . "';\n";
    }

    public static function jsVars($vars)
    {
        $ret = '<script type="text/javascript">' . "\n";
        foreach ($vars as $var => $value) {
            $ret .= $var . ' = ' . (is_string($value) ? "'" . html::escapeJS($value) . "'" : $value) . ';' . "\n";
        }
        $ret .= "</script>\n";

        return $ret;
    }

    public static function jsToggles()
    {
        if ($GLOBALS['core']->auth->user_prefs->toggles) {
            $unfolded_sections = explode(',', $GLOBALS['core']->auth->user_prefs->toggles->unfolded_sections);
            foreach ($unfolded_sections as $k => &$v) {
                if ($v == '') {
                    unset($unfolded_sections[$k]);
                } else {
                    $v = "'" . html::escapeJS($v) . "':true";
                }
            }
        } else {
            $unfolded_sections = array();
        }
        return '<script type="text/javascript">' . "\n" .
        'dotclear.unfolded_sections = {' . join(",", $unfolded_sections) . "};\n" .
            "</script>\n";
    }

    public static function jsCommon()
    {
        $mute_or_no = '';
        if (empty($GLOBALS['core']->blog) || $GLOBALS['core']->blog->settings->system->jquery_migrate_mute) {
            $mute_or_no .=
                '<script type="text/javascript">' . "\n" .
                'jQuery.migrateMute = true;' . "\n" .
                "</script>\n";
        }

        return
        self::jsLoad('js/jquery/jquery.js') .
        $mute_or_no .
        self::jsLoad('js/jquery/jquery-migrate.js') .
        self::jsLoad('js/jquery/jquery.biscuit.js') .
        self::jsLoad('js/common.js') .
        self::jsLoad('js/prelude.js') .

        '<script type="text/javascript">' . "\n" .
        'jsToolBar = {}, jsToolBar.prototype = { elements : {} };' . "\n" .

        self::jsVar('dotclear.nonce', $GLOBALS['core']->getNonce()) .

        self::jsVar('dotclear.img_plus_src', 'images/expand.png') .
        self::jsVar('dotclear.img_plus_txt', '►') .
        self::jsVar('dotclear.img_plus_alt', __('uncover')) .
        self::jsVar('dotclear.img_minus_src', 'images/hide.png') .
        self::jsVar('dotclear.img_minus_txt', '▼') .
        self::jsVar('dotclear.img_minus_alt', __('hide')) .
        self::jsVar('dotclear.img_menu_on', 'images/menu_on.png') .
        self::jsVar('dotclear.img_menu_off', 'images/menu_off.png') .

        self::jsVar('dotclear.img_plus_theme_src', 'images/plus-theme.png') .
        self::jsVar('dotclear.img_plus_theme_txt', '►') .
        self::jsVar('dotclear.img_plus_theme_alt', __('uncover')) .
        self::jsVar('dotclear.img_minus_theme_src', 'images/minus-theme.png') .
        self::jsVar('dotclear.img_minus_theme_txt', '▼') .
        self::jsVar('dotclear.img_minus_theme_alt', __('hide')) .

        self::jsVar('dotclear.msg.help',
            __('Need help?')) .
        self::jsVar('dotclear.msg.new_window',
            __('new window')) .
        self::jsVar('dotclear.msg.help_hide',
            __('Hide')) .
        self::jsVar('dotclear.msg.to_select',
            __('Select:')) .
        self::jsVar('dotclear.msg.no_selection',
            __('no selection')) .
        self::jsVar('dotclear.msg.select_all',
            __('select all')) .
        self::jsVar('dotclear.msg.invert_sel',
            __('Invert selection')) .
        self::jsVar('dotclear.msg.website',
            __('Web site:')) .
        self::jsVar('dotclear.msg.email',
            __('Email:')) .
        self::jsVar('dotclear.msg.ip_address',
            __('IP address:')) .
        self::jsVar('dotclear.msg.error',
            __('Error:')) .
        self::jsVar('dotclear.msg.entry_created',
            __('Entry has been successfully created.')) .
        self::jsVar('dotclear.msg.edit_entry',
            __('Edit entry')) .
        self::jsVar('dotclear.msg.view_entry',
            __('view entry')) .
        self::jsVar('dotclear.msg.confirm_delete_posts',
            __("Are you sure you want to delete selected entries (%s)?")) .
        self::jsVar('dotclear.msg.confirm_delete_medias',
            __("Are you sure you want to delete selected medias (%d)?")) .
        self::jsVar('dotclear.msg.confirm_delete_categories',
            __("Are you sure you want to delete selected categories (%s)?")) .
        self::jsVar('dotclear.msg.confirm_delete_post',
            __("Are you sure you want to delete this entry?")) .
        self::jsVar('dotclear.msg.click_to_unlock',
            __("Click here to unlock the field")) .
        self::jsVar('dotclear.msg.confirm_spam_delete',
            __('Are you sure you want to delete all spams?')) .
        self::jsVar('dotclear.msg.confirm_delete_comments',
            __('Are you sure you want to delete selected comments (%s)?')) .
        self::jsVar('dotclear.msg.confirm_delete_comment',
            __('Are you sure you want to delete this comment?')) .
        self::jsVar('dotclear.msg.cannot_delete_users',
            __('Users with posts cannot be deleted.')) .
        self::jsVar('dotclear.msg.confirm_delete_user',
            __('Are you sure you want to delete selected users (%s)?')) .
        self::jsVar('dotclear.msg.confirm_delete_blog',
            __('Are you sure you want to delete selected blogs (%s)?')) .
        self::jsVar('dotclear.msg.confirm_delete_category',
            __('Are you sure you want to delete category "%s"?')) .
        self::jsVar('dotclear.msg.confirm_reorder_categories',
            __('Are you sure you want to reorder all categories?')) .
        self::jsVar('dotclear.msg.confirm_delete_media',
            __('Are you sure you want to remove media "%s"?')) .
        self::jsVar('dotclear.msg.confirm_delete_directory',
            __('Are you sure you want to remove directory "%s"?')) .
        self::jsVar('dotclear.msg.confirm_extract_current',
            __('Are you sure you want to extract archive in current directory?')) .
        self::jsVar('dotclear.msg.confirm_remove_attachment',
            __('Are you sure you want to remove attachment "%s"?')) .
        self::jsVar('dotclear.msg.confirm_delete_lang',
            __('Are you sure you want to delete "%s" language?')) .
        self::jsVar('dotclear.msg.confirm_delete_plugin',
            __('Are you sure you want to delete "%s" plugin?')) .
        self::jsVar('dotclear.msg.confirm_delete_plugins',
            __('Are you sure you want to delete selected plugins?')) .
        self::jsVar('dotclear.msg.use_this_theme',
            __('Use this theme')) .
        self::jsVar('dotclear.msg.remove_this_theme',
            __('Remove this theme')) .
        self::jsVar('dotclear.msg.confirm_delete_theme',
            __('Are you sure you want to delete "%s" theme?')) .
        self::jsVar('dotclear.msg.confirm_delete_themes',
            __('Are you sure you want to delete selected themes?')) .
        self::jsVar('dotclear.msg.confirm_delete_backup',
            __('Are you sure you want to delete this backup?')) .
        self::jsVar('dotclear.msg.confirm_revert_backup',
            __('Are you sure you want to revert to this backup?')) .
        self::jsVar('dotclear.msg.zip_file_content',
            __('Zip file content')) .
        self::jsVar('dotclear.msg.xhtml_validator',
            __('XHTML markup validator')) .
        self::jsVar('dotclear.msg.xhtml_valid',
            __('XHTML content is valid.')) .
        self::jsVar('dotclear.msg.xhtml_not_valid',
            __('There are XHTML markup errors.')) .
        self::jsVar('dotclear.msg.warning_validate_no_save_content',
            __('Attention: an audit of a content not yet registered.')) .
        self::jsVar('dotclear.msg.confirm_change_post_format',
            __('You have unsaved changes. Switch post format will loose these changes. Proceed anyway?')) .
        self::jsVar('dotclear.msg.confirm_change_post_format_noconvert',
            __("Warning: post format change will not convert existing content. You will need to apply new format by yourself. Proceed anyway?")) .
        self::jsVar('dotclear.msg.load_enhanced_uploader',
            __('Loading enhanced uploader, please wait.')) .

        self::jsVar('dotclear.msg.module_author',
            __('Author:')) .
        self::jsVar('dotclear.msg.module_details',
            __('Details')) .
        self::jsVar('dotclear.msg.module_support',
            __('Support')) .
        self::jsVar('dotclear.msg.module_help',
            __('Help:')) .
        self::jsVar('dotclear.msg.module_section',
            __('Section:')) .
        self::jsVar('dotclear.msg.module_tags',
            __('Tags:')) .

        self::jsVar('dotclear.msg.close_notice',
            __('Hide this notice')) .

            "\n" .
            "</script>\n";
    }

    /**
    @deprecated since version 2.11
     */
    public static function jsLoadIE7()
    {
        return '';
    }

    public static function jsConfirmClose()
    {
        $args = func_get_args();
        if (count($args) > 0) {
            foreach ($args as $k => $v) {
                $args[$k] = "'" . html::escapeJS($v) . "'";
            }
            $args = implode(',', $args);
        } else {
            $args = '';
        }

        return
        self::jsLoad('js/confirm-close.js') .
        '<script type="text/javascript">' . "\n" .
        "confirmClosePage = new confirmClose(" . $args . "); " .
        "confirmClose.prototype.prompt = '" . html::escapeJS(__('You have unsaved changes.')) . "'; " .
            "</script>\n";
    }

    public static function jsPageTabs($default = null)
    {
        if ($default) {
            $default = "'" . html::escapeJS($default) . "'";
        }

        return
        self::jsLoad('js/jquery/jquery.pageTabs.js') .
            '<script type="text/javascript">' . "\n" .
            '$(function() {' . "\n" .
            '   $.pageTabs(' . $default . ');' . "\n" .
            '});' .
            "</script>\n";
    }

    public static function jsModal()
    {
        return
        self::jsLoad('js/jquery/jquery.magnific-popup.js');
    }

    public static function jsColorPicker()
    {
        return
        self::cssLoad('style/farbtastic/farbtastic.css') .
        self::jsLoad('js/jquery/jquery.farbtastic.js') .
        self::jsLoad('js/color-picker.js');
    }

    public static function jsDatePicker()
    {
        return
        self::cssLoad('style/date-picker.css') .
        self::jsLoad('js/date-picker.js') .
        '<script type="text/javascript">' . "\n" .

        "datePicker.prototype.months[0] = '" . html::escapeJS(__('January')) . "'; " .
        "datePicker.prototype.months[1] = '" . html::escapeJS(__('February')) . "'; " .
        "datePicker.prototype.months[2] = '" . html::escapeJS(__('March')) . "'; " .
        "datePicker.prototype.months[3] = '" . html::escapeJS(__('April')) . "'; " .
        "datePicker.prototype.months[4] = '" . html::escapeJS(__('May')) . "'; " .
        "datePicker.prototype.months[5] = '" . html::escapeJS(__('June')) . "'; " .
        "datePicker.prototype.months[6] = '" . html::escapeJS(__('July')) . "'; " .
        "datePicker.prototype.months[7] = '" . html::escapeJS(__('August')) . "'; " .
        "datePicker.prototype.months[8] = '" . html::escapeJS(__('September')) . "'; " .
        "datePicker.prototype.months[9] = '" . html::escapeJS(__('October')) . "'; " .
        "datePicker.prototype.months[10] = '" . html::escapeJS(__('November')) . "'; " .
        "datePicker.prototype.months[11] = '" . html::escapeJS(__('December')) . "'; " .

        "datePicker.prototype.days[0] = '" . html::escapeJS(__('Monday')) . "'; " .
        "datePicker.prototype.days[1] = '" . html::escapeJS(__('Tuesday')) . "'; " .
        "datePicker.prototype.days[2] = '" . html::escapeJS(__('Wednesday')) . "'; " .
        "datePicker.prototype.days[3] = '" . html::escapeJS(__('Thursday')) . "'; " .
        "datePicker.prototype.days[4] = '" . html::escapeJS(__('Friday')) . "'; " .
        "datePicker.prototype.days[5] = '" . html::escapeJS(__('Saturday')) . "'; " .
        "datePicker.prototype.days[6] = '" . html::escapeJS(__('Sunday')) . "'; " .

        "datePicker.prototype.img_src = 'images/date-picker.png'; " .
        "datePicker.prototype.img_alt = '" . html::escapeJS(__('Choose date')) . "'; " .

        "datePicker.prototype.close_msg = '" . html::escapeJS(__('close')) . "'; " .
        "datePicker.prototype.now_msg = '" . html::escapeJS(__('now')) . "'; " .

            "</script>\n";
    }

    public static function jsToolBar()
    {
        # Deprecated but we keep this for plugins.
    }

    public static function jsUpload($params = array(), $base_url = null)
    {
        if (!$base_url) {
            $base_url = path::clean(dirname(preg_replace('/(\?.*$)?/', '', $_SERVER['REQUEST_URI']))) . '/';
        }

        $params = array_merge($params, array(
            'sess_id=' . session_id(),
            'sess_uid=' . $_SESSION['sess_browser_uid'],
            'xd_check=' . $GLOBALS['core']->getNonce()
        ));

        return
        '<script type="text/javascript">' . "\n" .
        "dotclear.jsUpload = {};\n" .
        "dotclear.jsUpload.msg = {};\n" .
        self::jsVar('dotclear.msg.enhanced_uploader_activate', __('Temporarily activate enhanced uploader')) .
        self::jsVar('dotclear.msg.enhanced_uploader_disable', __('Temporarily disable enhanced uploader')) .
        self::jsVar('dotclear.jsUpload.msg.limit_exceeded', __('Limit exceeded.')) .
        self::jsVar('dotclear.jsUpload.msg.size_limit_exceeded', __('File size exceeds allowed limit.')) .
        self::jsVar('dotclear.jsUpload.msg.canceled', __('Canceled.')) .
        self::jsVar('dotclear.jsUpload.msg.http_error', __('HTTP Error:')) .
        self::jsVar('dotclear.jsUpload.msg.error', __('Error:')) .
        self::jsVar('dotclear.jsUpload.msg.choose_file', __('Choose file')) .
        self::jsVar('dotclear.jsUpload.msg.choose_files', __('Choose files')) .
        self::jsVar('dotclear.jsUpload.msg.cancel', __('Cancel')) .
        self::jsVar('dotclear.jsUpload.msg.clean', __('Clean')) .
        self::jsVar('dotclear.jsUpload.msg.upload', __('Upload')) .
        self::jsVar('dotclear.jsUpload.msg.send', __('Send')) .
        self::jsVar('dotclear.jsUpload.msg.file_successfully_uploaded', __('File successfully uploaded.')) .
        self::jsVar('dotclear.jsUpload.msg.no_file_in_queue', __('No file in queue.')) .
        self::jsVar('dotclear.jsUpload.msg.file_in_queue', __('1 file in queue.')) .
        self::jsVar('dotclear.jsUpload.msg.files_in_queue', __('%d files in queue.')) .
        self::jsVar('dotclear.jsUpload.msg.queue_error', __('Queue error:')) .
        self::jsVar('dotclear.jsUpload.base_url', $base_url) .
        "</script>\n" .

        self::jsLoad('js/jquery/jquery-ui.custom.js') .
        self::jsLoad('js/jsUpload/tmpl.js') .
        self::jsLoad('js/jsUpload/template-upload.js') .
        self::jsLoad('js/jsUpload/template-download.js') .
        self::jsLoad('js/jsUpload/load-image.js') .
        self::jsLoad('js/jsUpload/jquery.iframe-transport.js') .
        self::jsLoad('js/jsUpload/jquery.fileupload.js') .
        self::jsLoad('js/jsUpload/jquery.fileupload-process.js') .
        self::jsLoad('js/jsUpload/jquery.fileupload-resize.js') .
        self::jsLoad('js/jsUpload/jquery.fileupload-ui.js');
    }

    public static function jsMetaEditor()
    {
        return self::jsLoad('js/meta-editor.js');
    }

    public static function jsFilterControl($show = true)
    {
        return
        self::jsLoad('js/filter-controls.js') .
        '<script type="text/javascript">' . "\n" .
        self::jsVar('dotclear.msg.show_filters', $show ? 'true' : 'false') . "\n" .
        self::jsVar('dotclear.msg.filter_posts_list', __('Show filters and display options')) . "\n" .
        self::jsVar('dotclear.msg.cancel_the_filter', __('Cancel filters and display options')) . "\n" .
            "</script>";
    }

    public static function jsLoadCodeMirror($theme = '', $multi = true, $modes = array('css', 'htmlmixed', 'javascript', 'php', 'xml'))
    {
        $ret =
        self::cssLoad('js/codemirror/lib/codemirror.css') .
        self::jsLoad('js/codemirror/lib/codemirror.js');
        if ($multi) {
            $ret .= self::jsLoad('js/codemirror/addon/mode/multiplex.js');
        }
        foreach ($modes as $mode) {
            $ret .= self::jsLoad('js/codemirror/mode/' . $mode . '/' . $mode . '.js');
        }
        $ret .=
        self::jsLoad('js/codemirror/addon/edit/closebrackets.js') .
        self::jsLoad('js/codemirror/addon/edit/matchbrackets.js') .
        self::cssLoad('js/codemirror/addon/display/fullscreen.css') .
        self::jsLoad('js/codemirror/addon/display/fullscreen.js');
        if ($theme != '') {
            $ret .= self::cssLoad('js/codemirror/theme/' . $theme . '.css');
        }
        return $ret;
    }

    public static function jsRunCodeMirror($name, $id, $mode, $theme = '')
    {
        $ret =
            '<script type="text/javascript">' . "\n" .
            'var ' . $name . ' = CodeMirror.fromTextArea(' . $id . ',{' . "\n" .
            '   mode: "' . $mode . '",' . "\n" .
            '   tabMode: "indent",' . "\n" .
            '   lineWrapping: 1,' . "\n" .
            '   lineNumbers: 1,' . "\n" .
            '   matchBrackets: 1,' . "\n" .
            '   autoCloseBrackets: 1,' . "\n" .
            '   extraKeys: {"F11": function(cm) {cm.setOption("fullScreen",!cm.getOption("fullScreen"));}}';
        if ($theme) {
            $ret .=
                ',' . "\n" .
                '   theme: "' . $theme . '"';
        }
        $ret .= "\n" .
            '});' . "\n" .
            '</script>';
        return $ret;
    }

    public static function getCodeMirrorThemes()
    {
        $themes      = array();
        $themes_root = dirname(__FILE__) . '/../../admin' . '/js/codemirror/theme/';
        if (is_dir($themes_root) && is_readable($themes_root)) {
            if (($d = @dir($themes_root)) !== false) {
                while (($entry = $d->read()) !== false) {
                    if ($entry != '.' && $entry != '..' && substr($entry, 0, 1) != '.' && is_readable($themes_root . '/' . $entry)) {
                        $themes[] = substr($entry, 0, -4); // remove .css extension
                    }
                }
                sort($themes);
            }
        }
        return $themes;
    }

    public static function getPF($file)
    {
        return $GLOBALS['core']->adminurl->get('load.plugin.file', array('pf' => $file));
    }

    public static function getVF($file)
    {
        return $GLOBALS['core']->adminurl->get('load.var.file', array('vf' => $file));
    }

    public static function setXFrameOptions($headers, $origin = null)
    {
        if (self::$xframe_loaded) {
            return;
        }

        if ($origin !== null) {
            $url                        = parse_url($origin);
            $headers['x-frame-options'] = sprintf('X-Frame-Options: %s', is_array($url) && isset($url['host']) ?
                ("ALLOW-FROM " . (isset($url['scheme']) ? $url['scheme'] . ':' : '') . '//' . $url['host']) :
                'SAMEORIGIN');
        } else {
            $headers['x-frame-options'] = 'X-Frame-Options: SAMEORIGIN'; // FF 3.6.9+ Chrome 4.1+ IE 8+ Safari 4+ Opera 10.5+
        }
        self::$xframe_loaded = true;
    }
}
