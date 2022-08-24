<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

dcCore::app()->rest->addFunction('getPostsCount', ['dcRestMethods', 'getPostsCount']);
dcCore::app()->rest->addFunction('getCommentsCount', ['dcRestMethods', 'getCommentsCount']);
dcCore::app()->rest->addFunction('checkNewsUpdate', ['dcRestMethods', 'checkNewsUpdate']);
dcCore::app()->rest->addFunction('checkCoreUpdate', ['dcRestMethods', 'checkCoreUpdate']);
dcCore::app()->rest->addFunction('checkStoreUpdate', ['dcRestMethods', 'checkStoreUpdate']);
dcCore::app()->rest->addFunction('getPostById', ['dcRestMethods', 'getPostById']);
dcCore::app()->rest->addFunction('getCommentById', ['dcRestMethods', 'getCommentById']);
dcCore::app()->rest->addFunction('quickPost', ['dcRestMethods', 'quickPost']);
dcCore::app()->rest->addFunction('validatePostMarkup', ['dcRestMethods', 'validatePostMarkup']);
dcCore::app()->rest->addFunction('getZipMediaContent', ['dcRestMethods', 'getZipMediaContent']);
dcCore::app()->rest->addFunction('getMeta', ['dcRestMethods', 'getMeta']);
dcCore::app()->rest->addFunction('delMeta', ['dcRestMethods', 'delMeta']);
dcCore::app()->rest->addFunction('setPostMeta', ['dcRestMethods', 'setPostMeta']);
dcCore::app()->rest->addFunction('searchMeta', ['dcRestMethods', 'searchMeta']);
dcCore::app()->rest->addFunction('setSectionFold', ['dcRestMethods', 'setSectionFold']);
dcCore::app()->rest->addFunction('getModuleById', ['dcRestMethods', 'getModuleById']);
dcCore::app()->rest->addFunction('setDashboardPositions', ['dcRestMethods', 'setDashboardPositions']);
dcCore::app()->rest->addFunction('setListsOptions', ['dcRestMethods', 'setListsOptions']);

dcCore::app()->rest->serve();

/* Common REST methods */
class dcRestMethods
{
    /**
     * Serve method to get number of posts (whatever are their status) for current blog.
     */
    public static function getPostsCount()
    {
        $count = dcCore::app()->blog->getPosts([], true)->f(0);
        $str   = sprintf(__('%d post', '%d posts', $count), $count);

        $rsp      = new xmlTag('count');
        $rsp->ret = $str;

        return $rsp;
    }

    /**
     * Serve method to get number of comments (whatever are their status) for current blog.
     */
    public static function getCommentsCount()
    {
        $count = dcCore::app()->blog->getComments([], true)->f(0);
        $str   = sprintf(__('%d comment', '%d comments', $count), $count);

        $rsp      = new xmlTag('count');
        $rsp->ret = $str;

        return $rsp;
    }

    public static function checkNewsUpdate()
    {
        # Dotclear news

        $rsp        = new xmlTag('news');
        $rsp->check = false;
        $ret        = __('Dotclear news not available');

        if (dcCore::app()->auth->user_prefs->dashboard->dcnews) {
            try {
                if (empty(dcCore::app()->resources['rss_news'])) {
                    throw new Exception();
                }
                $feed_reader = new feedReader();
                $feed_reader->setCacheDir(DC_TPL_CACHE);
                $feed_reader->setTimeout(2);
                $feed_reader->setUserAgent('Dotclear - https://dotclear.org/');
                $feed = $feed_reader->parse(dcCore::app()->resources['rss_news']);
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
                // Ignore exceptions
            }
        }
        $rsp->ret = $ret;

        return $rsp;
    }

