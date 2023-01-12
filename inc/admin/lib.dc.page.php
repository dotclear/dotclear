<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcPage
{
    /**
     * Stack of loaded JS
     */
    private static array $loaded_js = [];

    /**
     * Stack of loaded CSS
     */
    private static array $loaded_css = [];

    /**
     * Stack of preloaded resources (Js, CSS)
     */
    private static array $preloaded = [];

    /**
     * Flag to avoid loading more than once the x-frame-options header
     */
    private static bool $xframe_loaded = false;

    /**
     * Auth check
     *
     * @param      string  $permissions  The permissions
     * @param      bool    $home         Currently on dashboard
     */
    public static function check(string $permissions, bool $home = false)
    {
        if (dcCore::app()->blog && dcCore::app()->auth->check($permissions, dcCore::app()->blog->id)) {
            return;
        }

        // Check if dashboard is not the current page et if it is granted for the user
        if (!$home && dcCore::app()->blog && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            // Go back to the dashboard
            http::redirect(DC_ADMIN_URL);
        }

        if (session_id()) {
            dcCore::app()->session->destroy();
        }
        http::redirect(dcCore::app()->adminurl->get('admin.auth'));
    }

    /**
     * Check super admin
     *
     * @param      bool  $home   The home
     */
    public static function checkSuper(bool $home = false)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            // Check if dashboard is not the current page et if it is granted for the user
            if (!$home && dcCore::app()->blog && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)) {
                // Go back to the dashboard
                http::redirect(DC_ADMIN_URL);
            }

            if (session_id()) {
                dcCore::app()->session->destroy();
            }
            http::redirect(dcCore::app()->adminurl->get('admin.auth'));
        }
    }

    /**
     * Top of admin page
     *
     * @param      string  $title       The title
     * @param      string  $head        The head
     * @param      string  $breadcrumb  The breadcrumb
     * @param      array   $options     The options
     */
    public static function open(string $title = '', string $head = '', string $breadcrumb = '', array $options = [])
    {
        $js = [];

        # List of user's blogs
        if (dcCore::app()->auth->getBlogCount() == 1 || dcCore::app()->auth->getBlogCount() > 20) {
            $blog_box = '<p>' . __('Blog:') . ' <strong title="' . html::escapeHTML(dcCore::app()->blog->url) . '">' .
            html::escapeHTML(dcCore::app()->blog->name) . '</strong>';

            if (dcCore::app()->auth->getBlogCount() > 20) {
                $blog_box .= ' - <a href="' . dcCore::app()->adminurl->get('admin.blogs') . '">' . __('Change blog') . '</a>';
            }
            $blog_box .= '</p>';
        } else {
            $rs_blogs = dcCore::app()->getBlogs(['order' => 'LOWER(blog_name)', 'limit' => 20]);
            $blogs    = [];
            while ($rs_blogs->fetch()) {
                $blogs[html::escapeHTML($rs_blogs->blog_name . ' - ' . $rs_blogs->blog_url)] = $rs_blogs->blog_id;
            }
            $blog_box = '<p><label for="switchblog" class="classic">' . __('Blogs:') . '</label> ' .
            dcCore::app()->formNonce() . form::combo('switchblog', $blogs, dcCore::app()->blog->id) .
            form::hidden(['redir'], $_SERVER['REQUEST_URI']) .
            '<input type="submit" value="' . __('ok') . '" class="hidden-if-js" /></p>';
        }

        $safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

        # Display
        $headers = new ArrayObject([]);

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
        if (!$safe_mode && dcCore::app()->blog->settings->system->csp_admin_on) {
            // Get directives from settings if exist, else set defaults
            $csp = new ArrayObject([]);

            // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
                                                                                // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
            $csp_prefix = dcCore::app()->con->syntax() == 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks syntax
            $csp_suffix = dcCore::app()->con->syntax() == 'sqlite' ? ' 127.0.0.1' : ''; // Hack for SQlite Clearbricks syntax

            $csp['default-src'] = dcCore::app()->blog->settings->system->csp_admin_default ?:
            $csp_prefix . "'self'" . $csp_suffix;
            $csp['script-src'] = dcCore::app()->blog->settings->system->csp_admin_script ?:
            $csp_prefix . "'self' 'unsafe-eval'" . $csp_suffix;
            $csp['style-src'] = dcCore::app()->blog->settings->system->csp_admin_style ?:
            $csp_prefix . "'self' 'unsafe-inline'" . $csp_suffix;
            $csp['img-src'] = dcCore::app()->blog->settings->system->csp_admin_img ?:
            $csp_prefix . "'self' data: https://media.dotaddict.org blob:";

            # Cope with blog post preview (via public URL in iframe)
            if (dcCore::app()->blog->host) {
                $csp['default-src'] .= ' ' . parse_url(dcCore::app()->blog->host, PHP_URL_HOST);
                $csp['script-src']  .= ' ' . parse_url(dcCore::app()->blog->host, PHP_URL_HOST);
                $csp['style-src']   .= ' ' . parse_url(dcCore::app()->blog->host, PHP_URL_HOST);
            }
            # Cope with media display in media manager (via public URL)
            if (!is_null(dcCore::app()->media)) {
                $csp['img-src'] .= ' ' . parse_url(dcCore::app()->media->root_url, PHP_URL_HOST);
            } elseif (!is_null(dcCore::app()->blog->host)) {
                // Let's try with the blog URL
                $csp['img-src'] .= ' ' . parse_url(dcCore::app()->blog->host, PHP_URL_HOST);
            }
            # Allow everything in iframe (used by editors to preview public content)
            $csp['frame-src'] = '*';

            # --BEHAVIOR-- adminPageHTTPHeaderCSP
            dcCore::app()->callBehavior('adminPageHTTPHeaderCSP', $csp);

            // Construct CSP header
            $directives = [];
            foreach ($csp as $key => $value) {
                if ($value) {
                    $directives[] = $key . ' ' . $value;
                }
            }
            if (count($directives)) {
                $directives[]   = 'report-uri ' . DC_ADMIN_URL . 'csp_report.php';
                $report_only    = (dcCore::app()->blog->settings->system->csp_admin_report_only) ? '-Report-Only' : '';
                $headers['csp'] = 'Content-Security-Policy' . $report_only . ': ' . implode(' ; ', $directives);
            }
        }

        # --BEHAVIOR-- adminPageHTTPHeaders
        dcCore::app()->callBehavior('adminPageHTTPHeaders', $headers);
        foreach ($headers as $key => $value) {
            header($value);
        }

        $data_theme = dcCore::app()->auth->user_prefs->interface->theme;

        echo
        '<!DOCTYPE html>' .
        '<html lang="' . dcCore::app()->auth->getInfo('user_lang') . '" data-theme="' . $data_theme . '">' . "\n" .
        "<head>\n" .
        '  <meta charset="UTF-8" />' . "\n" .
        '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
        '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
        '  <title>' . $title . ' - ' . html::escapeHTML(dcCore::app()->blog->name) . ' - ' . html::escapeHTML(DC_VENDOR_NAME) . ' - ' . DC_VERSION . '</title>' . "\n";

        echo self::preload('style/default.css') . self::cssLoad('style/default.css');

        if ($rtl = (l10n::getLanguageTextDirection(dcCore::app()->lang) == 'rtl')) {
            echo self::cssLoad('style/default-rtl.css');
        }

        if (!dcCore::app()->auth->user_prefs->interface->hide_std_favicon) {
            echo
                '<link rel="icon" type="image/png" href="images/favicon96-login.png" />' . "\n" .
                '<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />' . "\n";
        }
        if (dcCore::app()->auth->user_prefs->interface->htmlfontsize) {
            $js['htmlFontSize'] = dcCore::app()->auth->user_prefs->interface->htmlfontsize;
        }
        $js['hideMoreInfo']   = (bool) dcCore::app()->auth->user_prefs->interface->hidemoreinfo;
        $js['showAjaxLoader'] = (bool) dcCore::app()->auth->user_prefs->interface->showajaxloader;

        $js['noDragDrop'] = (bool) dcCore::app()->auth->user_prefs->accessibility->nodragdrop;

        $js['debug'] = !!DC_DEBUG;

        $js['showIp'] = dcCore::app()->blog && dcCore::app()->blog->id ? dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id) : false;

        // Set some JSON data
        echo dcUtils::jsJson('dotclear_init', $js);

        echo
            self::jsCommon() .
            self::jsToggles() .
            $head;

        # --BEHAVIOR-- adminPageHTMLHead
        dcCore::app()->callBehavior('adminPageHTMLHead');

        echo
        "</head>\n" .
        '<body id="dotclear-admin" class="no-js' .
        ($rtl ? ' rtl ' : '') .
        ($safe_mode ? ' safe-mode' : '') .
        (DC_DEBUG ? ' debug-mode' : '') .
        '">' . "\n" .

        '<ul id="prelude">' .
        '<li><a href="#content">' . __('Go to the content') . '</a></li>' .
        '<li><a href="#main-menu">' . __('Go to the menu') . '</a></li>' .
        '<li><a href="#help">' . __('Go to help') . '</a></li>' .
        '</ul>' . "\n" .
        '<header id="header" role="banner">' .
        '<h1><a href="' . dcCore::app()->adminurl->get('admin.home') . '"><span class="hidden">' . DC_VENDOR_NAME . '</span></a></h1>' . "\n";

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.home') . '" method="post" id="top-info-blog">' .
        $blog_box .
        '<p><a href="' . dcCore::app()->blog->url . '" class="outgoing" title="' . __('Go to site') .
        '">' . __('Go to site') . '<img src="images/outgoing-link.svg" alt="" /></a>' .
        '</p></form>' .
        '<ul id="top-info-user">' .
        '<li><a class="' . (preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.home')) . '$/', (string) $_SERVER['REQUEST_URI']) ? ' active' : '') . '" href="' . dcCore::app()->adminurl->get('admin.home') . '">' . __('My dashboard') . '</a></li>' .
        '<li><a class="smallscreen' . (preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.user.preferences')) . '(\?.*)?$/', (string) $_SERVER['REQUEST_URI']) ? ' active' : '') .
        '" href="' . dcCore::app()->adminurl->get('admin.user.preferences') . '">' . __('My preferences') . '</a></li>' .
        '<li><a href="' . dcCore::app()->adminurl->get('admin.home', ['logout' => 1]) . '" class="logout"><span class="nomobile">' . sprintf(__('Logout %s'), dcCore::app()->auth->userID()) .
            '</span><img src="images/logout.svg" alt="" /></a></li>' .
            '</ul>' .
            '</header>'; // end header

        echo
        '<div id="wrapper" class="clearfix">' . "\n" .
        '<div class="hidden-if-no-js collapser-box"><button type="button" id="collapser" class="void-btn">' .
        '<img class="collapse-mm visually-hidden" src="images/collapser-hide.png" alt="' . __('Hide main menu') . '" />' .
        '<img class="expand-mm visually-hidden" src="images/collapser-show.png" alt="' . __('Show main menu') . '" />' .
            '</button></div>' .
            '<main id="main" role="main">' . "\n" .
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
        echo dcAdminNotices::getNotices();
    }

    /**
     * Get current notices
     *
     * @return     string
     */
    public static function notices(): string
    {
        return dcAdminNotices::getNotices();
    }

    /**
     * Adds a message notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addMessageNotice(string $message, array $options = [])
    {
        dcAdminNotices::addNotice(dcAdminNotices::NOTICE_MESSAGE, $message, $options);
    }

    /**
     * Adds a success notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addSuccessNotice(string $message, array $options = [])
    {
        dcAdminNotices::addNotice(dcAdminNotices::NOTICE_SUCCESS, $message, $options);
    }

    /**
     * Adds a warning notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addWarningNotice(string $message, array $options = [])
    {
        dcAdminNotices::addNotice(dcAdminNotices::NOTICE_WARNING, $message, $options);
    }

    /**
     * Adds an error notice.
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addErrorNotice(string $message, array $options = [])
    {
        dcAdminNotices::addNotice(dcAdminNotices::NOTICE_ERROR, $message, $options);
    }

    /**
     * Return/display a notice
     *
     * @param      string  $msg        The message
     * @param      bool    $timestamp  Include the timestamp
     * @param      bool    $div        The message container, true for <div>, false for <p>
     * @param      bool    $echo       Immediatly displayed
     * @param      string  $class      The class to use
     *
     * @return     string  The notice
     */
    public static function message(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true, ?string $class = null): string
    {
        return dcAdminNotices::message($msg, $timestamp, $div, $echo, $class);
    }

    /**
     * Return/display a success notice
     *
     * @param      string  $msg        The message
     * @param      bool    $timestamp  Include the timestamp
     * @param      bool    $div        The message container, true for <div>, false for <p>
     * @param      bool    $echo       Immediatly displayed
     *
     * @return     string  The notice
     */
    public static function success(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true): string
    {
        return dcAdminNotices::success($msg, $timestamp, $div, $echo);
    }

    /**
     * Return/display a warning notice
     *
     * @param      string  $msg        The message
     * @param      bool    $timestamp  Include the timestamp
     * @param      bool    $div        The message container, true for <div>, false for <p>
     * @param      bool    $echo       Immediatly displayed
     *
     * @return     string  The notice
     */
    public static function warning(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true): string
    {
        return dcAdminNotices::warning($msg, $timestamp, $div, $echo);
    }

    /**
     * Return/display a error notice
     *
     * @param      string  $msg        The message
     * @param      bool    $timestamp  Include the timestamp
     * @param      bool    $div        The message container, true for <div>, false for <p>
     * @param      bool    $echo       Immediatly displayed
     *
     * @return     string  The notice
     */
    public static function error(string $msg, bool $timestamp = true, bool $div = false, bool $echo = true): string
    {
        return dcAdminNotices::error($msg, $timestamp, $div, $echo);
    }

    /**
     * End of admin page
     */
    public static function close()
    {
        if (!dcCore::app()->resources['ctxhelp']) {
            if (!dcCore::app()->auth->user_prefs->interface->hidehelpbutton) {
                echo
                '<p id="help-button"><a href="' . dcCore::app()->adminurl->get('admin.help') . '" class="outgoing" title="' .
                __('Global help') . '">' . __('Global help') . '</a></p>';
            }
        }

        echo
        "</div>\n" .  // End of #content
        "</main>\n" . // End of #main

        '<nav id="main-menu" role="navigation">' . "\n" .

        '<form id="search-menu" action="' . dcCore::app()->adminurl->get('admin.search') . '" method="get" role="search">' .
        '<p><label for="qx" class="hidden">' . __('Search:') . ' </label>' . form::field('qx', 30, 255, '') .
        '<input type="submit" value="' . __('OK') . '" /></p>' .
            '</form>';

        foreach (array_keys((array) dcCore::app()->menu) as $k) {
            echo dcCore::app()->menu[$k]->draw();
        }

        $text = sprintf(__('Thank you for using %s.'), 'Dotclear ' . DC_VERSION . '<br />(Codename: So far so good)');

        # --BEHAVIOR-- adminPageFooter
        $textAlt = dcCore::app()->callBehavior('adminPageFooterV2', $text);
        if ($textAlt != '') {
            $text = $textAlt;
        }
        $text = html::escapeHTML($text);

        echo
        '</nav>' . "\n" . // End of #main-menu
        "</div>\n";       // End of #wrapper

        echo '<p id="gototop"><a href="#wrapper">' . __('Page top') . '</a></p>' . "\n";

        $figure = "\n" .

        '           |            ' . "\n" .
        '           |.===.       ' . "\n" .
        '           {}o o{}      ' . "\n" .
        '     ---ooO--(_)--Ooo---' . "\n";

        echo
            '<footer id="footer" role="contentinfo">' .
            '<a href="https://dotclear.org/" title="' . $text . '">' .
            '<img src="style/dc_logos/dotclear-light.svg" class="light-only" alt="' . $text . '" />' .
            '<img src="style/dc_logos/dotclear-dark.svg" class="dark-only" alt="' . $text . '" />' .
            '</a></footer>' . "\n" .
            '<!-- ' . "\n" .
            $figure .
            ' -->' . "\n";

        if (defined('DC_DEV') && DC_DEV === true) {
            echo self::debugInfo();
        }

        echo
            '</body></html>';
    }

    /**
     * The top of a popup.
     *
     * @param      string  $title       The title
     * @param      string  $head        The head
     * @param      string  $breadcrumb  The breadcrumb
     */
    public static function openPopup(string $title = '', string $head = '', string $breadcrumb = '')
    {
        $js = [];

        $safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

        # Display
        header('Content-Type: text/html; charset=UTF-8');

        # Referrer Policy for admin pages
        header('Referrer-Policy: strict-origin');

        # Prevents Clickjacking as far as possible
        header('X-Frame-Options: SAMEORIGIN'); // FF 3.6.9+ Chrome 4.1+ IE 8+ Safari 4+ Opera 10.5+

        $data_theme = dcCore::app()->auth->user_prefs->interface->theme;

        echo
        '<!DOCTYPE html>' .
        '<html lang="' . dcCore::app()->auth->getInfo('user_lang') . '" data-theme="' . $data_theme . '">' . "\n" .
        "<head>\n" .
        '  <meta charset="UTF-8" />' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
        '  <title>' . $title . ' - ' . html::escapeHTML(dcCore::app()->blog->name) . ' - ' . html::escapeHTML(DC_VENDOR_NAME) . ' - ' . DC_VERSION . '</title>' . "\n" .
            '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
            '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n";

        echo self::preload('style/default.css') . self::cssLoad('style/default.css');

        if ($rtl = (l10n::getLanguageTextDirection(dcCore::app()->lang) == 'rtl')) {
            echo self::cssLoad('style/default-rtl.css');
        }

        if (dcCore::app()->auth->user_prefs->interface->htmlfontsize) {
            $js['htmlFontSize'] = dcCore::app()->auth->user_prefs->interface->htmlfontsize;
        }
        $js['hideMoreInfo']   = (bool) dcCore::app()->auth->user_prefs->interface->hidemoreinfo;
        $js['showAjaxLoader'] = (bool) dcCore::app()->auth->user_prefs->interface->showajaxloader;

        $js['noDragDrop'] = (bool) dcCore::app()->auth->user_prefs->accessibility->nodragdrop;

        $js['debug'] = !!DC_DEBUG;

        // Set JSON data
        echo dcUtils::jsJson('dotclear_init', $js);

        echo
        self::jsCommon() .
        self::jsToggles() .
            $head;

        # --BEHAVIOR-- adminPageHTMLHead
        dcCore::app()->callBehavior('adminPageHTMLHead');

        echo
            "</head>\n" .
            '<body id="dotclear-admin" class="popup' .
            ($rtl ? 'rtl' : '') .
            ($safe_mode ? ' safe-mode' : '') .
            (DC_DEBUG ? ' debug-mode' : '') .
            '">' . "\n" .

            '<h1>' . DC_VENDOR_NAME . '</h1>' . "\n";

        echo
            '<div id="wrapper">' . "\n" .
            '<main id="main" role="main">' . "\n" .
            '<div id="content">' . "\n";

        // display breadcrumb if given
        echo $breadcrumb;

        // Display notices and errors
        echo dcAdminNotices::getNotices();
    }

    /**
     * The end of a popup.
     */
    public static function closePopup()
    {
        echo
        "</div>\n" .  // End of #content
        "</main>\n" . // End of #main
        "</div>\n" .  // End of #wrapper

        '<p id="gototop"><a href="#wrapper">' . __('Page top') . '</a></p>' . "\n" .

            '<footer id="footer" role="contentinfo"><p>&nbsp;</p></footer>' . "\n" .
            '</body></html>';
    }

    /**
     * Get breadcrumb
     *
     * @param      array|null   $elements  The elements
     * @param      array        $options   The options
     *
     * @return     string
     */
    public static function breadcrumb(?array $elements = null, array $options = []): string
    {
        $with_home_link = $options['home_link'] ?? true;
        $hl             = $options['hl']        ?? true;
        $hl_pos         = $options['hl_pos']    ?? -1;

        // First item of array elements should be blog's name, System or Plugins
        $res = '<h2>' . ($with_home_link ?
            '<a class="go_home" href="' . dcCore::app()->adminurl->get('admin.home') . '">' .
            '<img class="go_home light-only" src="style/dashboard.svg" alt="' . __('Go to dashboard') . '" />' .
            '<img class="go_home dark-only" src="style/dashboard-dark.svg" alt="' . __('Go to dashboard') . '" />' .
            '</a>' :
            '<img class="go_home light-only" src="style/dashboard-alt.svg" alt="" />' .
            '<img class="go_home dark-only" src="style/dashboard-alt-dark.svg" alt="" />');

        $index = 0;
        if ($hl_pos < 0) {
            $hl_pos = count((array) $elements) + $hl_pos;
        }
        foreach ($elements as $element => $url) {
            if ($hl && $index === $hl_pos) {
                $element = sprintf('<span class="page-title">%s</span>', $element);
            }
            $res .= ($with_home_link ? ($index === 1 ? ' : ' : ' &rsaquo; ') : ($index === 0 ? ' ' : ' &rsaquo; ')) .
                ($url ? '<a href="' . $url . '">' : '') . $element . ($url ? '</a>' : '');
            $index++;
        }

        $res .= '</h2>';

        return $res;
    }

    /**
     * Get HTML code of debug information
     *
     * @return     string
     */
    private static function debugInfo(): string
    {
        $global_vars = implode(', ', array_keys($GLOBALS));

        $res = '<div id="debug"><div>' .
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
                $res      .= '<p><a href="' . html::escapeURL($prof_url) . '">Trigger profiler</a></p>';
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

        $res .= '<p>Global vars: ' . $global_vars . '</p>' .
            '</div></div>';

        return $res;
    }

    /**
     * Display Help block
     *
     * @param      mixed  ...$params  The parameters
     */
    public static function helpBlock(...$params)
    {
        if (dcCore::app()->auth->user_prefs->interface->hidehelpbutton) {
            return;
        }

        $args = new ArrayObject($params);

        # --BEHAVIOR-- adminPageHelpBlock
        dcCore::app()->callBehavior('adminPageHelpBlock', $args);

        if (!count($args)) {
            return;
        }

        if (empty(dcCore::app()->resources['help'])) {
            return;
        }

        $content = '';
        foreach ($args as $arg) {
            if (is_object($arg) && isset($arg->content)) {
                $content .= $arg->content;

                continue;
            }

            if (!isset(dcCore::app()->resources['help'][$arg])) {
                continue;
            }
            $file = dcCore::app()->resources['help'][$arg];
            if (!file_exists($file) || !is_readable($file)) {
                continue;
            }

            $file_content = (string) file_get_contents($file);
            if (preg_match('|<body[^>]*?>(.*?)</body>|ms', $file_content, $matches)) {
                $content .= $matches[1];
            } else {
                $content .= $file_content;
            }
        }

        if (trim($content) == '') {
            return;
        }

        // Set contextual help global flag
        dcCore::app()->resources['ctxhelp'] = true;

        echo
        '<div id="help"><hr /><div class="help-content clear"><h3>' . __('Help about this page') . '</h3>' .
        $content .
        '</div>' .
        '<div id="helplink"><hr />' .
        '<p>' .
        sprintf(__('See also %s'), sprintf('<a href="' . dcCore::app()->adminurl->get('admin.help') . '">%s</a>', __('the global help'))) .
            '.</p>' .
            '</div></div>';
    }

    /**
     * Get HTML code to preload resource
     *
     * @param      string       $src         The source
     * @param      null|string  $version     The version
     * @param      string       $type        The type
     *
     * @return     string
     */
    public static function preload(string $src, ?string $version = '', string $type = 'style'): string
    {
        $escaped_src = html::escapeHTML($src);
        if (!isset(self::$preloaded[$escaped_src])) {
            self::$preloaded[$escaped_src] = true;

            return '<link rel="preload" href="' . self::appendVersion($escaped_src, $version) . '" as="' . $type . '" />' . "\n";
        }

        return '';
    }

    /**
     * Get HTML code to load CSS stylesheet
     *
     * @param      string       $src         The source
     * @param      string       $media       The media
     * @param      null|string  $version     The version
     *
     * @return     string
     */
    public static function cssLoad(string $src, string $media = 'screen', ?string $version = ''): string
    {
        $escaped_src = html::escapeHTML($src);
        if (!isset(self::$loaded_css[$escaped_src])) {
            self::$loaded_css[$escaped_src] = true;

            return '<link rel="stylesheet" href="' . self::appendVersion($escaped_src, $version) . '" type="text/css" media="' . $media . '" />' . "\n";
        }

        return '';
    }

    /**
     * Wrapper for cssLoad to be used by module
     *
     * @param      string       $src         The source
     * @param      string       $media       The media
     * @param      null|string  $version     The version
     *
     * @return     string
     */
    public static function cssModuleLoad(string $src, string $media = 'screen', ?string $version = ''): string
    {
        return self::cssLoad(urldecode(self::getPF($src)), $media, $version);
    }

    /**
     * Get HTML code to load JS script
     *
     * @param      string       $src         The source
     * @param      null|string  $version     The version
     *
     * @return     string
     */
    public static function jsLoad(string $src, ?string $version = ''): string
    {
        $escaped_src = html::escapeHTML($src);
        if (!isset(self::$loaded_js[$escaped_src])) {
            self::$loaded_js[$escaped_src] = true;

            return '<script src="' . self::appendVersion($escaped_src, $version) . '"></script>' . "\n";
        }

        return '';
    }

    /**
     * Wrapper for jsLoad to be used by module
     *
     * @param      string       $src         The source
     * @param      null|string  $version     The version
     *
     * @return     string
     */
    public static function jsModuleLoad(string $src, ?string $version = ''): string
    {
        return self::jsLoad(urldecode(self::getPF($src)), $version);
    }

    /**
     * Appends a version to force cache refresh if necessary.
     *
     * @param      string       $src         The source
     * @param      null|string  $version     The version
     *
     * @return     string
     */
    private static function appendVersion(string $src, ?string $version = ''): string
    {
        return $src .
            (strpos($src, '?') === false ? '?' : '&amp;') .
            'v=' . (defined('DC_DEV') && DC_DEV === true ? md5(uniqid()) : ($version ?: DC_VERSION));
    }

    /**
     * Get HTML code to load JS variables encoded as JSON
     *
     * @param      string  $id     The identifier
     * @param      mixed   $vars   The variables
     *
     * @return     string
     */
    public static function jsJson(string $id, $vars): string
    {
        return dcUtils::jsJson($id, $vars);
    }

    /**
     * Get HTML code to load toggles JS
     *
     * @return     string
     */
    public static function jsToggles(): string
    {
        $js = [];
        if (dcCore::app()->auth->user_prefs->toggles) {
            $unfolded_sections = explode(',', (string) dcCore::app()->auth->user_prefs->toggles->unfolded_sections);
            foreach ($unfolded_sections as $section => &$v) {
                if ($v !== '') {
                    $js[$unfolded_sections[$section]] = true;
                }
            }
        }

        return
        self::jsJson('dotclear_toggles', $js) .
        self::jsLoad('js/toggles.js');
    }

    /**
     * Get HTML code to load common JS for admin pages
     *
     * @return     string
     */
    public static function jsCommon(): string
    {
        $js = [
            'nonce'         => dcCore::app()->getNonce(),

            'img_plus_src'  => 'images/expand.svg',
            'img_plus_txt'  => '▶',
            'img_plus_alt'  => __('uncover'),

            'img_minus_src' => 'images/hide.svg',
            'img_minus_txt' => '▼',
            'img_minus_alt' => __('hide'),
        ];

        $js_msg = [
            'help'                                 => __('Need help?'),
            'new_window'                           => __('new window'),
            'help_hide'                            => __('Hide'),
            'to_select'                            => __('Select:'),
            'no_selection'                         => __('No selection'),
            'select_all'                           => __('Select all'),
            'invert_sel'                           => __('Invert selection'),
            'website'                              => __('Web site:'),
            'email'                                => __('Email:'),
            'ip_address'                           => __('IP address:'),
            'error'                                => __('Error:'),
            'entry_created'                        => __('Entry has been successfully created.'),
            'edit_entry'                           => __('Edit entry'),
            'view_entry'                           => __('view entry'),
            'confirm_delete_posts'                 => __('Are you sure you want to delete selected entries (%s)?'),
            'confirm_delete_medias'                => __('Are you sure you want to delete selected medias (%d)?'),
            'confirm_delete_categories'            => __('Are you sure you want to delete selected categories (%s)?'),
            'confirm_delete_post'                  => __('Are you sure you want to delete this entry?'),
            'click_to_unlock'                      => __('Click here to unlock the field'),
            'confirm_spam_delete'                  => __('Are you sure you want to delete all spams?'),
            'confirm_delete_comments'              => __('Are you sure you want to delete selected comments (%s)?'),
            'confirm_delete_comment'               => __('Are you sure you want to delete this comment?'),
            'cannot_delete_users'                  => __('Users with posts cannot be deleted.'),
            'confirm_delete_user'                  => __('Are you sure you want to delete selected users (%s)?'),
            'confirm_delete_blog'                  => __('Are you sure you want to delete selected blogs (%s)?'),
            'confirm_delete_category'              => __('Are you sure you want to delete category "%s"?'),
            'confirm_reorder_categories'           => __('Are you sure you want to reorder all categories?'),
            'confirm_delete_media'                 => __('Are you sure you want to remove media "%s"?'),
            'confirm_delete_directory'             => __('Are you sure you want to remove directory "%s"?'),
            'confirm_extract_current'              => __('Are you sure you want to extract archive in current directory?'),
            'confirm_remove_attachment'            => __('Are you sure you want to remove attachment "%s"?'),
            'confirm_delete_lang'                  => __('Are you sure you want to delete "%s" language?'),
            'confirm_delete_plugin'                => __('Are you sure you want to delete "%s" plugin?'),
            'confirm_delete_plugins'               => __('Are you sure you want to delete selected plugins?'),
            'use_this_theme'                       => __('Use this theme'),
            'remove_this_theme'                    => __('Remove this theme'),
            'confirm_delete_theme'                 => __('Are you sure you want to delete "%s" theme?'),
            'confirm_delete_themes'                => __('Are you sure you want to delete selected themes?'),
            'confirm_delete_backup'                => __('Are you sure you want to delete this backup?'),
            'confirm_revert_backup'                => __('Are you sure you want to revert to this backup?'),
            'zip_file_content'                     => __('Zip file content'),
            'xhtml_validator'                      => __('HTML markup validator'),
            'xhtml_valid'                          => __('HTML content is valid.'),
            'xhtml_not_valid'                      => __('There are HTML markup errors.'),
            'warning_validate_no_save_content'     => __('Attention: an audit of a content not yet registered.'),
            'confirm_change_post_format'           => __('You have unsaved changes. Switch post format will loose these changes. Proceed anyway?'),
            'confirm_change_post_format_noconvert' => __('Warning: post format change will not convert existing content. You will need to apply new format by yourself. Proceed anyway?'),
            'load_enhanced_uploader'               => __('Loading enhanced uploader, please wait.'),

            'module_author'                        => __('Author:'),
            'module_details'                       => __('Details'),
            'module_support'                       => __('Support'),
            'module_help'                          => __('Help:'),
            'module_section'                       => __('Section:'),
            'module_tags'                          => __('Tags:'),

            'close_notice'                         => __('Hide this notice'),

            'show_password'                        => __('Show password'),
            'hide_password'                        => __('Hide password'),

            'set_today'                            => __('Reset to now'),

            'adblocker'                            => __('An ad blocker has been detected on this Dotclear dashboard (Ghostery, Adblock plus, uBlock origin, …) and it may interfere with some features. In this case you should disable it. Note that this detection may be disabled in your preferences.'),
        ];

        return
        self::jsLoad('js/prepend.js') .
        self::jsLoad('js/jquery/jquery.js') .
        (
            DC_DEBUG ?
            self::jsJson('dotclear_jquery', [
                'mute' => (empty(dcCore::app()->blog) || dcCore::app()->blog->settings->system->jquery_migrate_mute),
            ]) .
            self::jsLoad('js/jquery-mute.js') .
            self::jsLoad('js/jquery/jquery-migrate.js') :
            ''
        ) .

        self::jsJson('dotclear', $js) .
        self::jsJson('dotclear_msg', $js_msg) .

        self::jsLoad('js/common.js') .
        self::jsLoad('js/services.js') .
        self::jsLoad('js/prelude.js');
    }

    /**
     * Get HTML code to load ads blocker detector for admin pages (usually active on dashboard and user preferences pages)
     *
     * @return     string
     */
    public static function jsAdsBlockCheck(): string
    {
        $adblockcheck = (!defined('DC_ADBLOCKER_CHECK') || DC_ADBLOCKER_CHECK === true);
        if ($adblockcheck) {
            // May not be set (auth page for example)
            if (dcCore::app()->auth->user_prefs) {
                $adblockcheck = dcCore::app()->auth->user_prefs->interface->nocheckadblocker !== true;
            } else {
                $adblockcheck = false;
            }
        }

        return $adblockcheck ? self::jsLoad('js/ads.js') : '';
    }

    /**
     * Get HTML code to load ConfirmClose JS
     *
     * @param      mixed  ...$args  The arguments
     *
     * @return     string
     */
    public static function jsConfirmClose(...$args): string
    {
        $js = [
            'prompt' => __('You have unsaved changes.'),
            'forms'  => $args,
        ];

        return
        self::jsJson('confirm_close', $js) .
        self::jsLoad('js/confirm-close.js');
    }

    /**
     * Get HTML code to load page tabs JS
     *
     * @param      mixed   $default  The default
     *
     * @return     string
     */
    public static function jsPageTabs($default = null): string
    {
        $js = [
            'default' => $default,
        ];

        return
        self::jsJson('page_tabs', $js) .
        self::jsLoad('js/jquery/jquery.pageTabs.js') .
        self::jsLoad('js/page-tabs.js');
    }

    /**
     * Get HTML code to load Magnific popup JS
     *
     * @return     string
     */
    public static function jsModal(): string
    {
        return
        self::jsLoad('js/jquery/jquery.magnific-popup.js');
    }

    /**
     * Get HTML to load Upload JS utility
     *
     * @param      array        $params    The parameters
     * @param      null|string  $base_url  The base url
     *
     * @return     string
     */
    public static function jsUpload(array $params = [], ?string $base_url = null): string
    {
        if (!$base_url) {
            $base_url = path::clean(dirname(preg_replace('/(\?.*$)?/', '', (string) $_SERVER['REQUEST_URI']))) . '/';
        }

        $params = array_merge($params, [
            'sess_id=' . session_id(),
            'sess_uid=' . $_SESSION['sess_browser_uid'],
            'xd_check=' . dcCore::app()->getNonce(),
        ]);

        $js_msg = [
            'enhanced_uploader_activate' => __('Temporarily activate enhanced uploader'),
            'enhanced_uploader_disable'  => __('Temporarily disable enhanced uploader'),
        ];
        $js = [
            'msg'      => [
                'limit_exceeded'             => __('Limit exceeded.'),
                'size_limit_exceeded'        => __('File size exceeds allowed limit.'),
                'canceled'                   => __('Canceled.'),
                'http_error'                 => __('HTTP Error:'),
                'error'                      => __('Error:'),
                'choose_file'                => __('Choose file'),
                'choose_files'               => __('Choose files'),
                'cancel'                     => __('Cancel'),
                'clean'                      => __('Clean'),
                'upload'                     => __('Upload'),
                'send'                       => __('Send'),
                'file_successfully_uploaded' => __('File successfully uploaded.'),
                'no_file_in_queue'           => __('No file in queue.'),
                'file_in_queue'              => __('1 file in queue.'),
                'files_in_queue'             => __('%d files in queue.'),
                'queue_error'                => __('Queue error:'),
            ],
            'base_url' => $base_url,
        ];

        return
        self::jsJson('file_upload', $js) .
        self::jsJson('file_upload_msg', $js_msg) .
        self::jsLoad('js/file-upload.js') .
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

    /**
     * Get HTML code to load meta editor
     *
     * @return     string
     */
    public static function jsMetaEditor(): string
    {
        return self::jsLoad('js/meta-editor.js');
    }

    /**
     * Get HTML code for filters control JS utility
     *
     * @param      bool    $show   Show filters?
     *
     * @return     string
     */
    public static function jsFilterControl(bool $show = true): string
    {
        $js = [
            'show_filters'      => (bool) $show,
            'filter_posts_list' => __('Show filters and display options'),
            'cancel_the_filter' => __('Cancel filters and display options'),
        ];

        return
        self::jsJson('filter_controls', $js) .
        self::jsJson('filter_options', ['auto_filter' => dcCore::app()->auth->user_prefs->interface->auto_filter]) .
        self::jsLoad('js/filter-controls.js');
    }

    /**
     * Get HTML code to load Codemirror
     *
     * @param      string  $theme  The theme
     * @param      bool    $multi  Is multiplex?
     * @param      array   $modes  The modes
     *
     * @return     string
     */
    public static function jsLoadCodeMirror(string $theme = '', bool $multi = true, array $modes = ['css', 'htmlmixed', 'javascript', 'php', 'xml', 'clike']): string
    {
        $ret = self::cssLoad('js/codemirror/lib/codemirror.css') .
        self::jsLoad('js/codemirror/lib/codemirror.js');
        if ($multi) {
            $ret .= self::jsLoad('js/codemirror/addon/mode/multiplex.js');
        }
        foreach ($modes as $mode) {
            $ret .= self::jsLoad('js/codemirror/mode/' . $mode . '/' . $mode . '.js');
        }
        $ret .= self::jsLoad('js/codemirror/addon/edit/closebrackets.js') .
        self::jsLoad('js/codemirror/addon/edit/matchbrackets.js') .
        self::cssLoad('js/codemirror/addon/display/fullscreen.css') .
        self::jsLoad('js/codemirror/addon/display/fullscreen.js');
        if ($theme != '' && $theme !== 'default') {
            $ret .= self::cssLoad('js/codemirror/theme/' . $theme . '.css');
        }

        return $ret;
    }

    /**
     * Get HTML code to run Codemirror
     *
     * @param      mixed        $name   The HTML name attribute
     * @param      null|string  $id     The HTML id attribute
     * @param      mixed        $mode   The Codemirror mode
     * @param      string       $theme  The theme
     *
     * @return     string
     */
    public static function jsRunCodeMirror($name, ?string $id = null, $mode = null, string $theme = ''): string
    {
        if (is_array($name)) {
            $js = $name;
        } else {
            $js = [[
                'name'  => $name,
                'id'    => $id,
                'mode'  => $mode,
                'theme' => $theme ?: 'default',
            ]];
        }

        return
        self::jsJson('codemirror', $js) .
        self::jsLoad('js/codemirror.js');
    }

    /**
     * Gets the codemirror themes list.
     *
     * @return     array  The code mirror themes.
     */
    public static function getCodeMirrorThemes(): array
    {
        $themes      = [];
        $themes_root = __DIR__ . '/../../admin' . '/js/codemirror/theme/';
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

    /**
     * Gets plugin file.
     *
     * @param      string  $file   The filename
     *
     * @return     string  The URL.
     */
    public static function getPF(string $file): string
    {
        return dcCore::app()->adminurl->get('load.plugin.file', ['pf' => $file]);
    }

    /**
     * Gets var file.
     *
     * @param      string  $file   The filename
     *
     * @return     string  The URL.
     */
    public static function getVF(string $file): string
    {
        return dcCore::app()->adminurl->get('load.var.file', ['vf' => $file]);
    }

    /**
     * Sets the x frame options.
     *
     * @param      array|ArrayObject    $headers  The headers
     * @param      mixed                $origin   The origin
     */
    public static function setXFrameOptions($headers, $origin = null)
    {
        if (self::$xframe_loaded) {
            return;
        }

        if ($origin !== null) {
            $url                        = parse_url($origin);
            $headers['x-frame-options'] = sprintf('X-Frame-Options: %s', is_array($url) && isset($url['host']) ?
                ('ALLOW-FROM ' . (isset($url['scheme']) ? $url['scheme'] . ':' : '') . '//' . $url['host']) :
                'SAMEORIGIN');
        } else {
            $headers['x-frame-options'] = 'X-Frame-Options: SAMEORIGIN'; // FF 3.6.9+ Chrome 4.1+ IE 8+ Safari 4+ Opera 10.5+
        }
        self::$xframe_loaded = true;
    }

    // Deprecated methods
    // ------------------

    /**
     * @deprecated since 2.24
     */
    public static function help()
    {
        # Deprecated but we keep this for plugins.
    }

    /**
     * @deprecated since version 2.16
     *
     * @return     string
     */
    public static function jsColorPicker(): string
    {
        return '';
    }

    /**
     * Get HTML code for date picker JS utility
     *
     * @deprecated since 2.21
     *
     * @return     string
     */
    public static function jsDatePicker(): string
    {
        return '';
    }

    /**
     * Load jsToolBar
     *
     * @deprecated since 2.??
     *
     * @return  string
     */
    public static function jsToolBar()
    {
        # Deprecated but we keep this for plugins.

        return '';
    }

    /**
     * return a javascript variable definition line code
     *
     * @deprecated 2.15 use dcPage::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript
     *
     * @param      string  $name      variable name
     * @param      mixed   $value      value
     *
     * @return     string  javascript code
     */
    public static function jsVar(string $name, $value): string
    {
        return $name . " = '" . html::escapeJS($value) . "';\n";
    }

    /**
     * return a list of javascript variables définitions code
     *
     * @deprecated 2.15 use dcPage::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript
     *
     * @param      array  $vars   The variables
     *
     * @return     string  javascript code (inside <script></script>)
     */
    public static function jsVars(array $vars): string
    {
        $ret = '<script>' . "\n";
        foreach ($vars as $var => $value) {
            $ret .= $var . ' = ' . (is_string($value) ? "'" . html::escapeJS($value) . "'" : $value) . ';' . "\n";
        }
        $ret .= "</script>\n";

        return $ret;
    }

    /**
     * @deprecated since version 2.11
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function jsLoadIE7()
    {
        return '';
    }
}
