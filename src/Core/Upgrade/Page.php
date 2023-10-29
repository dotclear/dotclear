<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Page as BackendPage;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;

/**
 * @brief   Upgrade page helper.
 *
 * @since   2.29
 */
class Page extends BackendPage
{
    /**
     * Auth check
     *
     * @param      string  $permissions  The permissions
     * @param      bool    $home         Currently on dashboard
     */
    public static function check(string $permissions, bool $home = false): void
    {
        self::checkSuper($home);
    }

    /**
     * Check super admin
     *
     * @param      bool  $home   The home
     */
    public static function checkSuper(bool $home = false): void
    {
        if (!App::auth()->isSuperAdmin()) {
            if (session_id()) {
                App::session()->destroy();
            }
            App::upgrade()->url()->redirect('upgrade.auth');
        }
    }

    /**
     * Top of admin page
     *
     * @param      string                   $title       The title
     * @param      string                   $head        The head
     * @param      string                   $breadcrumb  The breadcrumb
     * @param      array<string, string>    $options     The options
     */
    public static function open(string $title = '', string $head = '', string $breadcrumb = '', array $options = []): void
    {
        $js = [];

        /**
         * @var        ArrayObject<string, string>
         */
        $headers = new ArrayObject();

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

        $data_theme = App::auth()->prefs()->interface->theme;

        echo
        '<!DOCTYPE html>' .
        '<html lang="' . App::auth()->getInfo('user_lang') . '" data-theme="' . $data_theme . '">' . "\n" .
        "<head>\n" .
        '  <meta charset="UTF-8" />' . "\n" .
        '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
        '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
        '  <title>' . $title . ' - ' . Html::escapeHTML(App::config()->vendorName()) . ' - ' . App::config()->dotclearVersion() . '</title>' . "\n";

        echo self::cssLoad('style/default.css');

        if ($rtl = (L10n::getLanguageTextDirection(App::lang()->getLang()) == 'rtl')) {
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
        $js['servicesUri']    = App::upgrade()->url()->get('upgrade.rest');
        $js['servicesOff']    = !App::rest()->serveRestRequests();
        $js['noDragDrop']     = (bool) App::auth()->prefs()->accessibility->nodragdrop;
        $js['debug']          = false;
        $js['showIp']         = false;

        // Set some JSON data
        echo Html::jsJson('dotclear_init', $js);

        echo
            self::jsCommon() .
            self::jsToggles() .
            $head;

        echo
        "</head>\n" .
        '<body id="dotclear-admin" class="no-js' . ($rtl ? ' rtl ' : '') . '">' . "\n" .
        '<ul id="prelude">' .
        '<li><a href="#content">' . __('Go to the content') . '</a></li>' .
        '<li><a href="#main-menu">' . __('Go to the menu') . '</a></li>' .
        '</ul>' . "\n" .
        '<header id="header" role="banner">' .
        '<h1><a href="' . App::upgrade()->url()->get('upgrade.home') . '" title="' . __('My dashboard') . '"><span class="hidden">' . App::config()->vendorName() . '</span></a></h1>' . "\n";

        echo
        '<form action="' . App::upgrade()->url()->get('upgrade.home') . '" method="post" id="top-info-blog">' .
        '</form>' .
        '<ul id="top-info-user">' .
        '<li><a class="smallscreen" href="' . App::upgrade()->url()->get('admin.home') . '">' . __('Back to normal dashboard') . '</a></li>' .
        '<li><a href="' . App::upgrade()->url()->get('upgrade.logout') . '" class="logout"><span class="nomobile">' . sprintf(__('Logout %s'), App::auth()->userID()) .
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

        // Display notices and errors
        echo Notices::getNotices();
    }

    /**
     * End of admin page
     */
    public static function close(): void
    {
        echo
        "</div>\n" .  // End of #content
        "</main>\n" . // End of #main

        '<nav id="main-menu" role="navigation">' . "\n" .

        '<form id="search-menu" action="" method="get" role="search">' .
        '</form>';

        foreach (array_keys((array) App::upgrade()->menus()) as $k) {
            echo App::upgrade()->menus()[$k]?->draw();
        }

        $text = sprintf(__('Thank you for using %s.'), 'Dotclear ' . App::config()->dotclearVersion() . '<br />(Codename: ' . App::config()->dotclearName() . ')');
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
     * Get breadcrumb
     *
     * @param      array<int|string, mixed>|null    $elements  The elements
     * @param      array<string, mixed>             $options   The options
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
            '<a class="go_home" href="' . App::upgrade()->url()->get('upgrade.home') . '">' .
            '<img class="go_home light-only" src="style/dashboard.svg" alt="' . __('Go to dashboard') . '" />' .
            '<img class="go_home dark-only" src="style/dashboard-dark.svg" alt="' . __('Go to dashboard') . '" />' .
            '</a>' :
            '<img class="go_home light-only" src="style/dashboard-alt.svg" alt="" />' .
            '<img class="go_home dark-only" src="style/dashboard-alt-dark.svg" alt="" />');

        $index = 0;
        if ($hl_pos < 0) {
            $hl_pos = count((array) $elements) + $hl_pos;
        }
        foreach ((array) $elements as $element => $url) {
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
     * Appends a version to force cache refresh if necessary.
     *
     * @param      string       $src         The source
     * @param      null|string  $version     The version
     *
     * @return     string
     */
    protected static function appendVersion(string $src, ?string $version = ''): string
    {
        return $src .
            (str_contains($src, '?') ? '&amp;' : '?') .
            'v=' . (App::config()->devMode() === true ? md5(uniqid()) : ($version ?: App::config()->dotclearVersion()));
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
        return App::upgrade()->url()->get('load.plugin.file', ['pf' => $file], '&');
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
        return App::upgrade()->url()->get('load.var.file', ['vf' => $file], '&');
    }
}
