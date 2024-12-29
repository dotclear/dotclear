<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Process;
use Dotclear\Core\Upgrade\Update;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\File;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\XmlTag;
use Dotclear\Helper\Network\Feed\Reader;
use Dotclear\Helper\Text;
use Dotclear\Module\Store;
use Dotclear\Plugin\antispam\Antispam;
use Exception;

/**
 * @since 2.27 Before as admin/services.php
 */
class Rest extends Process
{
    public static function init(): bool
    {
        App::rest()->addFunction('getPostsCount', self::getPostsCount(...));
        App::rest()->addFunction('getCommentsCount', self::getCommentsCount(...));
        App::rest()->addFunction('checkNewsUpdate', self::checkNewsUpdate(...));
        App::rest()->addFunction('checkCoreUpdate', self::checkCoreUpdate(...));
        App::rest()->addFunction('checkStoreUpdate', self::checkStoreUpdate(...));
        App::rest()->addFunction('getPostById', self::getPostById(...));
        App::rest()->addFunction('getCommentById', self::getCommentById(...));
        App::rest()->addFunction('quickPost', self::quickPost(...));
        App::rest()->addFunction('getZipMediaContent', self::getZipMediaContent(...));
        App::rest()->addFunction('getMeta', self::getMeta(...));
        App::rest()->addFunction('delMeta', self::delMeta(...));
        App::rest()->addFunction('setPostMeta', self::setPostMeta(...));
        App::rest()->addFunction('searchMeta', self::searchMeta(...));
        App::rest()->addFunction('searchMetadata', self::searchMetadata(...));
        App::rest()->addFunction('setSectionFold', self::setSectionFold(...));
        App::rest()->addFunction('setDashboardPositions', self::setDashboardPositions(...));
        App::rest()->addFunction('setListsOptions', self::setListsOptions(...));

        return self::status(true);
    }

    public static function process(): bool
    {
        if (App::rest()->serveRestRequests()) {
            App::rest()->serve();
        }

        return true;
    }

    // REST Methods below
    // ------------------

    /**
     * REST method to get the posts count (JSON).
     *
     * @return     array<string, string>  The posts count.
     */
    public static function getPostsCount(): array
    {
        $count = App::blog()->getPosts([], true)->f(0);

        return [
            'ret' => sprintf(__('%d post', '%d posts', (int) $count), $count),
        ];
    }

    /**
     * REST method to get the comments count (JSON).
     *
     * @return     array<string, string>  The comments count.
     */
    public static function getCommentsCount(): array
    {
        $count = App::blog()->getComments([], true)->f(0);

        return [
            'ret' => sprintf(__('%d comment', '%d comments', (int) $count), $count),
        ];
    }

    /**
     * REST method to check Dotclear news (JSON).
     *
     * @throws     Exception
     *
     * @return     array<string, mixed>    returned data
     */
    public static function checkNewsUpdate(): array
    {
        # Dotclear news

        $data = [
            'check' => false,
            'ret'   => __('Dotclear news not available'),
        ];

        if (App::auth()->prefs()->dashboard->dcnews) {
            try {
                if ('' === $rss_news = App::backend()->resources()->entry('rss_news', 'Dotclear')) {
                    throw new Exception();
                }
                $feed_reader = new Reader();
                $feed_reader->setCacheDir(App::config()->cacheRoot());
                $feed_reader->setTimeout(2);
                $feed_reader->setUserAgent('Dotclear - https://dotclear.org/');
                $feed = $feed_reader->parse($rss_news);
                if ($feed) {
                    $ret = '<div class="box medium dc-box" id="ajax-news"><h3>' . __('Dotclear news') . '</h3><dl id="news">';
                    $i   = 1;
                    foreach ($feed->items as $item) {
                        $dt = isset($item->link) ? '<a href="' . $item->link . '" class="outgoing" title="' . $item->title . '">' .
                        $item->title . ' <img src="images/outgoing-link.svg" alt=""></a>' : $item->title;
                        $ret .= '<dt>' . $dt . '</dt>' .
                        '<dd><p><strong>' . Date::dt2str(__('%d %B %Y:'), $item->pubdate, 'Europe/Paris') . '</strong> ' .
                        '<em>' . Text::cutString(Html::clean($item->content), 120) . '...</em></p></dd>';
                        $i++;
                        if ($i > 2) {
                            break;
                        }
                    }
                    $ret .= '</dl></div>';
                    $data = [
                        'check' => true,
                        'ret'   => $ret,
                    ];
                }
            } catch (Exception) {
                // Ignore exceptions
            }
        }

        return $data;
    }

