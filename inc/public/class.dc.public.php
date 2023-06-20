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

use Dotclear\Fault;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;

class dcPublic
{
    use dcTraitDynamicProperties;

    // Constants

    public const TPL_ROOT = 'default-templates';

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
     * Instanciate this as a singleton
     *
     * @return     self
     */
    public static function bootstrap(): self
    {
        if (!(dcCore::app()->public instanceof self)) {
            // Init singleton
            dcCore::app()->public = new self();
        }

        return dcCore::app()->public;
    }

    /**
     * Initializes the context.
     */
    public function init(): bool
    {
        // Loading blog
        if (defined('DC_BLOG_ID')) {
            try {
                dcCore::app()->setBlog(DC_BLOG_ID);
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

        if (dcCore::app()->blog->id == null) {
            new Fault(__('Blog is not defined.'), __('Did you change your Blog ID?'), Fault::BLOG_ISSUE);
        }

        if ((int) dcCore::app()->blog->status !== dcBlog::BLOG_ONLINE) {
            dcCore::app()->unsetBlog();
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

        # Prepare for further notices, if any
        dcCore::app()->notices = new dcNotices();

        # Check blog sleep mode
        dcCore::app()->blog->checkSleepmodeTimeout();

        # Cope with static home page option
        if (dcCore::app()->blog->settings->system->static_home) {
            dcCore::app()->url->registerDefault([dcUrlHandlers::class, 'static_home']);
        }

        # Loading media
        try {
            dcCore::app()->media = new dcMedia();
        } catch (Exception $e) {
            // Ignore
        }

        # Creating template context
        dcCore::app()->ctx = new context();

        /*
         * Template context
         *
         * @var        context
         *
         * @deprecated Since 2.23, use dcCore::app()->ctx instead
         */
        $GLOBALS['_ctx'] = dcCore::app()->ctx;

        try {
            dcCore::app()->tpl = new dcTemplate(DC_TPL_CACHE, 'dcCore::app()->tpl');
        } catch (Exception $e) {
            new Fault(__('Can\'t create template files.'), $e->getMessage(), Fault::TEMPLATE_CREATION_ISSUE);
        }

        # Loading locales
        dcCore::app()->lang = (string) dcCore::app()->blog->settings->system->lang;
        dcCore::app()->lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', dcCore::app()->lang) ? dcCore::app()->lang : 'en';

        /*
         * @var        string
         *
         * @deprecated Since 2.23, use dcCore::app()->lang instead
         */
        $GLOBALS['_lang'] = &dcCore::app()->lang;

        L10n::lang(dcCore::app()->lang);
        if (L10n::set(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/date') === false && dcCore::app()->lang != 'en') {
            L10n::set(DC_L10N_ROOT . '/en/date');
        }
        L10n::set(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/public');
        L10n::set(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/plugins');

        // Set lexical lang
        dcUtils::setlexicalLang('public', dcCore::app()->lang);

        # Loading plugins
        try {
            dcCore::app()->plugins->loadModules(DC_PLUGINS_ROOT, 'public', dcCore::app()->lang);
        } catch (Exception $e) {
            // Ignore
        }

        # Loading themes
        dcCore::app()->themes = new dcThemes();
        dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path);

        # Defining theme if not defined
        if (!isset($this->theme)) {
            $this->theme = dcCore::app()->blog->settings->system->theme;
        }

        if (!dcCore::app()->themes->moduleExists($this->theme)) {
            $this->theme = dcCore::app()->blog->settings->system->theme = DC_DEFAULT_THEME;
        }

        $this->parent_theme = dcCore::app()->themes->moduleInfo($this->theme, 'parent');
        if ($this->parent_theme && !dcCore::app()->themes->moduleExists($this->parent_theme)) {
            $this->theme        = dcCore::app()->blog->settings->system->theme = DC_DEFAULT_THEME;
            $this->parent_theme = null;
        }

        # If theme doesn't exist, stop everything
        if (!dcCore::app()->themes->moduleExists($this->theme)) {
            new Fault(__('Default theme not found.'), __('This either means you removed your default theme or set a wrong theme ' .
            'path in your blog configuration. Please check theme_path value in ' .
            'about:config module or reinstall default theme. (' . dcCore::app()->public->theme . ')'), Fault::THEME_ISSUE);
        }

        # Loading _public.php file for selected theme
        dcCore::app()->themes->loadNsFile($this->theme, 'public');

        # Loading translations for selected theme
        if ($this->parent_theme) {
            dcCore::app()->themes->loadModuleL10N($this->parent_theme, dcCore::app()->lang, 'main');
        }
        dcCore::app()->themes->loadModuleL10N($this->theme, dcCore::app()->lang, 'main');

        # --BEHAVIOR-- publicPrepend --
        dcCore::app()->callBehavior('publicPrependV2');

        # Prepare the HTTP cache thing
        dcCore::app()->cache['mod_files'] = get_included_files();
        /*
         * @var        array
         *
         * @deprecated Since 2.23, use dcCore::app()->cache['mod_files'] instead
         */
        $GLOBALS['mod_files'] = dcCore::app()->cache['mod_files'];

        dcCore::app()->cache['mod_ts']   = [];
        dcCore::app()->cache['mod_ts'][] = dcCore::app()->blog->upddt;
        /*
         * @var        array
         *
         * @deprecated Since 2.23, use dcCore::app()->cache['mod_ts'] instead
         */
        $GLOBALS['mod_ts'] = dcCore::app()->cache['mod_ts'];

        $tpl_path = [
            dcCore::app()->blog->themes_path . '/' . $this->theme . '/tpl',
        ];
        if ($this->parent_theme) {
            $tpl_path[] = dcCore::app()->blog->themes_path . '/' . $this->parent_theme . '/tpl';
        }
        $tplset = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'tplset');
        if (!empty($tplset) && is_dir(__DIR__ . DIRECTORY_SEPARATOR . self::TPL_ROOT . DIRECTORY_SEPARATOR . $tplset)) {
            dcCore::app()->tpl->setPath(
                $tpl_path,
                __DIR__ . DIRECTORY_SEPARATOR . self::TPL_ROOT . DIRECTORY_SEPARATOR . $tplset,
                dcCore::app()->tpl->getPath()
            );
        } else {
            dcCore::app()->tpl->setPath(
                $tpl_path,
                dcCore::app()->tpl->getPath()
            );
        }
        dcCore::app()->url->mode = dcCore::app()->blog->settings->system->url_scan;

        try {
            # --BEHAVIOR-- publicBeforeDocument --
            dcCore::app()->callBehavior('publicBeforeDocumentV2');

            dcCore::app()->url->getDocument();

            # --BEHAVIOR-- publicAfterDocument --
            dcCore::app()->callBehavior('publicAfterDocumentV2');
        } catch (Exception $e) {
            new Fault($e->getMessage(), __('Something went wrong while loading template file for your blog.'), Fault::TEMPLATE_PROCESSING_ISSUE);
        }

        return true;
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
