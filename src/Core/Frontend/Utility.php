<?php
/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * Utility class for public context.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use context;
use dcBlog;
use dcCore;
use dcMedia;
use dcThemes;
use dcUtils;
use dcTraitDynamicProperties;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Fault;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\HttpCacheStack;
use Exception;
use rsExtendPublic;

class Utility extends Process
{
    use dcTraitDynamicProperties;

    /** @var    string  The default templates folder name */
    public const TPL_ROOT = 'default-templates';

    /**
     * HTTP Cache stack
     *
     * @var HttpCacheStack;
     */
    private HttpCacheStack $cache;

    /**
     * Context
     *
     * @var context
     */
    public context $ctx;

    /**
     * Tpl instance
     *
     * @var Tpl
     */
    public Tpl $tpl;

    /**
     * Searched term
     *
     * @var string|null
     */
    public $search;

    /**
     * Searched count
     *
     * @var string
     */
    public $search_count;

    /**
     * Current theme
     *
     * @var mixed
     */
    public $theme;

    /**
     * Current theme's parent, if any
     *
     * @var mixed
     */
    public $parent_theme;

    /**
     * Smilies definitions
     *
     * @var array|null
     */
    public $smilies;

    /**
     * Current page number
     *
     * @var int
     */
    protected $page_number;

    /**
     * Constructs a new instance.
     *
     * @throws     Exception  (if not public context)
     */
    public function __construct()
    {
        if (!defined('DC_CONTEXT_PUBLIC')) {
            throw new Exception('Application is not in public context.', 500);
        }
    }

    /**
     * Prepaepre the context.
     *
     * @return     bool
     */
    public static function init(): bool
    {
        define('DC_CONTEXT_PUBLIC', true);

        return true;
    }