    /**
     * REST method to check Dotclear update (JSON).
     *
     * @return     array<string, mixed>    returned data
     */
    public static function checkCoreUpdate(): array
    {
        // Dotclear updates notifications

        $data = [
            'check' => false,
            'ret'   => __('Dotclear update not available'),
        ];

        if (App::auth()->isSuperAdmin() && !App::config()->coreNotUpdate() && is_readable(App::config()->digestsRoot()) && !App::auth()->prefs()->dashboard->nodcupdate) {
            $updater      = new Update(App::config()->coreUpdateUrl(), 'dotclear', App::config()->coreUpdateCanal(), App::config()->cacheRoot() . DIRECTORY_SEPARATOR . Update::CACHE_FOLDER);
            $new_v        = $updater->check(App::config()->dotclearVersion(), false);
            $version_info = $new_v ? $updater->getInfoURL() : '';

            if ($updater->getNotify() && $new_v) {
                // Check PHP version required
                if (version_compare(phpversion(), (string) $updater->getPHPVersion()) >= 0) {
                    $ret = '<div class="dc-update" id="ajax-update"><h3>' . sprintf(__('Dotclear %s is available!'), $new_v) . '</h3> ' .
                    '<p class="form-buttons"><a class="button submit" href="' . App::backend()->url()->get('upgrade.upgrade') . '">' . sprintf(__('Upgrade now'), $new_v) . '</a> ' .
                    '<a class="button" href="' . App::backend()->url()->get('upgrade.upgrade', ['hide_msg' => 1]) . '">' . __('Remind me later') . '</a>' .
                        ($version_info ? ' </p>' .
                        '<p class="updt-info"><a href="' . $version_info . '">' . __('Information about this version') . '</a>' : '') . '</p>' .
                        '</div>';
                } else {
                    $ret = '<p class="info">' .
                    sprintf(
                        __('A new version of Dotclear is available but needs PHP version ≥ %s, your\'s is currently %s'),
                        $updater->getPHPVersion(),
                        phpversion()
                    ) .
                        '</p>';
                }
                $data = [
                    'check' => true,
                    'ret'   => $ret,
                ];
            } elseif (version_compare(phpversion(), App::config()->nextRequiredPhp(), '<')) {
                if (!App::auth()->prefs()->interface->hidemoreinfo) {
                    $ret = '<p class="info">' .
                    sprintf(
                        __('The next versions of Dotclear will not support PHP version < %s, your\'s is currently %s'),
                        App::config()->nextRequiredPhp(),
                        phpversion()
                    ) .
                    '</p>';
                    $data = [
                        'check' => true,
                        'ret'   => $ret,
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * REST method to check store (JSON)
     *
     * @param      array<string, string>     $get    The get
     * @param      array<string, string>     $post   The post
     *
     * @throws     Exception
     *
     * @return     array<string, mixed>    returned data
     */
    public static function checkStoreUpdate(array $get, array $post): array
    {
        # Dotclear store updates notifications

        $data = [
            'ret'   => __('No updates are available'),
            'new'   => false,
            'check' => false,
            'nb'    => 0,
        ];

        if (empty($post['store'])) {
            throw new Exception('No store type');
        }

        if ($post['store'] === 'themes') {
            // load once themes
            if (App::themes()->isEmpty() && App::blog()->isDefined()) {
                App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
            }
            $mod = App::themes();
            $url = App::blog()->settings()->system->store_theme_url;
        } elseif ($post['store'] === 'plugins') {
            $mod = App::plugins();
            $url = App::blog()->settings()->system->store_plugin_url;
        } else {
            $mod = [];
            $url = '';
            # --BEHAVIOR-- restCheckStoreUpdate -- string, array<int,Modules>, array<int,string>
            App::behavior()->callBehavior('restCheckStoreUpdateV2', $post['store'], [&$mod], [&$url]);

            /**
             * @var array<\Dotclear\Process\Backend\ModulesInterface>    $mod
             * @var string                                               $url
             */
            if ($mod === [] || $url === '') {        // @phpstan-ignore-line
                throw new Exception('Unknown store type');
            }
        }

        $repo = new Store($mod, $url);     // @phpstan-ignore-line
        $upd  = $repo->getDefines(true);

        $tmp = new ArrayObject($upd);

        # --BEHAVIOR-- afterCheckStoreUpdate -- string, ArrayObject<int, ModuleDefine>
        App::behavior()->callBehavior('afterCheckStoreUpdate', $post['store'], $tmp);

        $upd = $tmp->getArrayCopy();

        if (!empty($upd)) {
            $data = [
                'ret'   => sprintf(__('An update is available', '%s updates are available.', count($upd)), count($upd)),
                'new'   => $repo->hasNewUdpates(),
                'check' => true,
                'nb'    => count($upd),
            ];
        }

        return $data;
    }

    /**
     * REST method to get post by ID (JSON)
     *
     * @param      array<string, string>     $get    The get
     *
     * @throws     Exception
     *
     * @return     array<string, mixed>    returned data
     */
    public static function getPostById(array $get): array
    {
        if (empty($get['id'])) {
            throw new Exception('No post ID');
        }

        $params = ['post_id' => (int) $get['id']];

        if (isset($get['post_type'])) {
            $params['post_type'] = $get['post_type'];
        }

        $rs = App::blog()->getPosts($params);

        if ($rs->isEmpty()) {
            throw new Exception('No post for this ID');
        }

        $metadata = [];
        if ($rs->post_meta && ($meta = @unserialize($rs->post_meta)) !== false) {
            foreach ($meta as $K => $V) {
                foreach ($V as $v) {
                    $metadata[$K] = $v;
                }
            }
        }

        return [
            'id' => $rs->post_id,

            'blog_id'            => $rs->blog_id,
            'user_id'            => $rs->user_id,
            'cat_id'             => $rs->cat_id,
            'post_dt'            => $rs->post_dt,
            'post_creadt'        => $rs->post_creadt,
            'post_upddt'         => $rs->post_upddt,
            'post_format'        => $rs->post_format,
            'post_url'           => $rs->post_url,
            'post_lang'          => $rs->post_lang,
            'post_title'         => $rs->post_title,
            'post_excerpt'       => $rs->post_excerpt,
            'post_excerpt_xhtml' => $rs->post_excerpt_xhtml,
            'post_content'       => $rs->post_content,
            'post_content_xhtml' => $rs->post_content_xhtml,
            'post_notes'         => $rs->post_notes,
            'post_status'        => $rs->post_status,
            'post_selected'      => $rs->post_selected,
            'post_open_comment'  => $rs->post_open_comment,
            'post_open_tb'       => $rs->post_open_tb,
            'nb_comment'         => $rs->nb_comment,
            'nb_trackback'       => $rs->nb_trackback,
            'user_name'          => $rs->user_name,
            'user_firstname'     => $rs->user_firstname,
            'user_displayname'   => $rs->user_displayname,
            'user_email'         => $rs->user_email,
            'user_url'           => $rs->user_url,
            'cat_title'          => $rs->cat_title,
            'cat_url'            => $rs->cat_url,

            'post_display_content' => $rs->getContent(true),
            'post_display_excerpt' => $rs->getExcerpt(true),

            'post_meta' => $metadata,
        ];
    }

    /**
     * REST method to get comment by ID (JSON)
     *
     * @param      array<string, string>     $get    The get
     *
     * @throws     Exception
     *
     * @return     array<string, mixed>    returned data
     */
    public static function getCommentById(array $get): array
    {
        if (empty($get['id'])) {
            throw new Exception('No comment ID');
        }

        $rs = App::blog()->getComments(['comment_id' => (int) $get['id']]);

        if ($rs->isEmpty()) {
            throw new Exception('No comment for this ID');
        }

        return [
            'id' => $rs->comment_id,

            'comment_dt'        => $rs->comment_dt,
            'comment_upddt'     => $rs->comment_upddt,
            'comment_author'    => $rs->comment_author,
            'comment_site'      => $rs->comment_site,
            'comment_content'   => $rs->comment_content,
            'comment_trackback' => $rs->comment_trackback,
            'comment_status'    => $rs->comment_status,
            'post_title'        => $rs->post_title,
            'post_url'          => $rs->post_url,
            'post_id'           => $rs->post_id,
            'post_dt'           => $rs->post_dt,
            'user_id'           => $rs->user_id,

            'comment_display_content' => $rs->getContent(true),

            'comment_ip'        => App::auth()->userID() ? $rs->comment_ip : '',
            'comment_email'     => App::auth()->userID() ? $rs->comment_email : '',
            'comment_spam_disp' => App::auth()->userID() ? Antispam::statusMessage($rs) : '',
        ];
    }

    /**
     * REST method to create a quick post (JSON)
     *
     * @param      array<string, string>     $get    The get
     * @param      array<string, string>     $post   The post
     *
     * @return     array<string, mixed>    returned data
     */
    public static function quickPost(array $get, array $post): array
    {
        # Create category
        if (!empty($post['new_cat_title']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CATEGORIES,
        ]), App::blog()->id())) {
            $cur_cat            = App::blog()->categories()->openCategoryCursor();
            $cur_cat->cat_title = $post['new_cat_title'];
            $cur_cat->cat_url   = '';

            $parent_cat = $post['new_cat_parent'] ?? '';

            # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
            App::behavior()->callBehavior('adminBeforeCategoryCreate', $cur_cat);

            $post['cat_id'] = App::blog()->addCategory($cur_cat, (int) $parent_cat);

            # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, int
            App::behavior()->callBehavior('adminAfterCategoryCreate', $cur_cat, $post['cat_id']);
        }

        $cur = App::blog()->openPostCursor();

        $cur->post_title        = $post['post_title'] ?? '';
        $cur->user_id           = App::auth()->userID();
        $cur->post_content      = $post['post_content'] ?? '';
        $cur->cat_id            = $post['cat_id']       ?? null;
        $cur->post_format       = $post['post_format']  ?? 'xhtml';
        $cur->post_lang         = $post['post_lang']    ?? '';
        $cur->post_status       = $post['post_status']  ?? App::blog()::POST_UNPUBLISHED;
        $cur->post_open_comment = (int) App::blog()->settings()->system->allow_comments;
        $cur->post_open_tb      = (int) App::blog()->settings()->system->allow_trackbacks;

        # --BEHAVIOR-- adminBeforePostCreate -- Cursor
        App::behavior()->callBehavior('adminBeforePostCreate', $cur);

        $return_id = App::blog()->addPost($cur);

        # --BEHAVIOR-- adminAfterPostCreate -- Cursor, int
        App::behavior()->callBehavior('adminAfterPostCreate', $cur, $return_id);

        $post = App::blog()->getPosts(['post_id' => $return_id]);

        return [
            'id'     => $return_id,
            'status' => $post->post_status,
            'url'    => $post->getURL(),
        ];
    }

    /**
     * REST method to get Zip content list (JSON)
     *
     * @param      array<string, string>     $get    The get
     *
     * @throws     Exception
     *
     * @return     array<string, mixed>    returned data
     */
    public static function getZipMediaContent(array $get): array
    {
        if (empty($get['id'])) {
            throw new Exception('No media ID');
        }

        $id = (int) $get['id'];

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA,
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]), App::blog()->id())) {
            throw new Exception('Permission denied');
        }

