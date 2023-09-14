<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use ArrayObject;
use Autoloader;
use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use form;

class Page
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
        if (App::blog()->isDefined() && App::auth()->check($permissions, App::blog()->id())) {
            return;
        }

        // Check if dashboard is not the current page et if it is granted for the user
        if (!$home && App::blog()->isDefined() && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            // Go back to the dashboard
            Http::redirect(App::config()->adminUrl());
        }

        if (session_id()) {
            App::session()->destroy();
        }
        App::backend()->url->redirect('admin.auth');
    }

    /**
     * Check super admin
     *
     * @param      bool  $home   The home
     */
    public static function checkSuper(bool $home = false)
    {
        if (!App::auth()->isSuperAdmin()) {
            // Check if dashboard is not the current page et if it is granted for the user
            if (!$home && App::blog()->isDefined() && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                // Go back to the dashboard
                Http::redirect(App::config()->adminUrl());
            }

            if (session_id()) {
                App::session()->destroy();
            }
            App::backend()->url->redirect('admin.auth');
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
        if (App::auth()->getBlogCount() == 1 || App::auth()->getBlogCount() > 20) {
            $blog_box = '<p>' . __('Blog:') . ' <strong title="' . Html::escapeHTML(App::blog()->url()) . '">' .
            Html::escapeHTML(App::blog()->name()) . '</strong>';

            if (App::auth()->getBlogCount() > 20) {
                $blog_box .= ' - <a href="' . App::backend()->url->get('admin.blogs') . '">' . __('Change blog') . '</a>';
            }
            $blog_box .= '</p>';
        } else {
            $rs_blogs = App::blogs()->getBlogs(['order' => 'LOWER(blog_name)', 'limit' => 20]);
            $blogs    = [];
            while ($rs_blogs->fetch()) {
                $blogs[Html::escapeHTML($rs_blogs->blog_name . ' - ' . $rs_blogs->blog_url)] = $rs_blogs->blog_id;
            }
            $blog_box = '<p><label for="switchblog" class="classic">' . __('Blogs:') . '</label> ' .
            App::nonce()->getFormNonce() . form::combo('switchblog', $blogs, App::blog()->id()) .
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
        if (!$safe_mode && App::blog()->settings()->system->csp_admin_on) {
            // Get directives from settings if exist, else set defaults
            $csp = new ArrayObject([]);

            // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
            // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
            $csp_prefix = App::con()->syntax() == 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks syntax
            $csp_suffix = App::con()->syntax() == 'sqlite' ? ' 127.0.0.1' : ''; // Hack for SQlite Clearbricks syntax

            $csp['default-src'] = App::blog()->settings()->system->csp_admin_default ?:
            $csp_prefix . "'self'" . $csp_suffix;
            $csp['script-src'] = App::blog()->settings()->system->csp_admin_script ?:
            $csp_prefix . "'self' 'unsafe-eval'" . $csp_suffix;
            $csp['style-src'] = App::blog()->settings()->system->csp_admin_style ?:
            $csp_prefix . "'self' 'unsafe-inline'" . $csp_suffix;
            $csp['img-src'] = App::blog()->settings()->system->csp_admin_img ?:
            $csp_prefix . "'self' data: https://media.dotaddict.org blob:";

            # Cope with blog post preview (via public URL in iframe)
            if (App::blog()->host()) {
                $csp['default-src'] .= ' ' . parse_url(App::blog()->host(), PHP_URL_HOST);
                $csp['script-src']  .= ' ' . parse_url(App::blog()->host(), PHP_URL_HOST);
                $csp['style-src']   .= ' ' . parse_url(App::blog()->host(), PHP_URL_HOST);
            }
            # Cope with media display in media manager (via public URL)
            if (App::media()->getRootUrl()) {
                $csp['img-src'] .= ' ' . parse_url(App::media()->getRootUrl(), PHP_URL_HOST);
            } elseif (!is_null(App::blog()->host())) {
                // Let's try with the blog URL
                $csp['img-src'] .= ' ' . parse_url(App::blog()->host(), PHP_URL_HOST);
            }
            # Allow everything in iframe (used by editors to preview public content)
            $csp['frame-src'] = '*';

            # --BEHAVIOR-- adminPageHTTPHeaderCSP -- ArrayObject
            App::behavior()->callBehavior('adminPageHTTPHeaderCSP', $csp);

            // Construct CSP header
            $directives = [];
            foreach ($csp as $key => $value) {
                if ($value) {
                    $directives[] = $key . ' ' . $value;
                }
            }
            if (count($directives)) {
                $directives[]   = 'report-uri ' . App::config()->adminUrl() . App::backend()->url->get('admin.csp.report');
                $report_only    = (App::blog()->settings()->system->csp_admin_report_only) ? '-Report-Only' : '';
                $headers['csp'] = 'Content-Security-Policy' . $report_only . ': ' . implode(' ; ', $directives);
            }
        }

        # --BEHAVIOR-- adminPageHTTPHeaders -- ArrayObject
        App::behavior()->callBehavior('adminPageHTTPHeaders', $headers);
        foreach ($headers as $key => $value) {
            header($value);
        }

        $data_theme = App::auth()->prefs()->interface->theme;

        echo
        '<!DOCTYPE html>' .
        '<html lang="' . App::auth()->getInfo('user_lang') . '" data-theme="' . $data_theme . '">' . "\n" .
        "<head>\n" .
        '  <meta charset="UTF-8" />' . "\n" .
        '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
        '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
        '  <title>' . $title . ' - ' . Html::escapeHTML(App::blog()->name()) . ' - ' . Html::escapeHTML(App::config()->vendorName()) . ' - ' . App::config()->dotclearVersion() . '</title>' . "\n";

        echo self::cssLoad('style/default.css');

        if ($rtl = (L10n::getLanguageTextDirection(App::lang()) == 'rtl')) {
            echo self::cssLoad('style/default-rtl.css');
        }

        if (!App::auth()->prefs()->interface->hide_std_favicon) {
            echo
                '<link rel="icon" type="image/png" href="images/favicon96-login.png" />' . "\n" .
                '<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />' . "\n";
        }
        if (App::auth()->prefs()->interface->htmlfontsize) {
            $js['htmlFontSize'] = App::auth()->prefs()->interface->htmlfontsize;
        }
        if (App::auth()->prefs()->interface->systemfont) {
            $js['systemFont'] = true;
        }
        $js['hideMoreInfo']   = (bool) App::auth()->prefs()->interface->hidemoreinfo;
        $js['showAjaxLoader'] = (bool) App::auth()->prefs()->interface->showajaxloader;
        $js['servicesUri']    = App::backend()->url->get('admin.rest');
        $js['servicesOff']    = !App::rest()->serveRestRequests();

        $js['noDragDrop'] = (bool) App::auth()->prefs()->accessibility->nodragdrop;

        $js['debug'] = !!App::config()->debugMode();

        $js['showIp'] = App::blog()->isDefined() ? App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id()) : false;

        // Set some JSON data
        echo Html::jsJson('dotclear_init', $js);

        echo
            self::jsCommon() .
            self::jsToggles() .
            $head;

        # --BEHAVIOR-- adminPageHTMLHead
        App::behavior()->callBehavior('adminPageHTMLHead');

        echo
        "</head>\n" .
        '<body id="dotclear-admin" class="no-js' .
        ($rtl ? ' rtl ' : '') .
        ($safe_mode ? ' safe-mode' : '') .
        (App::config()->debugMode() ? ' debug-mode' : '') .
        '">' . "\n" .
        '<ul id="prelude">' .
        '<li><a href="#content">' . __('Go to the content') . '</a></li>' .
        '<li><a href="#main-menu">' . __('Go to the menu') . '</a></li>' .
        '<li><a href="#help">' . __('Go to help') . '</a></li>' .
        '</ul>' . "\n" .
        '<header id="header" role="banner">' .
        '<h1><a href="' . App::backend()->url->get('admin.home') . '" title="' . __('My dashboard') . '"><span class="hidden">' . App::config()->vendorName() . '</span></a></h1>' . "\n";

        echo
        '<form action="' . App::backend()->url->get('admin.home') . '" method="post" id="top-info-blog">' .
        $blog_box .
        '<p><a href="' . App::blog()->url() . '" class="outgoing" title="' . __('Go to site') .
        '">' . __('Go to site') . '<img src="images/outgoing-link.svg" alt="" /></a>' .
        '</p></form>' .
        '<ul id="top-info-user">' .
        '<li><a class="smallscreen' . (preg_match('/' . preg_quote(App::backend()->url->get('admin.user.preferences')) . '(\?.*)?$/', (string) $_SERVER['REQUEST_URI']) ? ' active' : '') .
        '" href="' . App::backend()->url->get('admin.user.preferences') . '">' . __('My preferences') . '</a></li>' .
        '<li><a href="' . App::backend()->url->get('admin.logout') . '" class="logout"><span class="nomobile">' . sprintf(__('Logout %s'), App::auth()->userID()) .
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

        // Display breadcrumb (if given) before any error messages
        echo $breadcrumb;

        // Safe mode
        if ($safe_mode) {
            echo
            '<div class="warning" role="alert"><h3>' . __('Safe mode') . '</h3>' .
            '<p>' . __('You are in safe mode. All plugins have been temporarily disabled. Remind to log out then log in again normally to get back all functionalities') . '</p>' .
                '</div>';
        }

        // Display notices and errors
        echo Notices::getNotices();
    }

    /**
     * Get current notices
     *
     * @deprecated sicne 2.27, use Notices::getNotices() instead
     *
     * @return     string
     */
    public static function notices(): string
    {
        App::deprecated()->set('Notices::getNotices()', '2.27');

        return Notices::getNotices();
    }

    /**
     * Adds a message notice.
     *
     * @deprecated sicne 2.27, use Notices::addMessageNotice() instead
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addMessageNotice(string $message, array $options = [])
    {
        App::deprecated()->set('Notices::addNotices()', '2.27');

        Notices::addNotice(Notices::NOTICE_MESSAGE, $message, $options);
    }

    /**
     * Adds a success notice.
     *
     * @deprecated sicne 2.27, use Notices::addSuccessNotice() instead
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addSuccessNotice(string $message, array $options = [])
    {
        App::deprecated()->set('Notices::addNotices()', '2.27');

        Notices::addNotice(Notices::NOTICE_SUCCESS, $message, $options);
    }

    /**
     * Adds a warning notice.
     *
     * @deprecated sicne 2.27, use Notices::addWarningNotice() instead
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addWarningNotice(string $message, array $options = [])
    {
        App::deprecated()->set('Notices::addNotices()', '2.27');

        Notices::addNotice(Notices::NOTICE_WARNING, $message, $options);
    }

    /**
     * Adds an error notice.
     *
     * @deprecated sicne 2.27, use Notices::addErrorNotice() instead
     *
     * @param      string  $message  The message
     * @param      array   $options  The options
     */
    public static function addErrorNotice(string $message, array $options = [])
    {
        App::deprecated()->set('Notices::addNotices()', '2.27');

        Notices::addNotice(Notices::NOTICE_ERROR, $message, $options);
    }

    /**
     * Return/display a notice.
     *
     * @deprecated sicne 2.27, use Notices::message() instead
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
        App::deprecated()->set('Notices::message()', '2.27');

        return Notices::message($msg, $timestamp, $div, $echo, $class);
    }

    /**
     * Return/display a success notice.
     *
     * @deprecated sicne 2.27, use Notices::success() instead
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
        App::deprecated()->set('Notices::success()', '2.27');

        return Notices::success($msg, $timestamp, $div, $echo);
    }

    /**
     * Return/display a warning notice.
     *
     * @deprecated sicne 2.27, use Notices::warning() instead
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
        App::deprecated()->set('Notices::warning()', '2.27');

        return Notices::warning($msg, $timestamp, $div, $echo);
    }

    /**
     * Return/display a error notice.
     *
     * @deprecated sicne 2.27, use Notices::error() instead
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
        App::deprecated()->set('Notices::error()', '2.27');

        return Notices::error($msg, $timestamp, $div, $echo);
    }

    /**
     * End of admin page
     */
    public static function close()
    {
        if (!App::backend()->resources->context()) {
            if (!App::auth()->prefs()->interface->hidehelpbutton) {
                echo
                '<p id="help-button"><a href="' . App::backend()->url->get('admin.help') . '" class="outgoing" title="' .
                __('Global help') . '">' . __('Global help') . '</a></p>';
            }
        }

        echo
        "</div>\n" .  // End of #content
        "</main>\n" . // End of #main

        '<nav id="main-menu" role="navigation">' . "\n" .

        '<form id="search-menu" action="' . App::backend()->url->get('admin.search') . '" method="get" role="search">' .
        '<p><label for="qx" class="hidden">' . __('Search:') . ' </label>' . form::field('qx', 30, 255, '') .
        '<input type="hidden" name="process" value="Search" />' .
        '<input type="submit" value="' . __('OK') . '" /></p>' .
            '</form>';

        foreach (array_keys((array) App::backend()->menus) as $k) {
            echo App::backend()->menus[$k]->draw();
        }

        $text = sprintf(__('Thank you for using %s.'), 'Dotclear ' . App::config()->dotclearVersion() . '<br />(Codename: ' . App::config()->dotclearName() . ')');

        # --BEHAVIOR-- adminPageFooter --
        $textAlt = App::behavior()->callBehavior('adminPageFooterV2', $text);
        if ($textAlt != '') {
            $text = $textAlt;
        }
        $text = Html::escapeHTML($text);

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

        if (App::config()->devMode() === true) {
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

        $data_theme = App::auth()->prefs()->interface->theme;

        echo
        '<!DOCTYPE html>' .
        '<html lang="' . App::auth()->getInfo('user_lang') . '" data-theme="' . $data_theme . '">' . "\n" .
        "<head>\n" .
        '  <meta charset="UTF-8" />' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
        '  <title>' . $title . ' - ' . Html::escapeHTML(App::blog()->name()) . ' - ' . Html::escapeHTML(App::config()->vendorName()) . ' - ' . App::config()->dotclearVersion() . '</title>' . "\n" .
            '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
            '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n";

        echo self::cssLoad('style/default.css');

        if ($rtl = (L10n::getLanguageTextDirection(App::lang()) == 'rtl')) {
            echo self::cssLoad('style/default-rtl.css');
        }

        if (App::auth()->prefs()->interface->htmlfontsize) {
            $js['htmlFontSize'] = App::auth()->prefs()->interface->htmlfontsize;
        }
        if (App::auth()->prefs()->interface->systemfont) {
            $js['systemFont'] = true;
        }
        $js['hideMoreInfo']   = (bool) App::auth()->prefs()->interface->hidemoreinfo;
        $js['showAjaxLoader'] = (bool) App::auth()->prefs()->interface->showajaxloader;
        $js['servicesUri']    = App::backend()->url->get('admin.rest');
        $js['servicesOff']    = !App::rest()->serveRestRequests();

        $js['noDragDrop'] = (bool) App::auth()->prefs()->accessibility->nodragdrop;

        $js['debug'] = !!App::config()->debugMode();

        // Set JSON data
        echo Html::jsJson('dotclear_init', $js);

        echo
        self::jsCommon() .
        self::jsToggles() .
            $head;

        # --BEHAVIOR-- adminPageHTMLHead --
        App::behavior()->callBehavior('adminPageHTMLHead');

        echo
            "</head>\n" .
            '<body id="dotclear-admin" class="popup' .
            ($rtl ? 'rtl' : '') .
            ($safe_mode ? ' safe-mode' : '') .
            (App::config()->debugMode() ? ' debug-mode' : '') .
            '">' . "\n" .
            '<h1>' . App::config()->vendorName() . '</h1>' . "\n";

        echo
            '<div id="wrapper">' . "\n" .
            '<main id="main" role="main">' . "\n" .
            '<div id="content">' . "\n";

        // display breadcrumb if given
        echo $breadcrumb;

        // Display notices and errors
        echo Notices::getNotices();
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
     * Opens a module.
     *
     * @param      string       $title  The title
     * @param      null|string  $head   The head
     */
    public static function openModule(string $title = '', ?string $head = '')
    {
        if (!$title) {
            $title = App::config()->vendorName();
        }
        echo '<html><head><title>' . $title . '</title>' . $head . '</head><body>';
    }

    /**
     * Closes a module.
     */
    public static function closeModule()
    {
        echo '</body></html>';
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
        $res = '<h2 role="navigation">' . ($with_home_link ?
            '<a class="go_home" href="' . App::backend()->url->get('admin.home') . '">' .
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
                $element = sprintf('<span class="page-title" aria-current="location">%s</span>', $element);
            }
            $res .= ($with_home_link ? ($index === 1 ? ' : ' : ' &rsaquo; ') : ($index === 0 ? ' ' : ' &rsaquo; ')) .
                ($url ? '<a href="' . $url . '">' : '') . $element . ($url ? '</a>' : '');
            $index++;
        }

        $res .= '</h2>';

        return $res;
    }

    /**
     * Ensures that Xdebug stack trace is available based on Xdebug version.
     *
     * Idea taken from developer bishopb at https://github.com/rollbar/rollbar-php
     *
     *  xdebug configuration:
     *
     *  zend_extension = /.../xdebug.so
     *
     *  xdebug.auto_trace = On
     *  xdebug.trace_format = 0
     *  xdebug.trace_options = 1
     *  xdebug.show_mem_delta = On
     *  xdebug.profiler_enable = 0
     *  xdebug.profiler_enable_trigger = 1
     *  xdebug.profiler_output_dir = /tmp
     *  xdebug.profiler_append = 0
     *  xdebug.profiler_output_name = timestamp
     *
     * @return      bool
     */
    private static function isXdebugStackAvailable()
    {
        if (!function_exists('xdebug_get_function_stack')) {
            return false;
        }

        // check for Xdebug being installed to ensure origin of xdebug_get_function_stack()
        $version = phpversion('xdebug');
        if ($version === false) {
            return false;
        }

        // Xdebug 2 and prior
        if (version_compare($version, '3.0.0', '<')) {
            return true;
        }

        // Xdebug 3 and later, proper mode is required
        return str_contains(ini_get('xdebug.mode'), 'develop');
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
        '<p>memory usage: ' . memory_get_usage() . ' (' . Files::size(memory_get_usage()) . ')</p>';

        if (self::isXdebugStackAvailable()) {
            $res .= '<p>Elapsed time: ' . xdebug_time_index() . ' seconds</p>';

            $prof_file = xdebug_get_profiler_filename();
            if ($prof_file) {
                $res .= '<p>Profiler file : ' . xdebug_get_profiler_filename() . '</p>';
            } else {
                $prof_url = Http::getSelfURI();
                $prof_url .= str_contains($prof_url, '?') ? '&' : '?';
                $prof_url .= 'XDEBUG_PROFILE';
                $res      .= '<p><a href="' . Html::escapeURL($prof_url) . '">Trigger profiler</a></p>';
            }
        } else {
            $start    = App::config()->startTime();
            $end      = microtime(true);
            $duration = (int) (($end - $start) * 1000); // in milliseconds
            $res .= sprintf('<p>Page construction time (without asynchronous/secondary HTTP requests): %dms</p>', $duration);
        }

        $res .= '<p>Global vars: ' . $global_vars . '</p>' .
            '<p>Autoloader requests: ' . Autoloader::me()->getRequestsCount() .
            ' | Autoloader loads: ' . Autoloader::me()->getLoadsCount() . '</p>' .
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
        if (App::auth()->prefs()->interface->hidehelpbutton) {
            return;
        }

        $args = new ArrayObject($params);

        # --BEHAVIOR-- adminPageHelpBlock -- ArrayObject
        App::behavior()->callBehavior('adminPageHelpBlock', $args);

        if (!count($args)) {
            return;
        }

        if (empty(App::backend()->resources->entries('help'))) {
            return;
        }

        $content = '';
        foreach ($args as $arg) {
            if (is_object($arg) && isset($arg->content)) {
                $content .= $arg->content;

                continue;
            }

            $file = App::backend()->resources->entry('help', $arg);
            if (empty($file) || !file_exists($file) || !is_readable($file)) {
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
        App::backend()->resources->context(true);

        echo
        '<div id="help"><hr /><div class="help-content clear"><h3>' . __('Help about this page') . '</h3>' .
        $content .
        '</div>' .
        '<div id="helplink"><hr />' .
        '<p>' .
        sprintf(__('See also %s'), sprintf('<a href="' . App::backend()->url->get('admin.help') . '">%s</a>', __('the global help'))) .
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
        $escaped_src = Html::escapeHTML($src);
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
        $escaped_src = Html::escapeHTML($src);
        if (!isset(self::$loaded_css[$escaped_src])) {
            self::$loaded_css[$escaped_src] = true;

            return '<link rel="stylesheet" href="' . self::appendVersion($escaped_src, $version) . '" type="text/css" media="' . $media . '" />' . "\n";
        }

        return '';
    }

    /**
     * Get HTML code to load JS script
     *
     * @param      string       $src         The source
     * @param      null|string  $version     The version
     * @param      bool         $module      Load source as JS module
     *
     * @return     string
     */
    public static function jsLoad(string $src, ?string $version = '', bool $module = false): string
    {
        $escaped_src = Html::escapeHTML($src);
        if (!isset(self::$loaded_js[$escaped_src])) {
            self::$loaded_js[$escaped_src] = true;

            return '<script ' . ($module ? 'type="module" ' : '') . 'src="' . self::appendVersion($escaped_src, $version) . '"></script>' . "\n";
        }

        return '';
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
        if (App::config()->debugMode()) {
            return $src;
        }

        return $src .
            (str_contains($src, '?') ? '&amp;' : '?') .
            'v=' . (App::config()->devMode() === true ? md5(uniqid()) : ($version ?: App::config()->dotclearVersion()));
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
        return Html::jsJson($id, $vars);
    }

    /**
     * Get HTML code to load toggles JS
     *
     * @return     string
     */
    public static function jsToggles(): string
    {
        $js = [];
        if (App::auth()->prefs()->toggles) {
            $unfolded_sections = explode(',', (string) App::auth()->prefs()->toggles->unfolded_sections);
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
            'nonce' => App::nonce()->getNonce(),

            'img_plus_src' => 'images/expand.svg',
            'img_plus_txt' => '▶',
            'img_plus_alt' => __('uncover'),

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

            'module_author'  => __('Author:'),
            'module_details' => __('Details'),
            'module_support' => __('Support'),
            'module_help'    => __('Help:'),
            'module_section' => __('Section:'),
            'module_tags'    => __('Tags:'),

            'close_notice' => __('Hide this notice'),

            'show_password' => __('Show password'),
            'hide_password' => __('Hide password'),

            'set_today' => __('Reset to now'),

            'adblocker' => __('An ad blocker has been detected on this Dotclear dashboard (Ghostery, Adblock plus, uBlock origin, …) and it may interfere with some features. In this case you should disable it. Note that this detection may be disabled in your preferences.'),
        ];

        return
        self::jsLoad('js/prepend.js') .
        self::jsLoad('js/jquery/jquery.js') .
        (
            App::config()->debugMode() ?
            self::jsJson('dotclear_jquery', [
                'mute' => (!App::blog()->isDefined() || App::blog()->settings()->system->jquery_migrate_mute),
            ]) .
            self::jsLoad('js/jquery-mute.js') .
            self::jsLoad('js/jquery/jquery-migrate.js') :
            ''
        ) .

        self::jsJson('dotclear', $js) .
        self::jsJson('dotclear_msg', $js_msg) .

        self::jsLoad('js/common.js') .
        self::jsLoad('js/easter.js') .
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
        $adblockcheck = App::config()->checkAddBlocker();
        if ($adblockcheck) {
            // May not be set (auth page for example)
            if (!is_null(App::auth()->userID())) {
                $adblockcheck = App::auth()->prefs()->interface->nocheckadblocker !== true;
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
            'prompt'     => __('You have unsaved changes.'),
            'lowbattery' => __('your battery charge seems low (%d%) and you have unsaved changes, you should save them.'),
            'forms'      => $args,
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
            $base_url = Path::clean(dirname(preg_replace('/(\?.*$)?/', '', (string) $_SERVER['REQUEST_URI']))) . '/';
        }

        $params = array_merge($params, [
            'sess_id=' . session_id(),
            'sess_uid=' . $_SESSION['sess_browser_uid'],
            'xd_check=' . App::nonce()->getNonce(),
        ]);

        $js_msg = [
            'enhanced_uploader_activate' => __('Temporarily activate enhanced uploader'),
            'enhanced_uploader_disable'  => __('Temporarily disable enhanced uploader'),
        ];
        $js = [
            'msg' => [
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
        self::jsJson('filter_options', ['auto_filter' => App::auth()->prefs()->interface->auto_filter]) .
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

        /**
         * Allow 3rd party plugin to add their own textarea, the given ArrayObject should be completed with the
         * list of required modes (css, htmlmixed, javascript, php, xml, clike).
         *
         * Example:
         *
         * $tab->append('xml');
         */
        $alt = new ArrayObject();
        # --BEHAVIOR-- adminLoadCodeMirror -- array
        App::behavior()->callBehavior('adminLoadCodeMirror', $alt);
        foreach ($alt as $item) {
            if (!in_array($item, $modes)) {
                $modes[] = $item;
            }
        }

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

        /**
         * Allow 3rd party plugin to add their own textarea, the given ArrayObject should be completed with the
         * same structure (string name, string id, string mode, string theme).
         *
         * Example:
         *
         * $tab->append([
         *     'name'  => 'my_editor_css',  // Editor id (should be unique)
         *     'id'    => 'css_content',    // Textarea id
         *     'mode'  => 'css',            // Codemirror mode ()
         *     'theme' => App::auth()->prefs()->interface->colorsyntax_theme ?: 'default'
         * ]);
         */
        $alt = new ArrayObject();
        # --BEHAVIOR-- adminRunCodeMirror -- array
        App::behavior()->callBehavior('adminRunCodeMirror', $alt);
        foreach ($alt as $item) {
            $js[] = [
                'name'  => $item['name'],
                'id'    => $item['id'],
                'mode'  => $item['mode'],
                'theme' => $item['theme'] ?: 'default',
            ];
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
        $themes_root = implode(DIRECTORY_SEPARATOR, [__DIR__,  '..', '..', '..', 'admin', 'js','codemirror','theme']);
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
        return App::backend()->url->get('load.plugin.file', ['pf' => $file], '&');
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
        return App::backend()->url->get('load.var.file', ['vf' => $file], '&');
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
     * @deprecated  since 2.24, permanetly removed
     */
    public static function help()
    {
        App::deprecated()->set('', '2.24');

        # Deprecated but we keep this for plugins.
    }

    /**
     * @deprecated  since 2.16, permanetly removed
     *
     * @return     string
     */
    public static function jsColorPicker(): string
    {
        App::deprecated()->set('', '2.16');

        return '';
    }

    /**
     * Get HTML code for date picker JS utility
     *
     * @deprecated  since 2.21, permanetly removed
     *
     * @return     string
     */
    public static function jsDatePicker(): string
    {
        App::deprecated()->set('', '2.21');

        return '';
    }

    /**
     * Load jsToolBar
     *
     * @deprecated  since 2.??, permanetly removed
     *
     * @return  string
     */
    public static function jsToolBar()
    {
        App::deprecated()->set('', '2.21');

        # Deprecated but we keep this for plugins.

        return '';
    }

    /**
     * return a javascript variable definition line code
     *
     * @deprecated  since 2.15, use Page::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript instead
     *
     * @param      string  $name      variable name
     * @param      mixed   $value      value
     *
     * @return     string  javascript code
     */
    public static function jsVar(string $name, $value): string
    {
        App::deprecated()->set('Page::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript', '2.15');

        return $name . " = '" . Html::escapeJS($value) . "';\n";
    }

    /**
     * return a list of javascript variables définitions code
     *
     * @deprecated since 2.15, use Page::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript intead
     *
     * @param      array  $vars   The variables
     *
     * @return     string  javascript code (inside <script></script>)
     */
    public static function jsVars(array $vars): string
    {
        App::deprecated()->set('Page::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript', '2.15');

        $ret = '<script>' . "\n";
        foreach ($vars as $var => $value) {
            $ret .= $var . ' = ' . (is_string($value) ? "'" . Html::escapeJS($value) . "'" : $value) . ';' . "\n";
        }
        $ret .= "</script>\n";

        return $ret;
    }

    /**
     * @deprecated  since 2.11, permanetly removed
     *
     * @return     string  ( description_of_the_return_value )
     */
    public static function jsLoadIE7()
    {
        App::deprecated()->set('', '2.11', '2.27');

        return '';
    }

    /**
     * @deprecated  since 2.27, use My::cssLoad() instead
     *
     * @param      string       $src         The source
     * @param      string       $media       The media
     * @param      null|string  $version     The version
     *
     * @return     string
     */
    public static function cssModuleLoad(string $src, string $media = 'screen', ?string $version = ''): string
    {
        App::deprecated()->set('My::cssLoad()', '2.27');

        return self::cssLoad(urldecode(self::getPF($src)), $media, $version);
    }

    /**
     * @deprecated  since 2.27, use My::jsLoad() intead
     *
     * @param      string       $src         The source
     * @param      null|string  $version     The version
     * @param      bool         $module      Load source as JS module
     *
     * @return     string
     */
    public static function jsModuleLoad(string $src, ?string $version = '', bool $module = false): string
    {
        App::deprecated()->set('My::jsLoad()', '2.27');

        return self::jsLoad(urldecode(self::getPF($src)), $version, $module);
    }
}
