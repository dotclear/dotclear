<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @var dcCore $core
 */
require dirname(__FILE__) . '/../inc/admin/prepend.php';

$core->rest->addFunction('getPostsCount', ['dcRestMethods', 'getPostsCount']);
$core->rest->addFunction('getCommentsCount', ['dcRestMethods', 'getCommentsCount']);
$core->rest->addFunction('checkNewsUpdate', ['dcRestMethods', 'checkNewsUpdate']);
$core->rest->addFunction('checkCoreUpdate', ['dcRestMethods', 'checkCoreUpdate']);
$core->rest->addFunction('checkStoreUpdate', ['dcRestMethods', 'checkStoreUpdate']);
$core->rest->addFunction('getPostById', ['dcRestMethods', 'getPostById']);
$core->rest->addFunction('getCommentById', ['dcRestMethods', 'getCommentById']);
$core->rest->addFunction('quickPost', ['dcRestMethods', 'quickPost']);
$core->rest->addFunction('validatePostMarkup', ['dcRestMethods', 'validatePostMarkup']);
$core->rest->addFunction('getZipMediaContent', ['dcRestMethods', 'getZipMediaContent']);
$core->rest->addFunction('getMeta', ['dcRestMethods', 'getMeta']);
$core->rest->addFunction('delMeta', ['dcRestMethods', 'delMeta']);
$core->rest->addFunction('setPostMeta', ['dcRestMethods', 'setPostMeta']);
$core->rest->addFunction('searchMeta', ['dcRestMethods', 'searchMeta']);
$core->rest->addFunction('setSectionFold', ['dcRestMethods', 'setSectionFold']);
$core->rest->addFunction('getModuleById', ['dcRestMethods', 'getModuleById']);
$core->rest->addFunction('setDashboardPositions', ['dcRestMethods', 'setDashboardPositions']);
$core->rest->addFunction('setListsOptions', ['dcRestMethods', 'setListsOptions']);

$core->rest->serve();

/* Common REST methods */
class dcRestMethods
{
    /**
     * Serve method to get number of posts (whatever are their status) for current blog.
     *
     * @param     dcCore  $core     dcCore instance
     * @param     array   $get     cleaned $_GET
     */
    public static function getPostsCount($core, $get)
    {
        $count = $core->blog->getPosts([], true)->f(0);
        $str   = sprintf(__('%d post', '%d posts', $count), $count);

        $rsp      = new xmlTag('count');
        $rsp->ret = $str;

        return $rsp;
    }

    /**
     * Serve method to get number of comments (whatever are their status) for current blog.
     *
     * @param     dcCore  $core     dcCore instance
     * @param     array   $get     cleaned $_GET
     */
    public static function getCommentsCount($core, $get)
    {
        $count = $core->blog->getComments([], true)->f(0);
        $str   = sprintf(__('%d comment', '%d comments', $count), $count);

        $rsp      = new xmlTag('count');
        $rsp->ret = $str;

        return $rsp;
    }