    /**
     * Instanciate this as a singleton and initializes the context.
     */
    public static function process(): bool
    {
        // Instanciate Frontend instance
        Core::frontend();

        // Loading blog
        if (defined('DC_BLOG_ID')) {
            try {
                Core::setBlog(DC_BLOG_ID);
            } catch (Exception $e) {
                // Loading locales for detected language
                (function () {
                    $detected_languages = Http::getAcceptLanguages();
                    foreach ($detected_languages as $language) {
                        if ($language === 'en' || L10n::set(implode(DIRECTORY_SEPARATOR, [DC_L10N_ROOT, $language, 'main'])) !== false) {
                            L10n::lang($language);

                            // We stop at first accepted language
                            break;
                        }
                    }
                })();
                new Fault(__('Database problem'), DC_DEBUG ?
            __('The following error was encountered while trying to read the database:') . '</p><ul><li>' . $e->getMessage() . '</li></ul>' :
            __('Something went wrong while trying to read the database.'), Fault::DATABASE_ISSUE);
            }
        }

        if (is_null(Core::blog()) || Core::blog()->id == null) {
            new Fault(__('Blog is not defined.'), __('Did you change your Blog ID?'), Fault::BLOG_ISSUE);
        }

        if ((int) Core::blog()->status !== dcBlog::BLOG_ONLINE) {
            Core::unsetBlog();
            new Fault(__('Blog is offline.'), __('This blog is offline. Please try again later.'), Fault::BLOG_OFFLINE);
        }

        // Load some class extents and set some public behaviors (was in public prepend before)
        rsExtendPublic::init();

        /*
         * @var        integer
         *
         * @deprecated Since 2.24
         */
        $GLOBALS['_page_number'] = 0;

        # Check blog sleep mode
        Core::blog()->checkSleepmodeTimeout();

        # Cope with static home page option
        if (Core::blog()->settings->system->static_home) {
            Core::url()->registerDefault([Url::class, 'static_home']);
        }

        # Loading media
        try {
            dcCore::app()->media = new dcMedia();
        } catch (Exception $e) {
            // Ignore
        }

        # Creating template context
        Core::frontend()->ctx = new context();
        dcCore::app()->ctx    = Core::frontend()->ctx; // deprecated

        /*
         * Template context
         *
         * @var        context
         *
         * @deprecated Since 2.23, use Core::frontend()->ctx instead
         */
        $GLOBALS['_ctx'] = Core::frontend()->ctx;

        try {
            Core::frontend()->tpl = new Tpl(DC_TPL_CACHE, 'Core::frontend()->tpl');
            dcCore::app()->tpl    = Core::frontend()->tpl; // deprecated
        } catch (Exception $e) {
            new Fault(__('Can\'t create template files.'), $e->getMessage(), Fault::TEMPLATE_CREATION_ISSUE);
        }

        # Loading locales
        Core::setLang((string) Core::blog()->settings->system->lang);

        /*
         * @var        string
         *
         * @deprecated Since 2.23, use Core::lang() instead
         */
        $GLOBALS['_lang'] = Core::lang();

        L10n::lang(Core::lang());
        if (L10n::set(DC_L10N_ROOT . '/' . Core::lang() . '/date') === false && Core::lang() != 'en') {
            L10n::set(DC_L10N_ROOT . '/en/date');
        }
        L10n::set(DC_L10N_ROOT . '/' . Core::lang() . '/public');
        L10n::set(DC_L10N_ROOT . '/' . Core::lang() . '/plugins');

        // Set lexical lang
        dcUtils::setlexicalLang('public', Core::lang());

        # Loading plugins
        try {
            Core::plugins()->loadModules(DC_PLUGINS_ROOT, 'public', Core::lang());
        } catch (Exception $e) {
            // Ignore
        }

        # Loading themes
        dcCore::app()->themes = new dcThemes();
        dcCore::app()->themes->loadModules(Core::blog()->themes_path);

        # Defining theme if not defined
        if (!isset(Core::frontend()->theme)) {
            Core::frontend()->theme = Core::blog()->settings->system->theme;
        }

        if (!dcCore::app()->themes->moduleExists(Core::frontend()->theme)) {
            Core::frontend()->theme = Core::blog()->settings->system->theme = DC_DEFAULT_THEME;
        }

        Core::frontend()->parent_theme = dcCore::app()->themes->moduleInfo(Core::frontend()->theme, 'parent');
        if (is_string(Core::frontend()->parent_theme) && !empty(Core::frontend()->parent_theme) && !dcCore::app()->themes->moduleExists(Core::frontend()->parent_theme)) {
            Core::frontend()->theme        = Core::blog()->settings->system->theme = DC_DEFAULT_THEME;
            Core::frontend()->parent_theme = null;
        }

        # If theme doesn't exist, stop everything
        if (!dcCore::app()->themes->moduleExists(Core::frontend()->theme)) {
            new Fault(__('Default theme not found.'), __('This either means you removed your default theme or set a wrong theme ' .
            'path in your blog configuration. Please check theme_path value in ' .
            'about:config module or reinstall default theme. (' . Core::frontend()->theme . ')'), Fault::THEME_ISSUE);
        }

        # Loading _public.php file for selected theme
        dcCore::app()->themes->loadNsFile(Core::frontend()->theme, 'public');

        # Loading translations for selected theme
        if (is_string(Core::frontend()->parent_theme) && !empty(Core::frontend()->parent_theme)) {
            dcCore::app()->themes->loadModuleL10N(Core::frontend()->parent_theme, Core::lang(), 'main');
        }
        dcCore::app()->themes->loadModuleL10N(Core::frontend()->theme, Core::lang(), 'main');

        # --BEHAVIOR-- publicPrepend --
        Core::behavior()->callBehavior('publicPrependV2');

        # Prepare the HTTP cache thing
        Core::frontend()->cache()->addFiles(get_included_files());
        /*
         * @var        array
         *
         * @deprecated Since 2.23, use Core::frontend()->cache()->addFiles() or Core::frontend()->cache()->getFiles() instead
         */
        $GLOBALS['mod_files'] = Core::frontend()->cache()->getFiles();

        Core::frontend()->cache()->addTime(Core::blog()->upddt);
        /*
         * @var        array
         *
         * @deprecated Since 2.23, use Core::frontend()->cache()->addTimes() or Core::frontend()->cache()->getTimes) instead
         */
        $GLOBALS['mod_ts'] = Core::frontend()->cache()->getTimes();

        $tpl_path = [
            Core::blog()->themes_path . '/' . Core::frontend()->theme . '/tpl',
        ];
        if (Core::frontend()->parent_theme) {
            $tpl_path[] = Core::blog()->themes_path . '/' . Core::frontend()->parent_theme . '/tpl';
        }
        $tplset = dcCore::app()->themes->moduleInfo(Core::blog()->settings->system->theme, 'tplset');
        $dir    = implode(DIRECTORY_SEPARATOR, [DC_ROOT, 'inc', 'public', self::TPL_ROOT, $tplset]);
        if (!empty($tplset) && is_dir($dir)) {
            Core::frontend()->tpl->setPath(
                $tpl_path,
                $dir,
                Core::frontend()->tpl->getPath()
            );
        } else {
            Core::frontend()->tpl->setPath(
                $tpl_path,
                Core::frontend()->tpl->getPath()
            );
        }
        Core::url()->mode = Core::blog()->settings->system->url_scan;

        try {
            # --BEHAVIOR-- publicBeforeDocument --
            Core::behavior()->callBehavior('publicBeforeDocumentV2');

            Core::url()->getDocument();

            # --BEHAVIOR-- publicAfterDocument --
            Core::behavior()->callBehavior('publicAfterDocumentV2');
        } catch (Exception $e) {
            new Fault($e->getMessage(), __('Something went wrong while loading template file for your blog.'), Fault::TEMPLATE_PROCESSING_ISSUE);
        }

        // Do not try to execute a process added to the URL.
        return false;
    }

    /**
     * HTTP Cache stack.
     *
     * @return      HttpCacheStack
     */
    public function cache(): HttpCacheStack
    {
        if (!isset($this->cache)) {
            $this->cache = new HttpCacheStack();
        }

        return $this->cache;
    }

    /**
     * Sets the page number.
     *
     * @param      int  $value  The value
     */
    public function setPageNumber(int $value): void
    {
        $this->page_number = $value;

        /*
         * @deprecated since 2.24, may be removed in near future
         *
         * @var int
         */
        $GLOBALS['_page_number'] = $value;
    }

    /**
     * Gets the page number.
     *
     * @return     int   The page number.
     */
    public function getPageNumber(): int
    {
        return (int) $this->page_number;
    }
}