    public static function checkCoreUpdate()
    {
        # Dotclear updates notifications

        $rsp        = new xmlTag('update');
        $rsp->check = false;
        $ret        = __('Dotclear update not available');

        if (dcCore::app()->auth->isSuperAdmin() && !DC_NOT_UPDATE && is_readable(DC_DIGESTS) && !dcCore::app()->auth->user_prefs->dashboard->nodcupdate) {
            $updater      = new dcUpdate(DC_UPDATE_URL, 'dotclear', DC_UPDATE_VERSION, DC_TPL_CACHE . '/versions');
            $new_v        = $updater->check(DC_VERSION);
            $version_info = $new_v ? $updater->getInfoURL() : '';

            if ($updater->getNotify() && $new_v) {
                // Check PHP version required
                if (version_compare(phpversion(), $updater->getPHPVersion()) >= 0) {
                    $ret = '<div class="dc-update" id="ajax-update"><h3>' . sprintf(__('Dotclear %s is available!'), $new_v) . '</h3> ' .
                    '<p><a class="button submit" href="' . dcCore::app()->adminurl->get('admin.update') . '">' . sprintf(__('Upgrade now'), $new_v) . '</a> ' .
                    '<a class="button" href="' . dcCore::app()->adminurl->get('admin.update', ['hide_msg' => 1]) . '">' . __('Remind me later') . '</a>' .
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
                    if (!dcCore::app()->auth->user_prefs->interface->hidemoreinfo) {
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

    public static function checkStoreUpdate(dcCore $core, $get, $post)
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
            $mod = new dcThemes(dcCore::app());
            $mod->loadModules(dcCore::app()->blog->themes_path, null);
            $url = dcCore::app()->blog->settings->system->store_theme_url;
        } elseif ($post['store'] == 'plugins') {
            $mod = dcCore::app()->plugins;
            $url = dcCore::app()->blog->settings->system->store_plugin_url;
        } else {

            # --BEHAVIOR-- restCheckStoreUpdate
            dcCore::app()->callBehavior('restCheckStoreUpdate', dcCore::app(), $post['store'], [& $mod], [& $url]);

            if (empty($mod) || empty($url)) {   // @phpstan-ignore-line
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

    public static function getPostById(dcCore $core, $get)
    {
        if (empty($get['id'])) {
            throw new Exception('No post ID');
        }

        $params = ['post_id' => (int) $get['id']];

        if (isset($get['post_type'])) {
            $params['post_type'] = $get['post_type'];
        }

        $rs = dcCore::app()->blog->getPosts($params);

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

    public static function getCommentById(dcCore $core, $get)
    {
        if (empty($get['id'])) {
            throw new Exception('No comment ID');
        }

        $rs = dcCore::app()->blog->getComments(['comment_id' => (int) $get['id']]);

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

        if (dcCore::app()->auth->userID()) {
            $rsp->comment_ip($rs->comment_ip);
            $rsp->comment_email($rs->comment_email);
            $rsp->comment_spam_disp(dcAntispam::statusMessage($rs));
        }

        return $rsp;
    }

    public static function quickPost(dcCore $core, $get, $post)
    {
        # Create category
        if (!empty($post['new_cat_title']) && dcCore::app()->auth->check('categories', dcCore::app()->blog->id)) {
            $cur_cat            = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'category');
            $cur_cat->cat_title = $post['new_cat_title'];
            $cur_cat->cat_url   = '';

            $parent_cat = !empty($post['new_cat_parent']) ? $post['new_cat_parent'] : '';

            # --BEHAVIOR-- adminBeforeCategoryCreate
            dcCore::app()->callBehavior('adminBeforeCategoryCreate', $cur_cat);

            $post['cat_id'] = dcCore::app()->blog->addCategory($cur_cat, (int) $parent_cat);

            # --BEHAVIOR-- adminAfterCategoryCreate
            dcCore::app()->callBehavior('adminAfterCategoryCreate', $cur_cat, $post['cat_id']);
        }

        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'post');

        $cur->post_title        = !empty($post['post_title']) ? $post['post_title'] : '';
        $cur->user_id           = dcCore::app()->auth->userID();
        $cur->post_content      = !empty($post['post_content']) ? $post['post_content'] : '';
        $cur->cat_id            = !empty($post['cat_id']) ? (int) $post['cat_id'] : null;
        $cur->post_format       = !empty($post['post_format']) ? $post['post_format'] : 'xhtml';
        $cur->post_lang         = !empty($post['post_lang']) ? $post['post_lang'] : '';
        $cur->post_status       = !empty($post['post_status']) ? (int) $post['post_status'] : dcBlog::POST_UNPUBLISHED;
        $cur->post_open_comment = (int) dcCore::app()->blog->settings->system->allow_comments;
        $cur->post_open_tb      = (int) dcCore::app()->blog->settings->system->allow_trackbacks;

        # --BEHAVIOR-- adminBeforePostCreate
        dcCore::app()->callBehavior('adminBeforePostCreate', $cur);

        $return_id = dcCore::app()->blog->addPost($cur);

        # --BEHAVIOR-- adminAfterPostCreate
        dcCore::app()->callBehavior('adminAfterPostCreate', $cur, $return_id);

        $rsp     = new xmlTag('post');
        $rsp->id = $return_id;

        $post = dcCore::app()->blog->getPosts(['post_id' => $return_id]);

        $rsp->post_status = $post->post_status;
        $rsp->post_url    = $post->getURL();

        return $rsp;
    }

    public static function validatePostMarkup(dcCore $core, $get, $post)
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

        dcCore::app()->blog->setPostContent(0, $format, $lang, $excerpt, $excerpt_xhtml, $content, $content_xhtml);

        $rsp = new xmlTag('result');

        $v = htmlValidator::validate($excerpt_xhtml . $content_xhtml);

        $rsp->valid($v['valid']);
        $rsp->errors($v['errors']);

        return $rsp;
    }

    public static function getZipMediaContent(dcCore $core, $get)
    {
        if (empty($get['id'])) {
            throw new Exception('No media ID');
        }

        $id = (int) $get['id'];

        if (!dcCore::app()->auth->check('media,media_admin', dcCore::app()->blog)) {
            throw new Exception('Permission denied');
        }

        $file = null;

        try {
            dcCore::app()->media = new dcMedia(dcCore::app());
            $file                = dcCore::app()->media->getFile($id);
        } catch (Exception $e) {
            // Ignore exceptions
        }

        if ($file === null || $file->type != 'application/zip' || !$file->editable) {
            throw new Exception('Not a valid file');
        }

        $rsp     = new xmlTag('result');
        $content = dcCore::app()->media->getZipContent($file);

        foreach ($content as $k => $v) {
            $rsp->file($k);
        }

        return $rsp;
    }

    public static function getMeta(dcCore $core, $get)
    {
        $postid   = !empty($get['postId']) ? $get['postId'] : null;
        $limit    = !empty($get['limit']) ? $get['limit'] : null;
        $metaId   = !empty($get['metaId']) ? $get['metaId'] : null;
        $metaType = !empty($get['metaType']) ? $get['metaType'] : null;

        $sortby = !empty($get['sortby']) ? $get['sortby'] : 'meta_type,asc';

        $rs = dcCore::app()->meta->getMetadata([
            'meta_type' => $metaType,
            'limit'     => $limit,
            'meta_id'   => $metaId,
            'post_id'   => $postid, ]);
        $rs = dcCore::app()->meta->computeMetaStats($rs);

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

    public static function setPostMeta(dcCore $core, $get, $post)
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
        $post_meta = dcCore::app()->meta->getMetadata([
            'meta_type' => $post['metaType'],
            'post_id'   => $post['postId'], ]);
        $pm = [];
        while ($post_meta->fetch()) {
            $pm[] = $post_meta->meta_id;
        }

        foreach (dcCore::app()->meta->splitMetaValues($post['meta']) as $m) {
            if (!in_array($m, $pm)) {
                dcCore::app()->meta->setPostMeta($post['postId'], $post['metaType'], $m);
            }
        }

        return true;
    }

    public static function delMeta(dcCore $core, $get, $post)
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

        dcCore::app()->meta->delPostMeta($post['postId'], $post['metaType'], $post['metaId']);

        return true;
    }

    public static function searchMeta(dcCore $core, $get)
    {
        $q        = !empty($get['q']) ? $get['q'] : null;
        $metaType = !empty($get['metaType']) ? $get['metaType'] : null;

        $sortby = !empty($get['sortby']) ? $get['sortby'] : 'meta_type,asc';

        $rs = dcCore::app()->meta->getMetadata(['meta_type' => $metaType]);
        $rs = dcCore::app()->meta->computeMetaStats($rs);

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

    public static function setSectionFold(dcCore $core, $get, $post)
    {
        if (empty($post['section'])) {
            throw new Exception('No section name');
        }
        if (dcCore::app()->auth->user_prefs->toggles === null) {
            dcCore::app()->auth->user_prefs->addWorkspace('toggles');
        }
        $section = $post['section'];
        $status  = isset($post['value']) && ($post['value'] != 0);
        if (dcCore::app()->auth->user_prefs->toggles->prefExists('unfolded_sections')) {
            $toggles = explode(',', trim((string) dcCore::app()->auth->user_prefs->toggles->unfolded_sections));
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
            }
        }
        dcCore::app()->auth->user_prefs->toggles->put('unfolded_sections', join(',', $toggles));

        return true;
    }

    public static function setDashboardPositions(dcCore $core, $get, $post)
    {
        if (empty($post['id'])) {
            throw new Exception('No zone name');
        }
        if (empty($post['list'])) {
            throw new Exception('No sorted list of id');
        }

        if (dcCore::app()->auth->user_prefs->dashboard === null) {
            dcCore::app()->auth->user_prefs->addWorkspace('dashboard');
        }

        $zone  = $post['id'];
        $order = $post['list'];

        dcCore::app()->auth->user_prefs->dashboard->put($zone, $order);

        return true;
    }

    public static function setListsOptions(dcCore $core, $get, $post)
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
        if (dcCore::app()->auth->user_prefs->interface === null) {
            dcCore::app()->auth->user_prefs->addWorkspace('interface');
        }
        dcCore::app()->auth->user_prefs->interface->put('sorts', $su, 'array');

        $res->msg = __('List options saved');

        return $res;
    }

    public static function getModuleById(dcCore $core, $get)
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
            $modules = dcCore::app()->plugins->getModules();
            if (empty($modules) || !isset($modules[$id])) {
                throw new Exception('Unknown module ID');
            }
            $module = $modules[$id];
        } elseif ($list == 'plugin-new') {
            $store = new dcStore(
                dcCore::app()->plugins,
                dcCore::app()->blog->settings->system->store_plugin_url
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
