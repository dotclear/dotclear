<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Page as BackendPage;
use Dotclear\Helper\Html\Form\Btn;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Single;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
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
     * Auth check.
     *
     * @param   string  $permissions    Permissions
     * @param   bool    $home           Currently on dashboard
     */
    public static function check(string $permissions, bool $home = false): void
    {
        self::checkSuper($home);
    }

    /**
     * Check super admin.
     *
     * @param   bool    $home   The home
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
     * Top of upgrade page.
     *
     * @param   string                  $title          The title
     * @param   string                  $head           The head
     * @param   string                  $breadcrumb     The breadcrumb
     * @param   array<string, string>   $options        The options
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
        '<meta charset="UTF-8">' . "\n" .
        '<meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW">' . "\n" .
        '<meta name="GOOGLEBOT" content="NOSNIPPET">' . "\n" .
        '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n" .
        '<title>' . $title . ' - ' . Html::escapeHTML(App::config()->vendorName()) . ' - ' . App::config()->dotclearVersion() . '</title>' . "\n";

        echo self::cssLoad('style/default.css');

        if ($rtl = (L10n::getLanguageTextDirection(App::lang()->getLang()) === 'rtl')) {
            echo self::cssLoad('style/default-rtl.css');
        }

        if (!App::auth()->prefs()->interface->hide_std_favicon) {
            echo
            '<link rel="icon" type="image/png" href="images/favicon96-login.png">' . "\n" .
            '<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">' . "\n";
        }
        if (App::auth()->prefs()->interface->htmlfontsize) {
            $js['htmlFontSize'] = App::auth()->prefs()->interface->htmlfontsize;
        }
        if (App::auth()->prefs()->interface->systemfont) {
            $js['systemFont'] = true;
        }
        $js['hideMoreInfo']    = (bool) App::auth()->prefs()->interface->hidemoreinfo;
        $js['quickMenuPrefix'] = (string) App::auth()->prefs()->interface->quickmenuprefix;

        $js['servicesUri'] = App::upgrade()->url()->get('admin.rest');
        $js['servicesOff'] = !App::rest()->serveRestRequests();

        $js['noDragDrop'] = (bool) App::auth()->prefs()->accessibility->nodragdrop;

        $js['debug']  = App::config()->debugMode();
        $js['showIp'] = false;

        // Set some JSON data
        echo Html::jsJson('dotclear_init', $js);

        echo
        self::jsCommon() .
        self::jsToggles() .
        $head;

        $prelude = (new Ul())
            ->id('prelude')
            ->items([
                (new Li())
                    ->items([
                        (new Link())->href('#content')->text(__('Go to the content')),
                    ]),
                (new Li())
                    ->items([
                        (new Link())->href('#main-menu')->text(__('Go to the menu')),
                    ]),
                (new Li())
                    ->items([
                        (new Link())->href('#help')->text(__('Go to help')),
                    ]),
            ]);

        echo
        "</head>\n";

        echo
        '<body id="dotclear-admin" class="upgrade-mode no-js' . ($rtl ? ' rtl ' : '') . '">' . "\n" .
        $prelude->render() . "\n";

        // Header
        echo (new Div(null, 'header'))
            ->id('header')
            ->extra('role="banner"')
            ->items([
                (new Text('h1', (new Link())
                        ->href(App::upgrade()->url()->get('upgrade.home'))
                        ->title(__('My dashboard'))
                        ->items([
                            (new Text('span', App::config()->vendorName()))->class('hidden'),
                        ])
                    ->render())),
                (new Div())
                    ->id('top-info-blog')
                    ->items([
                        (new Para())
                            ->items([
                                (new Text('strong', __("Dotclear's update dashboard"))),
                            ]),
                    ]),
                (new Ul())
                    ->id('top-info-user')
                    ->items([
                        (new Li())
                            ->items([
                                (new Link())
                                    ->class('smallscreen')
                                    ->href(App::upgrade()->url()->get('admin.home'))
                                    ->text(__('Go to normal dashboard')),
                            ]),
                        (new Li())
                            ->items([
                                (new Link())
                                    ->class('logout')
                                    ->href(App::upgrade()->url()->get('upgrade.logout'))
                                    ->items([
                                        (new Text('span', sprintf(__('Logout %s'), App::auth()->userID())))
                                            ->class('nomobile'),
                                        (new Img('images/logout.svg'))
                                            ->alt(''),
                                    ]),
                            ]),
                    ]),
            ])
        ->render();

        $expander = (new Div())
            ->class(['hidden-if-no-js', 'collapser-box'])
            ->items([
                (new Btn())
                    ->type('button')
                    ->id('collapser')
                    ->class('void-btn')
                    ->text((new Set())
                            ->items([
                                (new Img('images/hide.svg'))
                                    ->class(['collapse-mm', 'visually-hidden'])
                                    ->alt(__('Hide main menu')),
                                (new Img('images/expand.svg'))
                                    ->class(['expand-mm', 'visually-hidden'])
                                    ->alt(__('Show main menu')),
                            ])
                        ->render()),
            ]);

        echo
        '<div id="wrapper" class="clearfix">' . "\n" .
        $expander->render() .
        '<main id="main" role="main">' . "\n" .
        '<div id="content" class="clearfix">' . "\n";

        // Display breadcrumb (if given) before any error messages
        echo $breadcrumb;

        // Display notices and errors
        echo Notices::getNotices();
    }

    /**
     * End of admin page.
     */
    public static function close(): void
    {
        echo
        "</div>\n" .  // End of #content
        "</main>\n" . // End of #main

        '<nav id="main-menu" role="navigation">' . "\n";

        foreach (array_keys((array) App::upgrade()->menus()) as $k) {
            echo App::upgrade()->menus()[$k]?->draw();
        }

        $text = sprintf(__('Thank you for using %s.'), 'Dotclear ' . App::config()->dotclearVersion() . '<br>(Codename: ' . App::config()->dotclearName() . ')');
        $text = Html::escapeHTML($text);

        $gototop = (new Para())
            ->id('gototop')
            ->items([
                (new Link())
                    ->href('#wrapper')
                    ->items([
                        (new Img('images/up.svg'))
                            ->alt(__('Page top'))
                            ->extra('aria-hidden="true"'),
                        (new Text('span', __('Page top')))
                            ->class('visually-hidden'),
                    ]),
            ]);

        echo
        "</nav>\n" . // End of #main-menu
        "</div>\n";  // End of #wrapper
        $gototop->render();

        $figure = "\n" .
        ' ' . "\n" .
        'ᓚᘏᗢ' . "\n";

        $logo = (new Link())
            ->href('https://dotclear.org/')
            ->title($text)
            ->items([
                (new Img('style/dc_logos/dotclear-light.svg'))
                    ->class('light-only')
                    ->alt(__('Dotclear logo')),
                (new Img('style/dc_logos/dotclear-dark.svg'))
                    ->class('dark-only')
                    ->alt(__('Dotclear logo')),
            ]);

        echo
        '<footer id="footer" role="contentinfo">' .
        $logo->render() .
        '</footer>' . "\n" .
        '<!-- ' . "\n" .
        $figure .
        ' -->' . "\n";

        if (App::config()->devMode()) {
            echo self::debugInfo();
        }

        echo
        '</body></html>';
    }

    /**
     * Get breadcrumb.
     *
     * @param   array<int|string, mixed>|null   $elements   The elements
     * @param   array<string, mixed>            $options    The options
     */
    public static function breadcrumb(?array $elements = null, array $options = []): string
    {
        $with_home_link = $options['home_link'] ?? true;
        $hl             = $options['hl']        ?? true;
        $hl_pos         = $options['hl_pos']    ?? -1;

        // First item of array elements should be blog's name, System or Plugins
        $home = $with_home_link ?
        (new Link())
            ->class('go_home')
            ->href(App::upgrade()->url()->get('upgrade.home'))
            ->items([
                (new Img('style/dashboard.svg'))
                    ->class(['go_home', 'light-only'])
                    ->alt(__('Go to dashboard')),
                (new Img('style/dashboard-dark.svg'))
                    ->class(['go_home', 'dark-only'])
                    ->alt(__('Go to dashboard')),
            ]) :
        (new Set())
            ->items([
                (new Img('style/dashboard-alt.svg'))
                    ->class(['go_home', 'light-only']),
                (new Img('style/dashboard-alt-dark.svg'))
                    ->class(['go_home', 'dark-only']),
            ])
        ;

        // Next items
        $links = [];
        $index = 0;
        if ($hl_pos < 0) {
            $hl_pos = count((array) $elements) + $hl_pos;
        }
        foreach ((array) $elements as $element => $url) {
            if ($hl && $index === $hl_pos) {
                $label = (new Text('span', (string) $element))
                    ->class('page-title')
                    ->extra('aria-current="location"');
            } else {
                $label = (new Text(null, (string) $element));
            }
            $links[] = $url ?
            (new Link())
                ->href($url)
                ->items([$label]) :
            (new Set())
                ->items([$label])
            ;
            $index++;
        }

        // Each items (but home) are separated by > (&rsaquo)
        $next = (new Set())
            ->separator(' &rsaquo; ')
            ->items($links);

        // Home and other items are separated by :
        $breadcrumb = (new Div(null, 'h2'))
            ->extra('role="navigation"')
            ->separator(' : ')
            ->items([
                $home,
                $next,
            ])
        ->render();

        return $breadcrumb;
    }

    /**
     * Display Help block.
     *
     * @param   mixed   ...$params  The parameters
     */
    public static function helpBlock(...$params): void
    {
        if (App::auth()->prefs()->interface->hidehelpbutton) {
            return;
        }

        $args = new ArrayObject($params);

        if (count($args) === 0) {
            return;
        }

        if (App::upgrade()->resources()->entries('help') === []) {
            return;
        }

        $content = '';
        foreach ($args as $arg) {
            if (is_object($arg) && isset($arg->content)) {
                $content .= $arg->content;

                continue;
            }

            $file = App::upgrade()->resources()->entry('help', $arg);
            if ($file === '' || !file_exists($file) || !is_readable($file)) {
                continue;
            }

            $file_content = (string) file_get_contents($file);
            if (preg_match('|<body[^>]*?>(.*?)</body>|ms', $file_content, $matches)) {
                $content .= $matches[1];
            } else {
                $content .= $file_content;
            }
        }

        if (trim($content) === '') {
            return;
        }

        // Set contextual help global flag
        App::upgrade()->resources()->context(true);

        echo (new Div())
            ->id('help')
            ->items([
                (new Single('hr')),
                (new Div())
                    ->class(['help-content', 'clear'])
                    ->items([
                        (new Text('h3', __('Help about this page'))),
                        (new Text(null, $content)),
                    ]),
                (new Div())
                    ->id('helplink')
                    ->items([
                        (new Single('hr')),
                        (new Note())
                            ->text(sprintf(
                                __('See also %s'),
                                sprintf(
                                    (new Link())->href(App::upgrade()->url()->get('upgrade.home'))->text('%s')->render(),
                                    __('the global help')
                                )
                            ) . '.'),
                    ]),
            ])
        ->render();
    }

    /**
     * Appends a version to force cache refresh if necessary.
     *
     * @param   string          $src        The source
     * @param   null|string     $version    The version
     */
    protected static function appendVersion(string $src, ?string $version = ''): string
    {
        return $src .
            (str_contains($src, '?') ? '&amp;' : '?') .
            'v=' . (App::config()->devMode() ? md5(uniqid()) : ($version ?: App::config()->dotclearVersion()));
    }

    /**
     * Gets plugin file.
     *
     * @param   string  $file   The filename
     *
     * @return  string  The URL.
     */
    public static function getPF(string $file): string
    {
        return App::upgrade()->url()->get('load.plugin.file', ['pf' => $file], '&');
    }

    /**
     * Gets var file.
     *
     * @param   string  $file   The filename
     *
     * @return  string  The URL.
     */
    public static function getVF(string $file): string
    {
        return App::upgrade()->url()->get('load.var.file', ['vf' => $file], '&');
    }
}