    public static function checkNewsUpdate($core, $get)
    {
        # Dotclear news

        $rsp        = new xmlTag('news');
        $rsp->check = false;
        $ret        = __('Dotclear news not available');

        if ($core->auth->user_prefs->dashboard->dcnews) {
            try {
                if (empty($GLOBALS['__resources']['rss_news'])) {
                    throw new Exception();
                }
                $feed_reader = new feedReader();
                $feed_reader->setCacheDir(DC_TPL_CACHE);
                $feed_reader->setTimeout(2);
                $feed_reader->setUserAgent('Dotclear - https://dotclear.org/');
                $feed = $feed_reader->parse($GLOBALS['__resources']['rss_news']);
                if ($feed) {
                    $ret = '<div class="box medium dc-box" id="ajax-news"><h3>' . __('Dotclear news') . '</h3><dl id="news">';
                    $i   = 1;
                    foreach ($feed->items as $item) {
                        /* @phpstan-ignore-next-line */
                        $dt = isset($item->link) ? '<a href="' . $item->link . '" class="outgoing" title="' . $item->title . '">' .
                        /* @phpstan-ignore-next-line */
                        $item->title . ' <img src="images/outgoing-link.svg" alt="" /></a>' : $item->title;
                        $ret .= '<dt>' . $dt . '</dt>' .
                        '<dd><p><strong>' . dt::dt2str(__('%d %B %Y:'), $item->pubdate, 'Europe/Paris') . '</strong> ' .
                        '<em>' . text::cutString(html::clean($item->content), 120) . '...</em></p></dd>';
                        $i++;
                        if ($i > 2) {
                            break;
                        }
                    }
                    $ret .= '</dl></div>';
                    $rsp->check = true;
                }
            } catch (Exception $e) {
            }
        }
        $rsp->ret = $ret;

        return $rsp;
    }

