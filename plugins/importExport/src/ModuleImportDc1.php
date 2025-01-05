<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Crypt;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text as Txt;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Plugin\blogroll\Blogroll;
use Exception;

/**
 * @brief   The DC1 import module handler.
 * @ingroup importExport
 */
class ModuleImportDc1 extends Module
{
    protected ConnectionInterface $con;
    protected string $prefix;
    protected string $blog_id;

    protected ?string $action = null;
    protected int $step       = 1;

    protected int $post_offset = 0;
    protected int $post_limit  = 20;
    protected int $post_count  = 0;

    /**
     * @var array<string, bool>
     */
    protected array $has_table = [];

    /**
     * @var array<string, mixed>
     */
    protected $vars;

    /**
     * @var array<string, mixed>
     */
    protected $base_vars = [
        'db_driver'  => 'mysqli',
        'db_host'    => '',
        'db_name'    => '',
        'db_user'    => '',
        'db_pwd'     => '',
        'db_prefix'  => 'dc_',
        'post_limit' => 20,
        'cat_ids'    => [],
    ];

    protected function setInfo(): void
    {
        $this->type        = 'import';
        $this->name        = __('Dotclear 1.2 import');
        $this->description = __('Import a Dotclear 1.2 installation into your current blog.');
    }

    public function init(): void
    {
        $this->con     = App::con();
        $this->prefix  = App::con()->prefix();
        $this->blog_id = App::blog()->id();

        if (!isset($_SESSION['dc1_import_vars'])) {
            $_SESSION['dc1_import_vars'] = $this->base_vars;
        }
        $this->vars = &$_SESSION['dc1_import_vars'];

        if ($this->vars['post_limit'] > 0) {
            $this->post_limit = $this->vars['post_limit'];
        }
    }

    public function resetVars(): void
    {
        $this->vars = $this->base_vars;
        unset($_SESSION['dc1_import_vars']);
    }

    public function process(string $do): void
    {
        $this->action = $do;
    }

    /**
     * Processes the import/export with GUI feedback.
     *
     * We handle process in another way to always display something to user
     *
     * @param   string  $do     action
     */
    protected function guiprocess(string $do): void
    {
        switch ($do) {
            case 'step1':
                $this->vars['db_driver']  = $_POST['db_driver'];
                $this->vars['db_host']    = $_POST['db_host'];
                $this->vars['db_name']    = $_POST['db_name'];
                $this->vars['db_user']    = $_POST['db_user'];
                $this->vars['db_pwd']     = $_POST['db_pwd'];
                $this->vars['post_limit'] = abs((int) $_POST['post_limit']) > 0 ? $_POST['post_limit'] : 0;
                $this->vars['db_prefix']  = $_POST['db_prefix'];
                $db                       = $this->db();
                $db->close();
                $this->step = 2;
                echo $this->progressBar(1);

                break;
            case 'step2':
                $this->step = 2;
                $this->importUsers();
                $this->step = 3;
                echo $this->progressBar(3);

                break;
            case 'step3':
                $this->step = 3;
                $this->importCategories();
                if (App::plugins()->moduleExists('blogroll')) {
                    $this->step = 4;
                    echo $this->progressBar(5);
                } else {
                    $this->step = 5;
                    echo $this->progressBar(7);
                }

                break;
            case 'step4':
                $this->step = 4;
                $this->importLinks();
                $this->step = 5;
                echo $this->progressBar(7);

                break;
            case 'step5':
                $this->step        = 5;
                $this->post_offset = empty($_REQUEST['offset']) ? 0 : abs((int) $_REQUEST['offset']);
                $percent           = 0;
                if ($this->importPosts($percent) === -1) {
                    Http::redirect($this->getURL() . '&do=ok');
                } else {
                    echo $this->progressBar(ceil($percent * 0.93) + 7);
                }

                break;
            case 'ok':
                $this->resetVars();
                App::blog()->triggerBlog();
                $this->step = 6;
                echo $this->progressBar(100);

                break;
        }
    }

