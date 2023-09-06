<?php
/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use dcCore;
use Dotclear\App;
use Dotclear\Core\Utils;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Fault;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\HttpCacheStack;
use Dotclear\Helper\TraitDynamicProperties;
use Dotclear\Schema\Extension\CommentPublic;
use Dotclear\Schema\Extension\PostPublic;
use Exception;

/**
 * Utility class for public context.
 */
class Utility extends Process
{
    use TraitDynamicProperties;

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
     * @var Ctx
     */
    public Ctx $ctx;

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
     * Prepare the context.
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
        App::frontend();

        // Loading blog
        if (defined('DC_BLOG_ID')) {
            try {
                App::blogLoader()->setBlog(DC_BLOG_ID);
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

        if (!App::blog()->isDefined()) {
            new Fault(__('Blog is not defined.'), __('Did you change your Blog ID?'), Fault::BLOG_ISSUE);
        }

        if ((int) App::blog()->status() !== App::blog()::BLOG_ONLINE) {
            App::blogLoader()->unsetBlog();
            new Fault(__('Blog is offline.'), __('This blog is offline. Please try again later.'), Fault::BLOG_OFFLINE);
        }

        // Load some class extents and set some public behaviors (was in public prepend before)
        App::behavior()->addBehaviors([
            'publicHeadContent' => function () {
                if (!App::blog()->settings()->system->no_public_css) {
                    echo Utils::cssLoad(App::blog()->getQmarkURL() . 'pf=public.css');
                }
                if (App::blog()->settings()->system->use_smilies) {
                    echo Utils::cssLoad(App::blog()->getQmarkURL() . 'pf=smilies.css');
                }
            },
            'coreBlogGetPosts' => function (MetaRecord $rs) {
                $rs->extend(PostPublic::class);
            },
            'coreBlogGetComments' => function (MetaRecord $rs) {
                $rs->extend(CommentPublic::class);
            },
        ]);

        /*
         * @var        integer
         *
         * @deprecated Since 2.24
         */
        $GLOBALS['_page_number'] = 0;

        # Check blog sleep mode
        App::blog()->checkSleepmodeTimeout();

        # Cope with static home page option
        if (App::blog()->settings()->system->static_home) {
            App::url()->registerDefault(Url::static_home(...));
        }

        // deprecated since 2.28, use App::media() instead
        dcCore::app()->media = App::media();

        # Creating template context
        App::frontend()->ctx = new Ctx();

        // deprecated since 2.28, use App::frontend()->ctx instead
        dcCore::app()->ctx = App::frontend()->ctx;

        // deprecated since 2.23, use App::frontend()->ctx instead
        $GLOBALS['_ctx'] = App::frontend()->ctx;

        try {
            App::frontend()->tpl = new Tpl(DC_TPL_CACHE, 'App::frontend()->tpl');

            // deprecated since 2.28, use App::frontend()->tpl instead
            dcCore::app()->tpl = App::frontend()->tpl;
        } catch (Exception $e) {
            new Fault(__('Can\'t create template files.'), $e->getMessage(), Fault::TEMPLATE_CREATION_ISSUE);
        }

        # Loading locales
        App::setLang((string) App::blog()->settings()->system->lang);

        // deprecated since 2.23, use App::lang() instead
        $GLOBALS['_lang'] = App::lang();

        L10n::lang(App::lang());
        if (L10n::set(DC_L10N_ROOT . '/' . App::lang() . '/date') === false && App::lang() != 'en') {
            L10n::set(DC_L10N_ROOT . '/en/date');
        }
        L10n::set(DC_L10N_ROOT . '/' . App::lang() . '/public');
        L10n::set(DC_L10N_ROOT . '/' . App::lang() . '/plugins');

        // Set lexical lang
        Utils::setlexicalLang('public', App::lang());

        # Loading plugins
        try {
            App::plugins()->loadModules(DC_PLUGINS_ROOT, 'public', App::lang());
        } catch (Exception $e) {
            // Ignore
        }

        // deprecated since 2.28, use App::themes() instead
        dcCore::app()->themes = App::themes();

        # Loading themes
        App::themes()->loadModules(App::blog()->themesPath());

        # Defining theme if not defined
        if (!isset(App::frontend()->theme)) {
            App::frontend()->theme = App::blog()->settings()->system->theme;
        }

        if (!App::themes()->moduleExists(App::frontend()->theme)) {
            App::frontend()->theme = App::blog()->settings()->system->theme = DC_DEFAULT_THEME;
        }

        App::frontend()->parent_theme = App::themes()->moduleInfo(App::frontend()->theme, 'parent');
        if (is_string(App::frontend()->parent_theme) && !empty(App::frontend()->parent_theme) && !App::themes()->moduleExists(App::frontend()->parent_theme)) {
            App::frontend()->theme        = App::blog()->settings()->system->theme = DC_DEFAULT_THEME;
            App::frontend()->parent_theme = null;
        }

        # If theme doesn't exist, stop everything
        if (!App::themes()->moduleExists(App::frontend()->theme)) {
            new Fault(__('Default theme not found.'), __('This either means you removed your default theme or set a wrong theme ' .
            'path in your blog configuration. Please check theme_path value in ' .
            'about:config module or reinstall default theme. (' . App::frontend()->theme . ')'), Fault::THEME_ISSUE);
        }

        # Loading _public.php file for selected theme
        App::themes()->loadNsFile(App::frontend()->theme, 'public');

        # Loading translations for selected theme
        if (is_string(App::frontend()->parent_theme) && !empty(App::frontend()->parent_theme)) {
            App::themes()->loadModuleL10N(App::frontend()->parent_theme, App::lang(), 'main');
        }
        App::themes()->loadModuleL10N(App::frontend()->theme, App::lang(), 'main');

        # --BEHAVIOR-- publicPrepend --
        App::behavior()->callBehavior('publicPrependV2');

        # Prepare the HTTP cache thing
        App::frontend()->cache()->addFiles(get_included_files());
        App::frontend()->cache()->addTime(App::blog()->upddt());

        // deprecated Since 2.23, use App::frontend()->cache()->addFiles() or App::frontend()->cache()->getFiles() instead
        $GLOBALS['mod_files'] = App::frontend()->cache()->getFiles();

        // deprecated Since 2.23, use App::frontend()->cache()->addTimes() or App::frontend()->cache()->getTimes) instead
        $GLOBALS['mod_ts'] = App::frontend()->cache()->getTimes();

        $tpl_path = [
            App::blog()->themesPath() . '/' . App::frontend()->theme . '/tpl',
        ];
        if (App::frontend()->parent_theme) {
            $tpl_path[] = App::blog()->themesPath() . '/' . App::frontend()->parent_theme . '/tpl';
        }
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
        $dir    = implode(DIRECTORY_SEPARATOR, [DC_ROOT, 'inc', 'public', self::TPL_ROOT, $tplset]);
        if (!empty($tplset) && is_dir($dir)) {
            App::frontend()->tpl->setPath(
                $tpl_path,
                $dir,
                App::frontend()->tpl->getPath()
            );
        } else {
            App::frontend()->tpl->setPath(
                $tpl_path,
                App::frontend()->tpl->getPath()
            );
        }
        App::url()->mode = App::blog()->settings()->system->url_scan;

        try {
            # --BEHAVIOR-- publicBeforeDocument --
            App::behavior()->callBehavior('publicBeforeDocumentV2');

            App::url()->getDocument();

            # --BEHAVIOR-- publicAfterDocument --
            App::behavior()->callBehavior('publicAfterDocumentV2');
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