    public static function checkCoreUpdate($core, $get)
    {
        # Dotclear updates notifications

        $rsp        = new xmlTag('update');
        $rsp->check = false;
        $ret        = __('Dotclear update not available');

        /* @phpstan-ignore-next-line */
        if ($core->auth->isSuperAdmin() && !DC_NOT_UPDATE && is_readable(DC_DIGESTS) && !$core->auth->user_prefs->dashboard->nodcupdate) {
            $updater      = new dcUpdate(DC_UPDATE_URL, 'dotclear', DC_UPDATE_VERSION, DC_TPL_CACHE . '/versions');
            $new_v        = $updater->check(DC_VERSION);
            $version_info = $new_v ? $updater->getInfoURL() : '';

            if ($updater->getNotify() && $new_v) {
                // Check PHP version required
                if (version_compare(phpversion(), $updater->getPHPVersion()) >= 0) {
                    $ret = '<div class="dc-update" id="ajax-update"><h3>' . sprintf(__('Dotclear %s is available!'), $new_v) . '</h3> ' .
                    '<p><a class="button submit" href="' . $core->adminurl->get('admin.update') . '">' . sprintf(__('Upgrade now'), $new_v) . '</a> ' .
                    '<a class="button" href="' . $core->adminurl->get('admin.update', ['hide_msg' => 1]) . '">' . __('Remind me later') . '</a>' .
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
                $rsp->check = true;
            } else {
                if (version_compare(phpversion(), DC_NEXT_REQUIRED_PHP, '<')) {
                    if (!$core->auth->user_prefs->interface->hidemoreinfo) {
                        $ret = '<p class="info">' .
                        sprintf(
                            __('The next versions of Dotclear will not support PHP version < %s, your\'s is currently %s'),
                            DC_NEXT_REQUIRED_PHP,
                            phpversion()
                        ) .
                        '</p>';
                        $rsp->check = true;
                    }
                }
            }
        }
        $rsp->ret = $ret;

        return $rsp;
    }

    public static function checkStoreUpdate($core, $get, $post)
    {
        # Dotclear store updates notifications

        $rsp        = new xmlTag('update');
        $rsp->check = false;
        $rsp->nb    = 0;
        $ret        = __('No updates are available');
        $mod        = '';
        $url        = '';

        if (empty($post['store'])) {
            throw new Exception('No store type');
        }

        if ($post['store'] == 'themes') {
            $mod = new dcThemes($core);
            $mod->loadModules($core->blog->themes_path, null);
            $url = $core->blog->settings->system->store_theme_url;
        } elseif ($post['store'] == 'plugins') {
            $mod = $core->plugins;
            $url = $core->blog->settings->system->store_plugin_url;
        } else {

            # --BEHAVIOR-- restCheckStoreUpdate
            $core->callBehavior('restCheckStoreUpdate', $core, $post['store'], [& $mod], [& $url]);

            if (empty($mod) || empty($url)) {
                throw new Exception('Unknown store type');
            }
        }

        $repo = new dcStore($mod, $url);
        $upd  = $repo->get(true);
        if (!empty($upd)) {
            $ret        = sprintf(__('An update is available', '%s updates are available.', count($upd)), count($upd));
            $rsp->check = true;
            $rsp->nb    = count($upd);
        }

        $rsp->ret = $ret;

        return $rsp;
    }

    public static function getPostById($core, $get)
    {
        if (empty($get['id'])) {
            throw new Exception('No post ID');
        }

        $params = ['post_id' => (int) $get['id']];

        if (isset($get['post_type'])) {
            $params['post_type'] = $get['post_type'];
        }

        $rs = $core->blog->getPosts($params);

        if ($rs->isEmpty()) {
            throw new Exception('No post for this ID');
        }

        $rsp     = new xmlTag('post');
        $rsp->id = $rs->post_id;

        $rsp->blog_id($rs->blog_id);
        $rsp->user_id($rs->user_id);
        $rsp->cat_id($rs->cat_id);
        $rsp->post_dt($rs->post_dt);
        $rsp->post_creadt($rs->post_creadt);
        $rsp->post_upddt($rs->post_upddt);
        $rsp->post_format($rs->post_format);
        $rsp->post_url($rs->post_url);
        $rsp->post_lang($rs->post_lang);
        $rsp->post_title($rs->post_title);
        $rsp->post_excerpt($rs->post_excerpt);
        $rsp->post_excerpt_xhtml($rs->post_excerpt_xhtml);
        $rsp->post_content($rs->post_content);
        $rsp->post_content_xhtml($rs->post_content_xhtml);
        $rsp->post_notes($rs->post_notes);
        $rsp->post_status($rs->post_status);
        $rsp->post_selected($rs->post_selected);
        $rsp->post_open_comment($rs->post_open_comment);
        $rsp->post_open_tb($rs->post_open_tb);
        $rsp->nb_comment($rs->nb_comment);
        $rsp->nb_trackback($rs->nb_trackback);
        $rsp->user_name($rs->user_name);
        $rsp->user_firstname($rs->user_firstname);
        $rsp->user_displayname($rs->user_displayname);
        $rsp->user_email($rs->user_email);
        $rsp->user_url($rs->user_url);
        $rsp->cat_title($rs->cat_title);
        $rsp->cat_url($rs->cat_url);

        $rsp->post_display_content($rs->getContent(true));
        $rsp->post_display_excerpt($rs->getExcerpt(true));

        $metaTag = new xmlTag('meta');
        if (($meta = @unserialize($rs->post_meta)) !== false) {
            foreach ($meta as $K => $V) {
                foreach ($V as $v) {
                    $metaTag->$K($v);
                }
            }
        }
        $rsp->post_meta($metaTag);

        return $rsp;
    }

    public static function getCommentById($core, $get)
    {
        if (empty($get['id'])) {
            throw new Exception('No comment ID');
        }

        $rs = $core->blog->getComments(['comment_id' => (int) $get['id']]);

        if ($rs->isEmpty()) {
            throw new Exception('No comment for this ID');
        }

        $rsp     = new xmlTag('post');
        $rsp->id = $rs->comment_id;

        $rsp->comment_dt($rs->comment_dt);
        $rsp->comment_upddt($rs->comment_upddt);
        $rsp->comment_author($rs->comment_author);
        $rsp->comment_site($rs->comment_site);
        $rsp->comment_content($rs->comment_content);
        $rsp->comment_trackback($rs->comment_trackback);
        $rsp->comment_status($rs->comment_status);
        $rsp->post_title($rs->post_title);
        $rsp->post_url($rs->post_url);
        $rsp->post_id($rs->post_id);
        $rsp->post_dt($rs->post_dt);
        $rsp->user_id($rs->user_id);

        $rsp->comment_display_content($rs->getContent(true));

        if ($core->auth->userID()) {
            $rsp->comment_ip($rs->comment_ip);
            $rsp->comment_email($rs->comment_email);
            $rsp->comment_spam_disp(dcAntispam::statusMessage($rs));
        }

        return $rsp;
    }

    public static function quickPost($core, $get, $post)
    {
        # Create category
        if (!empty($post['new_cat_title']) && $core->auth->check('categories', $core->blog->id)) {
            $cur_cat            = $core->con->openCursor($core->prefix . 'category');
            $cur_cat->cat_title = $post['new_cat_title'];
            $cur_cat->cat_url   = '';

            $parent_cat = !empty($post['new_cat_parent']) ? $post['new_cat_parent'] : '';

            # --BEHAVIOR-- adminBeforeCategoryCreate
            $core->callBehavior('adminBeforeCategoryCreate', $cur_cat);

            $post['cat_id'] = $core->blog->addCategory($cur_cat, (int) $parent_cat);

            # --BEHAVIOR-- adminAfterCategoryCreate
            $core->callBehavior('adminAfterCategoryCreate', $cur_cat, $post['cat_id']);
        }

        $cur = $core->con->openCursor($core->prefix . 'post');

        $cur->post_title        = !empty($post['post_title']) ? $post['post_title'] : '';
        $cur->user_id           = $core->auth->userID();
        $cur->post_content      = !empty($post['post_content']) ? $post['post_content'] : '';
        $cur->cat_id            = !empty($post['cat_id']) ? (int) $post['cat_id'] : null;
        $cur->post_format       = !empty($post['post_format']) ? $post['post_format'] : 'xhtml';
        $cur->post_lang         = !empty($post['post_lang']) ? $post['post_lang'] : '';
        $cur->post_status       = !empty($post['post_status']) ? (int) $post['post_status'] : 0;
        $cur->post_open_comment = (int) $core->blog->settings->system->allow_comments;
        $cur->post_open_tb      = (int) $core->blog->settings->system->allow_trackbacks;

        # --BEHAVIOR-- adminBeforePostCreate
        $core->callBehavior('adminBeforePostCreate', $cur);

        $return_id = $core->blog->addPost($cur);

        # --BEHAVIOR-- adminAfterPostCreate
        $core->callBehavior('adminAfterPostCreate', $cur, $return_id);

        $rsp     = new xmlTag('post');
        $rsp->id = $return_id;

        $post = $core->blog->getPosts(['post_id' => $return_id]);

        $rsp->post_status = $post->post_status;
        $rsp->post_url    = $post->getURL();

        return $rsp;
    }

    public static function validatePostMarkup($core, $get, $post)
    {
        if (!isset($post['excerpt'])) {
            throw new Exception('No entry excerpt');
        }

        if (!isset($post['content'])) {
            throw new Exception('No entry content');
        }

        if (empty($post['format'])) {
            throw new Exception('No entry format');
        }

        if (!isset($post['lang'])) {
            throw new Exception('No entry lang');
        }

        $excerpt       = $post['excerpt'];
        $excerpt_xhtml = '';
        $content       = $post['content'];
        $content_xhtml = '';
        $format        = $post['format'];
        $lang          = $post['lang'];

        $core->blog->setPostContent(0, $format, $lang, $excerpt, $excerpt_xhtml, $content, $content_xhtml);

        $rsp = new xmlTag('result');

        $v = htmlValidator::validate($excerpt_xhtml . $content_xhtml);

        $rsp->valid($v['valid']);
        $rsp->errors($v['errors']);

        return $rsp;
    }

    public static function getZipMediaContent($core, $get, $post)
    {
        if (empty($get['id'])) {
            throw new Exception('No media ID');
        }

        $id = (int) $get['id'];

        if (!$core->auth->check('media,media_admin', $core->blog)) {
            throw new Exception('Permission denied');
        }

        $file = null;

        try {
            $core->media = new dcMedia($core);
            $file        = $core->media->getFile($id);
        } catch (Exception $e) {
        }

        if ($file === null || $file->type != 'application/zip' || !$file->editable) {
            throw new Exception('Not a valid file');
        }

        $rsp     = new xmlTag('result');
        $content = $core->media->getZipContent($file);

        foreach ($content as $k => $v) {
            $rsp->file($k);
        }

        return $rsp;
    }

    public static function getMeta($core, $get)
    {
        $postid   = !empty($get['postId']) ? $get['postId'] : null;
        $limit    = !empty($get['limit']) ? $get['limit'] : null;
        $metaId   = !empty($get['metaId']) ? $get['metaId'] : null;
        $metaType = !empty($get['metaType']) ? $get['metaType'] : null;

        $sortby = !empty($get['sortby']) ? $get['sortby'] : 'meta_type,asc';

        $rs = $core->meta->getMetadata([
            'meta_type' => $metaType,
            'limit'     => $limit,
            'meta_id'   => $metaId,
            'post_id'   => $postid]);
        $rs = $core->meta->computeMetaStats($rs);

        $sortby = explode(',', $sortby);
        $sort   = $sortby[0];
        $order  = $sortby[1] ?? 'asc';

        switch ($sort) {
            case 'metaId':
                $sort = 'meta_id_lower';

                break;
            case 'count':
                $sort = 'count';

                break;
            case 'metaType':
                $sort = 'meta_type';

                break;
            default:
                $sort = 'meta_type';
        }

        $rs->sort($sort, $order);

        $rsp = new xmlTag();

        while ($rs->fetch()) {
            $metaTag               = new xmlTag('meta');
            $metaTag->type         = $rs->meta_type;
            $metaTag->uri          = rawurlencode($rs->meta_id);
            $metaTag->count        = $rs->count;
            $metaTag->percent      = $rs->percent;
            $metaTag->roundpercent = $rs->roundpercent;
            $metaTag->CDATA($rs->meta_id);

            $rsp->insertNode($metaTag);
        }

        return $rsp;
    }

    public static function setPostMeta($core, $get, $post)
    {
        if (empty($post['postId'])) {
            throw new Exception('No post ID');
        }

        if (empty($post['meta']) && $post['meta'] != '0') {
            throw new Exception('No meta');
        }

        if (empty($post['metaType'])) {
            throw new Exception('No meta type');
        }

        # Get previous meta for post
        $post_meta = $core->meta->getMetadata([
            'meta_type' => $post['metaType'],
            'post_id'   => $post['postId']]);
        $pm = [];
        while ($post_meta->fetch()) {
            $pm[] = $post_meta->meta_id;
        }

        foreach ($core->meta->splitMetaValues($post['meta']) as $m) {
            if (!in_array($m, $pm)) {
                $core->meta->setPostMeta($post['postId'], $post['metaType'], $m);
            }
        }

        return true;
    }

    public static function delMeta($core, $get, $post)
    {
        if (empty($post['postId'])) {
            throw new Exception('No post ID');
        }

        if (empty($post['metaId']) && $post['metaId'] != '0') {
            throw new Exception('No meta ID');
        }

        if (empty($post['metaType'])) {
            throw new Exception('No meta type');
        }

        $core->meta->delPostMeta($post['postId'], $post['metaType'], $post['metaId']);

        return true;
    }

    public static function searchMeta($core, $get)
    {
        $q        = !empty($get['q']) ? $get['q'] : null;
        $metaType = !empty($get['metaType']) ? $get['metaType'] : null;

        $sortby = !empty($get['sortby']) ? $get['sortby'] : 'meta_type,asc';

        $rs = $core->meta->getMetadata(['meta_type' => $metaType]);
        $rs = $core->meta->computeMetaStats($rs);

        $sortby = explode(',', $sortby);
        $sort   = $sortby[0];
        $order  = $sortby[1] ?? 'asc';

        switch ($sort) {
            case 'metaId':
                $sort = 'meta_id_lower';

                break;
            case 'count':
                $sort = 'count';

                break;
            case 'metaType':
                $sort = 'meta_type';

                break;
            default:
                $sort = 'meta_type';
        }

        $rs->sort($sort, $order);

        $rsp = new xmlTag();

        while ($rs->fetch()) {
            if (stripos($rs->meta_id, $q) === 0) {
                $metaTag               = new xmlTag('meta');
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

    public static function setSectionFold($core, $get, $post)
    {
        if (empty($post['section'])) {
            throw new Exception('No section name');
        }
        if ($core->auth->user_prefs->toggles === null) {
            $core->auth->user_prefs->addWorkspace('toggles');
        }
        $section = $post['section'];
        $status  = isset($post['value']) && ($post['value'] != 0);
        if ($core->auth->user_prefs->toggles->prefExists('unfolded_sections')) {
            $toggles = explode(',', trim($core->auth->user_prefs->toggles->unfolded_sections));
        } else {
            $toggles = [];
        }
        $k = array_search($section, $toggles);
        if ($status) {
            // true == Fold section ==> remove it from unfolded list
            if ($k !== false) {
                unset($toggles[$k]);
            }
        } else {
            // false == unfold section ==> add it to unfolded list
            if ($k === false) {
                $toggles[] = $section;
            };
        }
        $core->auth->user_prefs->toggles->put('unfolded_sections', join(',', $toggles));

        return true;
    }

    public static function setDashboardPositions($core, $get, $post)
    {
        if (empty($post['id'])) {
            throw new Exception('No zone name');
        }
        if (empty($post['list'])) {
            throw new Exception('No sorted list of id');
        }

        if ($core->auth->user_prefs->dashboard === null) {
            $core->auth->user_prefs->addWorkspace('dashboard');
        }

        $zone  = $post['id'];
        $order = $post['list'];

        $core->auth->user_prefs->dashboard->put($zone, $order);

        return true;
    }

    public static function setListsOptions($core, $get, $post)
    {
        if (empty($post['id'])) {
            throw new Exception('No list name');
        }

        $sorts = adminUserPref::getUserFilters();

        if (!isset($sorts[$post['id']])) {
            throw new Exception('List name invalid');
        }

        $res = new xmlTag('result');

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
        if ($core->auth->user_prefs->interface === null) {
            $core->auth->user_prefs->addWorkspace('interface');
        }
        $core->auth->user_prefs->interface->put('sorts', $su, 'array');

        $res->msg = __('List options saved');

        return $res;
    }

    public static function getModuleById($core, $get, $post)
    {
        if (empty($get['id'])) {
            throw new Exception('No module ID');
        }
        if (empty($get['list'])) {
            throw new Exception('No list ID');
        }

        $id     = $get['id'];
        $list   = $get['list'];
        $module = [];

        if ($list == 'plugin-activate') {
            $modules = $core->plugins->getModules();
            if (empty($modules) || !isset($modules[$id])) {
                throw new Exception('Unknown module ID');
            }
            $module = $modules[$id];
        } elseif ($list == 'plugin-new') {
            $store = new dcStore(
                $core->plugins,
                $core->blog->settings->system->store_plugin_url
            );
            $store->check();

            $modules = $store->get();
            if (empty($modules) || !isset($modules[$id])) {
                throw new Exception('Unknown module ID');
            }
            $module = $modules[$id];
        }
        // behavior not implemented yet

        if (empty($module)) {
            throw new Exception('Unknown module ID');
        }

        $module = adminModulesList::sanitizeModule($id, $module);

        $rsp     = new xmlTag('module');
        $rsp->id = $id;

        foreach ($module as $k => $v) {
            $rsp->{$k}((string) $v);
        }

        return $rsp;
    }
}
