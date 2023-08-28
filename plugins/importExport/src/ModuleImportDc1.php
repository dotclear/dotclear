<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use ArrayObject;
use Exception;
use dcAuth;
use dcBlog;
use dcCategories;
use dcCore;
use dcTrackback;
use Dotclear\Core\Core;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Crypt;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use initBlogroll;
use form;

class ModuleImportDc1 extends Module
{
    protected $con;
    protected $prefix;
    protected $blog_id;

    protected $action = null;
    protected $step   = 1;

    protected $post_offset = 0;
    protected $post_limit  = 20;
    protected $post_count  = 0;

    protected $has_table = [];

    protected $vars;
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

    /**
     * Sets the module information.
     */
    protected function setInfo(): void
    {
        $this->type        = 'import';
        $this->name        = __('Dotclear 1.2 import');
        $this->description = __('Import a Dotclear 1.2 installation into your current blog.');
    }

    /**
     * Initializes the module.
     */
    public function init(): void
    {
        $this->con     = Core::con();
        $this->prefix  = Core::con()->prefix();
        $this->blog_id = Core::blog()->id;

        if (!isset($_SESSION['dc1_import_vars'])) {
            $_SESSION['dc1_import_vars'] = $this->base_vars;
        }
        $this->vars = &$_SESSION['dc1_import_vars'];

        if ($this->vars['post_limit'] > 0) {
            $this->post_limit = $this->vars['post_limit'];
        }
    }

    public function resetVars()
    {
        $this->vars = $this->base_vars;
        unset($_SESSION['dc1_import_vars']);
    }

    /**
     * Processes the import/export.
     *
     * @param      string  $do     action
     */
    public function process(string $do): void
    {
        $this->action = $do;
    }

    /**
     * Processes the import/export with GUI feedback.
     *
     * We handle process in another way to always display something to user
     *
     * @param      string  $do     action
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
                if (dcCore::app()->plugins->moduleExists('blogroll')) {
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
                $this->post_offset = !empty($_REQUEST['offset']) ? abs((int) $_REQUEST['offset']) : 0;
                $percent           = 0;
                if ($this->importPosts($percent) === -1) {
                    Http::redirect($this->getURL() . '&do=ok');
                } else {
                    echo $this->progressBar(ceil($percent * 0.93) + 7);
                }

                break;
            case 'ok':
                $this->resetVars();
                Core::blog()->triggerBlog();
                $this->step = 6;
                echo $this->progressBar(100);

                break;
        }
    }

    /**
     * GUI for import/export module
     */
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
                echo
                '<p>' . sprintf(
                    __('Import the content of a Dotclear 1.2\'s blog in the current blog: %s.'),
                    '<strong>' . Html::escapeHTML(Core::blog()->name) . '</strong>'
                ) . '</p>' .
                '<p class="warning">' . __('Please note that this process ' .
                    'will empty your categories, blogroll, entries and comments on the current blog.') . '</p>';