    public function gui(): void
    {
        try {
            $this->guiprocess((string) $this->action);
        } catch (Exception $e) {
            $this->error($e);
        }

        # db drivers
        $db_drivers = [
            'mysqli' => 'mysqli',
        ];

        switch ($this->step) {
            case 1:
                echo (new Para())
                    ->items([
                        (new Text(null, sprintf(
                            __('Import the content of a Dotclear 1.2\'s blog in the current blog: %s.'),
                            '<strong>' . Html::escapeHTML(App::blog()->name()) . '</strong>'
                        ))),
                    ])
                ->render();

                echo (new Note())
                    ->class('warning')
                    ->text(__('Please note that this process will empty your categories, blogroll, entries and comments on the current blog.'))
                ->render();

                $text = (new Set())
                    ->items([
                        (new Para())
                            ->items([
                                (new Text(null, __('We first need some information about your old Dotclear 1.2 installation.'))),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('db_driver'))
                                    ->items($db_drivers)
                                    ->default(Html::escapeHTML($this->vars['db_driver']))
                                    ->label((new Label(__('Database driver:'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('db_host'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($this->vars['db_host']))
                                    ->label((new Label(__('Database Host Name:'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('db_name'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($this->vars['db_name']))
                                    ->label((new Label(__('Database Name:'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('db_user'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($this->vars['db_user']))
                                    ->label((new Label(__('Database User Name:'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                        (new Para())
                            ->items([
                                (new Password('db_pwd'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($this->vars['db_pwd']))
                                    ->label((new Label(__('Database Password:'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('db_prefix'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML($this->vars['db_prefix']))
                                    ->label((new Label(__('Database Tables Prefix:'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                        (new Text('h3', __('Entries import options')))->class('vertical-separator'),
                        (new Para())
                            ->items([
                                (new Number('post_limit', 0, 999))
                                    ->value(Html::escapeHTML((string) $this->vars['post_limit']))
                                    ->label((new Label(__('Number of entries to import at once:'), Label::INSIDE_TEXT_BEFORE))),
                            ]),
                    ])
                ->render();

                printf(
                    $this->imForm(1, __('General information'), __('Import my blog now')),
                    $text
                );

                break;
            case 2:
                printf(
                    $this->imForm(2, __('Importing users')),
                    $this->autoSubmit()
                );

                break;
            case 3:
                printf(
                    $this->imForm(3, __('Importing categories')),
                    $this->autoSubmit()
                );

                break;
            case 4:
                printf(
                    $this->imForm(4, __('Importing blogroll')),
                    $this->autoSubmit()
                );

                break;
            case 5:
                $text = sprintf(
                    __('Importing entries from %d to %d / %d'),
                    $this->post_offset,
                    min([$this->post_offset + $this->post_limit, $this->post_count]),
                    $this->post_count
                );
                printf(
                    $this->imForm(5, $text),
                    (new Hidden(['offset'], (string) $this->post_offset))->render() .
                    $this->autoSubmit()
                );

                break;
            case 6:
                echo (new Set())
                    ->items([
                        (new Text('h3', __('Please read carefully')))->class('vertical-separator'),
                        (new Ul())->items([
                            (new Li())->text(__('Every newly imported user has received a random password and will need to ask for a new one by following the "I forgot my password" link on the login page (Their registered email address has to be valid.)')),
                            (new Li())->text(sprintf(
                                __('Please note that Dotclear 2 has a new URL layout. You can avoid broken links by installing <a href="%s">DC1 redirect</a> plugin and activate it in your blog configuration.'),
                                'https://plugins.dotaddict.org/dc2/details/dc1redirect'
                            )),
                        ]),
                    ])
                ->render();

                $this->congratMessage();

                break;
        }
    }

    /**
     * Return a simple form for step by step process.
     *
     * @param   int     $step           The step
     * @param   string  $legend         The legend
     * @param   string  $submit_value   The submit value
     */
    protected function imForm(int $step, string $legend, ?string $submit_value = null): string
    {
        if (!$submit_value) {
            $submit_value = __('next step') . ' >';
        }

        return (new Form('im-form'))
            ->method('post')
            ->action($this->getURL(true))
            ->fields([
                (new Text('h3', $legend))->class('vertical-separator'),
                ...My::hiddenFields(),
                (new Div())->items([
                    (new Hidden(['do'], 'step' . $step)),
                    (new Text(null, '%s')),
                ]),
                (new Para())->class('vertical-separator')->items([
                    (new Submit('im-form-submit', $submit_value)),
                ]),
                (new Note())
                    ->class(['form-note', 'info'])
                    ->text(__('Depending on the size of your blog, it could take a few minutes.')),
            ])
        ->render();
    }

    /**
     * Error display.
     *
     * @param   Exception   $e  The error
     */
    protected function error(Exception $e): void
    {
        Notices::error('<strong>' . __('Errors:') . '</strong>' . '<p>' . $e->getMessage() . '</p>', false, false);
    }

    /**
     * DB init.
     *
     * @throws  Exception
     *
     * @return  mixed
     */
    protected function db()
    {
        $db = App::newConnectionFromValues($this->vars['db_driver'], $this->vars['db_host'], $this->vars['db_name'], $this->vars['db_user'], $this->vars['db_pwd']);

        $rs = $db->select("SHOW TABLES LIKE '" . $this->vars['db_prefix'] . "%'");
        if ($rs->isEmpty()) {
            throw new Exception(__('Dotclear tables not found'));
        }

        while ($rs->fetch()) {
            $this->has_table[(string) $rs->f(0)] = true;
        }

        // Set this to read data as they were written in Dotclear 1
        try {
            $db->execute('SET NAMES DEFAULT');
        } catch (Exception) {
        }

        $db->execute('SET CHARACTER SET DEFAULT');
        $db->execute('SET COLLATION_CONNECTION = DEFAULT');
        $db->execute('SET COLLATION_SERVER = DEFAULT');
        $db->execute('SET CHARACTER_SET_SERVER = DEFAULT');
        $db->execute('SET CHARACTER_SET_DATABASE = DEFAULT');

        $this->post_count = $db->select(
            'SELECT COUNT(post_id) FROM ' . $this->vars['db_prefix'] . 'post '
        )->f(0);

        return $db;
    }

    /**
     * User import.
     */
    protected function importUsers(): void
    {
        $db         = $this->db();
        $dc1_prefix = $this->vars['db_prefix'];
        $rs         = $db->select('SELECT * FROM ' . $dc1_prefix . 'user');

        try {
            $this->con->begin();

            while ($rs->fetch()) {
                if (!App::users()->userExists($rs->user_id)) {
                    $cur                   = App::auth()->openUserCursor();
                    $cur->user_id          = $rs->user_id;
                    $cur->user_name        = $rs->user_nom;
                    $cur->user_firstname   = $rs->user_prenom;
                    $cur->user_displayname = $rs->user_pseudo;
                    $cur->user_pwd         = Crypt::createPassword();
                    $cur->user_email       = $rs->user_email;
                    $cur->user_lang        = $rs->user_lang;
                    $cur->user_tz          = App::blog()->settings()->system->blog_timezone;
                    $cur->user_post_status = App::status()->post()->level($rs->user_post_pub ? 'published' : 'pending');
                    $cur->user_options     = new ArrayObject([
                        'edit_size'   => (int) $rs->user_edit_size,
                        'post_format' => $rs->user_post_format,
                    ]);

                    $permissions = [];
                    switch ($rs->user_level) {
                        case '0':
                            $cur->user_status = 0;

                            break;
                        case '1': # editor
                            $permissions['usage'] = true;

                            break;
                        case '5': # advanced editor
                            $permissions['contentadmin'] = true;
                            $permissions['categories']   = true;
                            $permissions['media_admin']  = true;

                            break;
                        case '9': # admin
                            $permissions['admin'] = true;

                            break;
                    }

                    App::users()->addUser($cur);
                    App::users()->setUserBlogPermissions(
                        $rs->user_id,
                        $this->blog_id,
                        $permissions
                    );
                }
            }

            $this->con->commit();
            $db->close();
        } catch (Exception $e) {
            $this->con->rollback();
            $db->close();

            throw $e;
        }
    }

    /**
     * Categories import.
     */
    protected function importCategories(): void
    {
        $db         = $this->db();
        $dc1_prefix = $this->vars['db_prefix'];
        $rs         = $db->select('SELECT * FROM ' . $dc1_prefix . 'categorie ORDER BY cat_ord ASC');

        try {
            $this->con->execute(
                'DELETE FROM ' . $this->prefix . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
                "WHERE blog_id = '" . $this->con->escapeStr($this->blog_id) . "' "
            );

            $ord = 2;
            while ($rs->fetch()) {
                $cur            = App::blog()->categories()->openCategoryCursor();
                $cur->blog_id   = $this->blog_id;
                $cur->cat_title = Txt::cleanStr(htmlspecialchars_decode($rs->cat_libelle));
                $cur->cat_desc  = Txt::cleanStr($rs->cat_desc);
                $cur->cat_url   = Txt::cleanStr($rs->cat_libelle_url);
                $cur->cat_lft   = $ord++;
                $cur->cat_rgt   = $ord++;

                $cur->cat_id = (new MetaRecord($this->con->select(
                    'SELECT MAX(cat_id) FROM ' . $this->prefix . App::blog()->categories()::CATEGORY_TABLE_NAME
                )))->f(0) + 1;
                $this->vars['cat_ids'][$rs->cat_id] = $cur->cat_id;
                $cur->insert();
            }

            $db->close();
        } catch (Exception $e) {
            $db->close();

            throw $e;
        }
    }

    /**
     * Blogroll import.
     */
    protected function importLinks(): void
    {
        $db         = $this->db();
        $dc1_prefix = $this->vars['db_prefix'];
        $rs         = $db->select('SELECT * FROM ' . $dc1_prefix . 'link ORDER BY link_id ASC');

        try {
            $this->con->execute(
                'DELETE FROM ' . $this->prefix . Blogroll::LINK_TABLE_NAME . ' ' .
                "WHERE blog_id = '" . $this->con->escapeStr($this->blog_id) . "' "
            );

            while ($rs->fetch()) {
                $cur                = $this->con->openCursor($this->prefix . Blogroll::LINK_TABLE_NAME);
                $cur->blog_id       = $this->blog_id;
                $cur->link_href     = Txt::cleanStr($rs->href);
                $cur->link_title    = Txt::cleanStr($rs->label);
                $cur->link_desc     = Txt::cleanStr($rs->title);
                $cur->link_lang     = Txt::cleanStr($rs->lang);
                $cur->link_xfn      = Txt::cleanStr($rs->rel);
                $cur->link_position = (int) $rs->position;

                $cur->link_id = (new MetaRecord($this->con->select(
                    'SELECT MAX(link_id) FROM ' . $this->prefix . Blogroll::LINK_TABLE_NAME
                )))->f(0) + 1;
                $cur->insert();
            }

            $db->close();
        } catch (Exception $e) {
            $db->close();

            throw $e;
        }
    }

    /**
     * Entries import.
     *
     * @param   int     $percent    The progress (in %)
     */
    protected function importPosts(&$percent): ?int
    {
        $db         = $this->db();
        $dc1_prefix = $this->vars['db_prefix'];

        $rs = $db->select(
            'SELECT * FROM ' . $dc1_prefix . 'post ORDER BY post_id ASC ' .
            $db->limit($this->post_offset, $this->post_limit)
        );

        try {
            if ($this->post_offset == 0) {
                $this->con->execute(
                    'DELETE FROM ' . $this->prefix . App::blog()::POST_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $this->con->escapeStr($this->blog_id) . "' "
                );
            }

            while ($rs->fetch()) {
                $this->importPost($rs, $db);
            }

            $db->close();
        } catch (Exception $e) {
            $db->close();

            throw $e;
        }

        if ($rs->count() < $this->post_limit) {
            return -1;
        }
        $this->post_offset += $this->post_limit;

        $percent = $this->post_offset > $this->post_count ? 100 : (int) ($this->post_offset * 100 / $this->post_count);

        return null;
    }

    /**
     * Entry import.
     *
     * @param   mixed   $rs     The record
     * @param   mixed   $db     The database
     */
    protected function importPost($rs, $db): void
    {
        $cur              = App::blog()->openPostCursor();
        $cur->blog_id     = $this->blog_id;
        $cur->user_id     = $rs->user_id;
        $cur->cat_id      = (int) $this->vars['cat_ids'][$rs->cat_id];
        $cur->post_dt     = $rs->post_dt;
        $cur->post_creadt = $rs->post_creadt;
        $cur->post_upddt  = $rs->post_upddt;
        $cur->post_title  = Html::decodeEntities(Txt::cleanStr($rs->post_titre));

        $cur->post_url = date('Y/m/d/', (int) strtotime($cur->post_dt)) . $rs->post_id . '-' . $rs->post_titre_url;
        $cur->post_url = substr($cur->post_url, 0, 255);

        $cur->post_format        = $rs->post_content_wiki == '' ? 'xhtml' : 'wiki';
        $cur->post_content_xhtml = Txt::cleanStr($rs->post_content);
        $cur->post_excerpt_xhtml = Txt::cleanStr($rs->post_chapo);

        if ($cur->post_format === 'wiki') {
            $cur->post_content = Txt::cleanStr($rs->post_content_wiki);
            $cur->post_excerpt = Txt::cleanStr($rs->post_chapo_wiki);
        } else {
            $cur->post_content = Txt::cleanStr($rs->post_content);
            $cur->post_excerpt = Txt::cleanStr($rs->post_chapo);
        }

        $cur->post_notes        = Txt::cleanStr($rs->post_notes);
        $cur->post_status       = (int) $rs->post_pub;
        $cur->post_selected     = (int) $rs->post_selected;
        $cur->post_open_comment = (int) $rs->post_open_comment;
        $cur->post_open_tb      = (int) $rs->post_open_tb;
        $cur->post_lang         = $rs->post_lang;

        $cur->post_words = implode(' ', Txt::splitWords(
            $cur->post_title . ' ' .
            $cur->post_excerpt_xhtml . ' ' .
            $cur->post_content_xhtml
        ));

        $cur->post_id = (int) (new MetaRecord($this->con->select(
            'SELECT MAX(post_id) FROM ' . $this->prefix . App::blog()::POST_TABLE_NAME
        )))->f(0) + 1;

        $cur->insert();
        $this->importComments($rs->post_id, $cur->post_id, $db);
        $this->importPings($rs->post_id, $cur->post_id, $db);

        # Load meta if we have some in DC1
        if (isset($this->has_table[$this->vars['db_prefix'] . 'post_meta'])) {
            $this->importMeta($rs->post_id, $cur->post_id, $db);
        }
    }

    /**
     * Comments import.
     *
     * @param   string  $post_id        The post identifier
     * @param   int     $new_post_id    The new post identifier
     * @param   mixed   $db             The database
     */
    protected function importComments(string $post_id, int $new_post_id, $db): void
    {
        $count_c = $count_t = 0;

        $rs = $db->select(
            'SELECT * FROM ' . $this->vars['db_prefix'] . 'comment ' .
            'WHERE post_id = ' . (int) $post_id . ' '
        );

        while ($rs->fetch()) {
            $cur                    = App::blog()->openCommentCursor();
            $cur->post_id           = $new_post_id;
            $cur->comment_author    = Txt::cleanStr($rs->comment_auteur);
            $cur->comment_status    = (int) $rs->comment_pub;
            $cur->comment_dt        = $rs->comment_dt;
            $cur->comment_upddt     = $rs->comment_upddt;
            $cur->comment_email     = Txt::cleanStr($rs->comment_email);
            $cur->comment_content   = Txt::cleanStr($rs->comment_content);
            $cur->comment_ip        = $rs->comment_ip;
            $cur->comment_trackback = (int) $rs->comment_trackback;

            $cur->comment_site = Txt::cleanStr($rs->comment_site);
            if ($cur->comment_site !== '' && !preg_match('!^http(s)?://.*$!', $cur->comment_site)) {
                $cur->comment_site = substr('http://' . $cur->comment_site, 0, 255);
            }

            if ($rs->exists('spam') && $rs->spam && $rs->comment_status == App::status()->comment()->level('unpublished')) {
                $cur->comment_status = App::status()->comment()->level('junk');
            }

            $cur->comment_words = implode(' ', Txt::splitWords($cur->comment_content));

            $cur->comment_id = (new MetaRecord($this->con->select(
                'SELECT MAX(comment_id) FROM ' . $this->prefix . App::blog()::COMMENT_TABLE_NAME
            )))->f(0) + 1;

            $cur->insert();

            if ($cur->comment_status === App::status()->comment()->level('published')) {
                if ($cur->comment_trackback !== 0) {
                    $count_t++;
                } else {
                    $count_c++;
                }
            }
        }

        if ($count_t > 0 || $count_c > 0) {
            $this->con->execute(
                'UPDATE ' . $this->prefix . App::blog()::POST_TABLE_NAME . ' SET ' .
                'nb_comment = ' . $count_c . ', ' .
                'nb_trackback = ' . $count_t . ' ' .
                'WHERE post_id = ' . $new_post_id . ' '
            );
        }
    }

    /**
     * Pings import.
     *
     * @param   string  $post_id        The post identifier
     * @param   int     $new_post_id    The new post identifier
     * @param   mixed   $db             The database
     */
    protected function importPings(string $post_id, int $new_post_id, $db): void
    {
        $urls = [];

        $rs = $db->select(
            'SELECT * FROM ' . $this->vars['db_prefix'] . 'ping ' .
            'WHERE post_id = ' . (int) $post_id
        );

        while ($rs->fetch()) {
            $url = Txt::cleanStr($rs->ping_url);
            if (isset($urls[$url])) {
                continue;
            }

            $cur           = App::trackback()->openTrackbackCursor();
            $cur->post_id  = $new_post_id;
            $cur->ping_url = $url;
            $cur->ping_dt  = $rs->ping_dt;
            $cur->insert();

            $urls[$url] = true;
        }
    }

    /**
     * Meta import.
     *
     * @param   string  $post_id        The post identifier
     * @param   int     $new_post_id    The new post identifier
     * @param   mixed   $db             The database
     */
    protected function importMeta(string $post_id, int $new_post_id, $db): void
    {
        $rs = $db->select(
            'SELECT * FROM ' . $this->vars['db_prefix'] . 'post_meta ' .
            'WHERE post_id = ' . (int) $post_id . ' '
        );

        if ($rs->isEmpty()) {
            return;
        }

        while ($rs->fetch()) {
            App::meta()->setPostMeta($new_post_id, Txt::cleanStr($rs->meta_key), Txt::cleanStr($rs->meta_value));
        }
    }
}