        $file = null;

        try {
            $file = App::media()->getFile($id);
        } catch (Exception) {
            // Ignore exceptions
        }

        if (!$file instanceof File || $file->type != 'application/zip' || !$file->editable) {
            throw new Exception('Not a valid file');
        }

        $content = App::media()->getZipContent($file);

        $data = [];
        foreach ($content as $k => $v) {
            $data[$k] = $v;
        }

        return $data;
    }

    /**
     * REST method to get metadata (JSON)
     *
     * @param      array<string, string>     $get    The get
     *
     * @return     array<int, array<string, mixed>>    returned data
     */
    public static function getMeta(array $get): array
    {
        $postid   = $get['postId']   ?? null;
        $limit    = $get['limit']    ?? null;
        $metaId   = $get['metaId']   ?? null;
        $metaType = $get['metaType'] ?? null;

        $sortby = $get['sortby'] ?? 'meta_type,asc';

        $rs = App::meta()->getMetadata([
            'meta_type' => $metaType,
            'limit'     => $limit,
            'meta_id'   => $metaId,
            'post_id'   => $postid, ]);
        $rs = App::meta()->computeMetaStats($rs);

        $sortby = explode(',', $sortby);
        $sort   = $sortby[0];
        $order  = $sortby[1] ?? 'asc';

        $sort = match ($sort) {
            'metaId'   => 'meta_id_lower',
            'count'    => 'count',
            'metaType' => 'meta_type',
            default    => 'meta_type',
        };

        $rs->sort($sort, $order);

        $data = [];
        while ($rs->fetch()) {
            $data[] = [
                'meta_id'      => $rs->meta_id,
                'type'         => $rs->meta_type,
                'uri'          => rawurlencode($rs->meta_id),
                'count'        => $rs->count,
                'percent'      => $rs->percent,
                'roundpercent' => $rs->roundpercent,
            ];
        }

        return $data;
    }

    /**
     * REST method to set post metadata (JSON)
     *
     * @param      array<string, string>     $get    The get
     * @param      array<string, string>     $post   The post
     *
     * @return     true
     */
    public static function setPostMeta(array $get, array $post): bool
    {
        if (empty($post['postId'])) {
            throw new Exception('No post ID');
        }

        if (empty($post['meta']) && $post['meta'] !== '0') {
            throw new Exception('No meta');
        }

        if (empty($post['metaType'])) {
            throw new Exception('No meta type');
        }

        # Get previous meta for post
        $post_meta = App::meta()->getMetadata([
            'meta_type' => $post['metaType'],
            'post_id'   => $post['postId'], ]);
        $pm = [];
        while ($post_meta->fetch()) {
            $pm[] = $post_meta->meta_id;
        }

        foreach (App::meta()->splitMetaValues($post['meta']) as $m) {
            if (!in_array($m, $pm)) {
                App::meta()->setPostMeta($post['postId'], $post['metaType'], $m);
            }
        }

        return true;
    }

    /**
     * REST method to get metadata (JSON)
     *
     * @param      array<string, string>     $get    The get
     * @param      array<string, string>     $post   The post
     *
     * @return     true
     */
    public static function delMeta(array $get, array $post): bool
    {
        if (empty($post['postId'])) {
            throw new Exception('No post ID');
        }

        if (empty($post['metaId']) && $post['metaId'] !== '0') {
            throw new Exception('No meta ID');
        }

        if (empty($post['metaType'])) {
            throw new Exception('No meta type');
        }

        App::meta()->delPostMeta($post['postId'], $post['metaType'], $post['metaId']);

        return true;
    }

    /**
     * REST method to search metadata (XML)
     *
     * Used with jquery.autocomplete()
     *
     * @param      mixed                    $unused     Was dcCore instance
     * @param      array<string, string>    $get        The get
     *
     * @deprecated Since 2.29, use searchMetadata instead
     */
    public static function searchMeta(mixed $unused, array $get): XmlTag
    {
        $q = $get['q'] ?? null;
        ;
        $metaType = $get['metaType'] ?? null;

        $sortby = $get['sortby'] ?? 'meta_type,asc';

        $rs = App::meta()->getMetadata(['meta_type' => $metaType]);
        $rs = App::meta()->computeMetaStats($rs);

        $sortby = explode(',', $sortby);
        $sort   = $sortby[0];
        $order  = $sortby[1] ?? 'asc';

        $sort = match ($sort) {
            'metaId'   => 'meta_id_lower',
            'count'    => 'count',
            'metaType' => 'meta_type',
            default    => 'meta_type',
        };

        $rs->lexicalSort($sort, $order);

        $rsp = new XmlTag();

        // 1st loop looking at the beginning
        while ($rs->fetch()) {
            if (mb_stripos($rs->meta_id, (string) $q) === 0) {
                $metaTag               = new XmlTag('meta');
                $metaTag->type         = $rs->meta_type;
                $metaTag->uri          = rawurlencode($rs->meta_id);
                $metaTag->count        = $rs->count;
                $metaTag->percent      = $rs->percent;
                $metaTag->roundpercent = $rs->roundpercent;
                $metaTag->CDATA($rs->meta_id);

                $rsp->insertNode($metaTag);
            }
        }

        // 2nd loop looking anywhere
        $rs->moveStart();
        while ($rs->fetch()) {  // @phpstan-ignore-line as we have done a moveStart(), the fetch() is not always false (while.alwaysFalse)
            if (mb_stripos($rs->meta_id, (string) $q) > 0) {
                $metaTag               = new XmlTag('meta');
                $metaTag->type         = $rs->meta_type;
                $metaTag->uri          = rawurlencode($rs->meta_id);
                $metaTag->count        = $rs->count;
                $metaTag->percent      = $rs->percent;
                $metaTag->roundpercent = $rs->roundpercent;
                $metaTag->CDATA($rs->meta_id);

                $rsp->insertNode($metaTag);
            }
        }

        return $rsp;
    }

    /**
     * REST method to search metadata (JSON)
     *
     * Used with jquery.autocomplete()
     *
     * @param      array<string, string>     $get    The get
     *
     * @return     array<int, array<string, mixed>>
     */
    public static function searchMetadata(array $get): array
    {
        $q        = $get['q']        ?? null;
        $metaType = $get['metaType'] ?? null;

        $sortby = $get['sortby'] ?? 'meta_type,asc';

        $rs = App::meta()->getMetadata(['meta_type' => $metaType]);
        $rs = App::meta()->computeMetaStats($rs);

        $sortby = explode(',', $sortby);
        $sort   = $sortby[0];
        $order  = $sortby[1] ?? 'asc';

        $sort = match ($sort) {
            'metaId'   => 'meta_id_lower',
            'count'    => 'count',
            'metaType' => 'meta_type',
            default    => 'meta_type',
        };

        $rs->lexicalSort($sort, $order);

        $data = [];

        // 1st loop looking at the beginning
        while ($rs->fetch()) {
            if (mb_stripos($rs->meta_id, (string) $q) === 0) {
                $data[] = [
                    'meta_id'      => $rs->meta_id,
                    'type'         => $rs->meta_type,
                    'uri'          => rawurlencode($rs->meta_id),
                    'count'        => $rs->count,
                    'percent'      => $rs->percent,
                    'roundpercent' => $rs->roundpercent,
                ];
            }
        }

        // 2nd loop looking anywhere
        $rs->moveStart();
        while ($rs->fetch()) {  // @phpstan-ignore-line as we have done a moveStart(), the fetch() is not always false (while.alwaysFalse)
            if (mb_stripos($rs->meta_id, (string) $q) > 0) {
                $data[] = [
                    'meta_id'      => $rs->meta_id,
                    'type'         => $rs->meta_type,
                    'uri'          => rawurlencode($rs->meta_id),
                    'count'        => $rs->count,
                    'percent'      => $rs->percent,
                    'roundpercent' => $rs->roundpercent,
                ];
            }
        }

        return $data;
    }

    /**
     * REST method to store section folding position (JSON)
     *
     * @param      array<string, string>     $get    The get
     * @param      array<string, string>     $post   The post
     *
     * @return     true
     */
    public static function setSectionFold(array $get, array $post): bool
    {
        if (empty($post['section'])) {
            throw new Exception('No section name');
        }
        $section = $post['section'];
        $status  = isset($post['value']) && ($post['value'] != 0);
        if (App::auth()->prefs()->toggles->prefExists('unfolded_sections')) {
            $toggles = explode(',', trim((string) App::auth()->prefs()->toggles->unfolded_sections));
        } else {
            $toggles = [];
        }
        $k = array_search($section, $toggles);
        if ($status) {
            // true == Fold section ==> remove it from unfolded list
            if ($k !== false) {
                unset($toggles[$k]);
            }
        } elseif ($k === false) {
            // false == unfold section ==> add it to unfolded list
            $toggles[] = $section;
        }
        App::auth()->prefs()->toggles->put('unfolded_sections', implode(',', $toggles));

        return true;
    }

    /**
     * REST method to store dashboard module's positions (JSON)
     *
     * @param      array<string, string>     $get    The get
     * @param      array<string, string>     $post   The post
     *
     * @throws     Exception
     *
     * @return     true
     */
    public static function setDashboardPositions(array $get, array $post): bool
    {
        if (empty($post['id'])) {
            throw new Exception('No zone name');
        }
        if (empty($post['list'])) {
            throw new Exception('No sorted list of id');
        }

        $zone  = $post['id'];
        $order = $post['list'];

        App::auth()->prefs()->dashboard->put($zone, $order);

        return true;
    }

    /**
     * REST method to store dashboard module's positions (JSON)
     *
     * @param      array<string, string>     $get    The get
     * @param      array<string, string>     $post   The post
     *
     * @throws     Exception
     *
     * @return     array<string, string>
     */
    public static function setListsOptions(array $get, array $post): array
    {
        if (empty($post['id'])) {
            throw new Exception('No list name');
        }

        $sorts = UserPref::getUserFilters();

        if (!isset($sorts[$post['id']])) {
            throw new Exception('List name invalid');
        }

        $su = [];
        foreach ($sorts as $sort_type => $sort_data) {
            if (null !== $sort_data[1]) {
                $k                 = 'sort';
                $su[$sort_type][0] = $sort_type == $post['id'] && isset($post[$k]) && in_array($post[$k], $sort_data[1]) ? $post[$k] : $sort_data[2];
            }
            if (null !== $sort_data[3]) {
                $k                 = 'order';
                $su[$sort_type][1] = $sort_type == $post['id'] && isset($post[$k]) && in_array($post[$k], ['asc', 'desc']) ? $post[$k] : $sort_data[3];
            }
            if (null !== $sort_data[4]) {
                $k                 = 'nb';
                $su[$sort_type][2] = $sort_type == $post['id'] && isset($post[$k]) ? abs((int) $post[$k]) : $sort_data[4][1];
            }
        }

        App::auth()->prefs()->interface->put('sorts', $su, 'array');

        return [
            'msg' => __('List options saved'),
        ];
    }
}