                printf(
                    $this->imForm(1, __('General information'), __('Import my blog now')),
                    '<p>' . __('We first need some information about your old Dotclear 1.2 installation.') . '</p>' .
                    '<p><label for="db_driver">' . __('Database driver:') . '</label> ' .
                    form::combo('db_driver', $db_drivers, Html::escapeHTML($this->vars['db_driver'])) . '</p>' .
                    '<p><label for="db_host">' . __('Database Host Name:') . '</label> ' .
                    form::field('db_host', 30, 255, Html::escapeHTML($this->vars['db_host'])) . '</p>' .
                    '<p><label for="db_name">' . __('Database Name:', Html::escapeHTML($this->vars['db_name'])) . '</label> ' .
                    form::field('db_name', 30, 255, Html::escapeHTML($this->vars['db_name'])) . '</p>' .
                    '<p><label for="db_user">' . __('Database User Name:') . '</label> ' .
                    form::field('db_user', 30, 255, Html::escapeHTML($this->vars['db_user'])) . '</p>' .
                    '<p><label for="db_pwd">' . __('Database Password:') . '</label> ' .
                    form::password('db_pwd', 30, 255) . '</p>' .
                    '<p><label for="db_prefix">' . __('Database Tables Prefix:') . '</label> ' .
                    form::field('db_prefix', 30, 255, Html::escapeHTML($this->vars['db_prefix'])) . '</p>' .
                    '<h3 class="vertical-separator">' . __('Entries import options') . '</h3>' .
                    '<p><label for="post_limit">' . __('Number of entries to import at once:') . '</label> ' .
                    form::number('post_limit', 0, 999, Html::escapeHTML((string) $this->vars['post_limit'])) . '</p>'
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
                $t = sprintf(
                    __('Importing entries from %d to %d / %d'),
                    $this->post_offset,
                    min([$this->post_offset + $this->post_limit, $this->post_count]),
                    $this->post_count
                );
                printf(
                    $this->imForm(5, $t),
                    form::hidden(['offset'], $this->post_offset) .
                    $this->autoSubmit()
                );

                break;
            case 6:
                echo
                '<h3 class="vertical-separator">' . __('Please read carefully') . '</h3>' .
                '<ul>' .
                '<li>' . __('Every newly imported user has received a random password ' .
                    'and will need to ask for a new one by following the "I forgot my password" link on the login page ' .
                    '(Their registered email address has to be valid.)') . '</li>' .

                '<li>' . sprintf(
                    __('Please note that Dotclear 2 has a new URL layout. You can avoid broken ' .
                    'links by installing <a href="%s">DC1 redirect</a> plugin and activate it in your blog configuration.'),
                    'https://plugins.dotaddict.org/dc2/details/dc1redirect'
                ) . '</li>' .
                '</ul>' .

                $this->congratMessage();

                break;
        }
    }

    /**
     * Return a simple form for step by step process
     *
     * @param      int          $step          The step
     * @param      string       $legend        The legend
     * @param      string       $submit_value  The submit value
     *
     * @return     string
     */
    protected function imForm(int $step, string $legend, ?string $submit_value = null): string
    {
        if (!$submit_value) {
            $submit_value = __('next step') . ' >';
        }

        return
        '<form action="' . $this->getURL(true) . '" method="post">' .
        '<h3 class="vertical-separator">' . $legend . '</h3>' .
        '<div>' . Core::nonce()->getFormNonce() .
        form::hidden(['do'], 'step' . $step) .
        '%s' . '</div>' .
        '<p class="vertical-separator"><input type="submit" value="' . $submit_value . '" /></p>' .
        '<p class="form-note info">' . __('Depending on the size of your blog, it could take a few minutes.') . '</p>' .
        '</form>';
    }

    /**
     * Error display
     *
     * @param      Exception  $e      The error
     */
    protected function error(Exception $e)
    {
        echo
        '<div class="error"><strong>' . __('Errors:') . '</strong>' . '<p>' . $e->getMessage() . '</p></div>';
    }

    /**
     * DB init
     *
     * @throws     Exception
     *
     * @return     mixed
     */
    protected function db()
    {
        $db = AbstractHandler::init($this->vars['db_driver'], $this->vars['db_host'], $this->vars['db_name'], $this->vars['db_user'], $this->vars['db_pwd']);

        $rs = $db->select("SHOW TABLES LIKE '" . $this->vars['db_prefix'] . "%'");
        if ($rs->isEmpty()) {
            throw new Exception(__('Dotclear tables not found'));
        }

        while ($rs->fetch()) {
            $this->has_table[$rs->f(0)] = true;
        }

        // Set this to read data as they were written in Dotclear 1
        try {
            $db->execute('SET NAMES DEFAULT');
        } catch (Exception $e) {
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
     * User import
     */
    protected function importUsers(): void
    {
        $db         = $this->db();
        $dc1_prefix = $this->vars['db_prefix'];
        $rs         = $db->select('SELECT * FROM ' . $dc1_prefix . 'user');

        try {
            $this->con->begin();

            while ($rs->fetch()) {
                if (!Core::users()->userExists($rs->user_id)) {
                    $cur                   = $this->con->openCursor($this->prefix . dcAuth::USER_TABLE_NAME);
                    $cur->user_id          = $rs->user_id;
                    $cur->user_name        = $rs->user_nom;
                    $cur->user_firstname   = $rs->user_prenom;
                    $cur->user_displayname = $rs->user_pseudo;
                    $cur->user_pwd         = Crypt::createPassword();
                    $cur->user_email       = $rs->user_email;
                    $cur->user_lang        = $rs->user_lang;
                    $cur->user_tz          = Core::blog()->settings->system->blog_timezone;
                    $cur->user_post_status = $rs->user_post_pub ? dcBlog::POST_PUBLISHED : dcBlog::POST_PENDING;
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

                    Core::users()->addUser($cur);
                    Core::users()->setUserBlogPermissions(
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
     * Categories import
     */
    protected function importCategories(): void
    {
        $db         = $this->db();
        $dc1_prefix = $this->vars['db_prefix'];
        $rs         = $db->select('SELECT * FROM ' . $dc1_prefix . 'categorie ORDER BY cat_ord ASC');

        try {
            $this->con->execute(
                'DELETE FROM ' . $this->prefix . dcCategories::CATEGORY_TABLE_NAME . ' ' .
                "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' "
            );

            $ord = 2;
            while ($rs->fetch()) {
                $cur            = $this->con->openCursor($this->prefix . dcCategories::CATEGORY_TABLE_NAME);
                $cur->blog_id   = $this->blog_id;
                $cur->cat_title = Text::cleanStr(htmlspecialchars_decode($rs->cat_libelle));
                $cur->cat_desc  = Text::cleanStr($rs->cat_desc);
                $cur->cat_url   = Text::cleanStr($rs->cat_libelle_url);
                $cur->cat_lft   = $ord++;
                $cur->cat_rgt   = $ord++;

                $cur->cat_id = (new MetaRecord($this->con->select(
                    'SELECT MAX(cat_id) FROM ' . $this->prefix . dcCategories::CATEGORY_TABLE_NAME
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
     * Blogroll import
     */
    protected function importLinks(): void
    {
        $db         = $this->db();
        $dc1_prefix = $this->vars['db_prefix'];
        $rs         = $db->select('SELECT * FROM ' . $dc1_prefix . 'link ORDER BY link_id ASC');

        try {
            $this->con->execute(
                'DELETE FROM ' . $this->prefix . initBlogroll::LINK_TABLE_NAME . ' ' .
                "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' "
            );

            while ($rs->fetch()) {
                $cur                = $this->con->openCursor($this->prefix . initBlogroll::LINK_TABLE_NAME);
                $cur->blog_id       = $this->blog_id;
                $cur->link_href     = Text::cleanStr($rs->href);
                $cur->link_title    = Text::cleanStr($rs->label);
                $cur->link_desc     = Text::cleanStr($rs->title);
                $cur->link_lang     = Text::cleanStr($rs->lang);
                $cur->link_xfn      = Text::cleanStr($rs->rel);
                $cur->link_position = (int) $rs->position;

                $cur->link_id = (new MetaRecord($this->con->select(
                    'SELECT MAX(link_id) FROM ' . $this->prefix . initBlogroll::LINK_TABLE_NAME
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
     * Entries import
     *
     * @param      int     $percent  The progress (in %)
     *
     * @return     mixed
     */
    protected function importPosts(&$percent)
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
                    'DELETE FROM ' . $this->prefix . dcBlog::POST_TABLE_NAME . ' ' .
                    "WHERE blog_id = '" . $this->con->escape($this->blog_id) . "' "
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

        if ($this->post_offset > $this->post_count) {
            $percent = 100;
        } else {
            $percent = $this->post_offset * 100 / $this->post_count;
        }
    }

    /**
     * Entry import
     *
     * @param      mixed    $rs     The record
     * @param      mixed    $db     The database
     */
    protected function importPost($rs, $db): void
    {
        $cur              = $this->con->openCursor($this->prefix . dcBlog::POST_TABLE_NAME);
        $cur->blog_id     = $this->blog_id;
        $cur->user_id     = $rs->user_id;
        $cur->cat_id      = (int) $this->vars['cat_ids'][$rs->cat_id];
        $cur->post_dt     = $rs->post_dt;
        $cur->post_creadt = $rs->post_creadt;
        $cur->post_upddt  = $rs->post_upddt;
        $cur->post_title  = Html::decodeEntities(Text::cleanStr($rs->post_titre));

        $cur->post_url = date('Y/m/d/', strtotime($cur->post_dt)) . $rs->post_id . '-' . $rs->post_titre_url;
        $cur->post_url = substr($cur->post_url, 0, 255);

        $cur->post_format        = $rs->post_content_wiki == '' ? 'xhtml' : 'wiki';
        $cur->post_content_xhtml = Text::cleanStr($rs->post_content);
        $cur->post_excerpt_xhtml = Text::cleanStr($rs->post_chapo);

        if ($cur->post_format == 'wiki') {
            $cur->post_content = Text::cleanStr($rs->post_content_wiki);
            $cur->post_excerpt = Text::cleanStr($rs->post_chapo_wiki);
        } else {
            $cur->post_content = Text::cleanStr($rs->post_content);
            $cur->post_excerpt = Text::cleanStr($rs->post_chapo);
        }

        $cur->post_notes        = Text::cleanStr($rs->post_notes);
        $cur->post_status       = (int) $rs->post_pub;
        $cur->post_selected     = (int) $rs->post_selected;
        $cur->post_open_comment = (int) $rs->post_open_comment;
        $cur->post_open_tb      = (int) $rs->post_open_tb;
        $cur->post_lang         = $rs->post_lang;

        $cur->post_words = implode(' ', Text::splitWords(
            $cur->post_title . ' ' .
            $cur->post_excerpt_xhtml . ' ' .
            $cur->post_content_xhtml
        ));

        $cur->post_id = (new MetaRecord($this->con->select(
            'SELECT MAX(post_id) FROM ' . $this->prefix . dcBlog::POST_TABLE_NAME
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
     * Comments import
     *
     * @param      string  $post_id      The post identifier
     * @param      int     $new_post_id  The new post identifier
     * @param      mixed   $db           The database
     */
    protected function importComments(string $post_id, int $new_post_id, $db): void
    {
        $count_c = $count_t = 0;

        $rs = $db->select(
            'SELECT * FROM ' . $this->vars['db_prefix'] . 'comment ' .
            'WHERE post_id = ' . (int) $post_id . ' '
        );

        while ($rs->fetch()) {
            $cur                    = $this->con->openCursor($this->prefix . dcBlog::COMMENT_TABLE_NAME);
            $cur->post_id           = $new_post_id;
            $cur->comment_author    = Text::cleanStr($rs->comment_auteur);
            $cur->comment_status    = (int) $rs->comment_pub;
            $cur->comment_dt        = $rs->comment_dt;
            $cur->comment_upddt     = $rs->comment_upddt;
            $cur->comment_email     = Text::cleanStr($rs->comment_email);
            $cur->comment_content   = Text::cleanStr($rs->comment_content);
            $cur->comment_ip        = $rs->comment_ip;
            $cur->comment_trackback = (int) $rs->comment_trackback;

            $cur->comment_site = Text::cleanStr($rs->comment_site);
            if ($cur->comment_site != '' && !preg_match('!^http(s)?://.*$!', $cur->comment_site)) {
                $cur->comment_site = substr('http://' . $cur->comment_site, 0, 255);
            }

            if ($rs->exists('spam') && $rs->spam && $rs->comment_status == dcBlog::COMMENT_UNPUBLISHED) {
                $cur->comment_status = dcBlog::COMMENT_JUNK;
            }

            $cur->comment_words = implode(' ', Text::splitWords($cur->comment_content));

            $cur->comment_id = (new MetaRecord($this->con->select(
                'SELECT MAX(comment_id) FROM ' . $this->prefix . dcBlog::COMMENT_TABLE_NAME
            )))->f(0) + 1;

            $cur->insert();

            if ($cur->comment_status == dcBlog::COMMENT_PUBLISHED) {
                if ($cur->comment_trackback) {
                    $count_t++;
                } else {
                    $count_c++;
                }
            }
        }

        if ($count_t > 0 || $count_c > 0) {
            $this->con->execute(
                'UPDATE ' . $this->prefix . dcBlog::POST_TABLE_NAME . ' SET ' .
                'nb_comment = ' . $count_c . ', ' .
                'nb_trackback = ' . $count_t . ' ' .
                'WHERE post_id = ' . (int) $new_post_id . ' '
            );
        }
    }

    /**
     * Pings import
     *
     * @param      string  $post_id      The post identifier
     * @param      int     $new_post_id  The new post identifier
     * @param      mixed   $db           The database
     */
    protected function importPings(string $post_id, int $new_post_id, $db): void
    {
        $urls = [];

        $rs = $db->select(
            'SELECT * FROM ' . $this->vars['db_prefix'] . 'ping ' .
            'WHERE post_id = ' . (int) $post_id
        );

        while ($rs->fetch()) {
            $url = Text::cleanStr($rs->ping_url);
            if (isset($urls[$url])) {
                continue;
            }

            $cur           = $this->con->openCursor($this->prefix . dcTrackback::PING_TABLE_NAME);
            $cur->post_id  = (int) $new_post_id;
            $cur->ping_url = $url;
            $cur->ping_dt  = $rs->ping_dt;
            $cur->insert();

            $urls[$url] = true;
        }
    }

    /**
     * Meta import
     *
     * @param      string  $post_id      The post identifier
     * @param      int     $new_post_id  The new post identifier
     * @param      mixed   $db           The database
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
            Core::meta()->setPostMeta($new_post_id, Text::cleanStr($rs->meta_key), Text::cleanStr($rs->meta_value));
        }
    }
}
