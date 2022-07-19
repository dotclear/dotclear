<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->addBehavior('coreBlogBeforeGetPosts', ['publicPages', 'coreBlogBeforeGetPosts']);

# Localized string we find in template
__('Published on');
__('This page\'s comments feed');

require __DIR__ . '/_widgets.php';

class publicPages
{
    public static function coreBlogBeforeGetPosts($params)
    {
        if (dcCore::app()->url->type == 'search') {
            // Add page post type for searching
            if (isset($params['post_type'])) {
                if (!is_array($params['post_type'])) {
                    // Convert it in array
                    $params['post_type'] = [$params['post_type']];
                }
                if (!in_array('page', $params['post_type'])) {
                    // Add page post type
                    $params['post_type'][] = 'page';
                }
            } else {
                // Dont miss default post type (aka post)
                $params['post_type'] = ['post', 'page'];
            }
        }
    }
}

class urlPages extends dcUrlHandlers
{
    public static function pages($args)
    {
        if ($args == '') {
            # No page was specified.
            self::p404();
        } else {
            $_ctx = &$GLOBALS['_ctx'];

            dcCore::app()->blog->withoutPassword(false);

            $params = new ArrayObject([
                'post_type' => 'page',
                'post_url'  => $args, ]);

            dcCore::app()->callBehavior('publicPagesBeforeGetPosts', $params, $args);

            $_ctx->posts = dcCore::app()->blog->getPosts($params);

            $_ctx->comment_preview               = new ArrayObject();
            $_ctx->comment_preview['content']    = '';
            $_ctx->comment_preview['rawcontent'] = '';
            $_ctx->comment_preview['name']       = '';
            $_ctx->comment_preview['mail']       = '';
            $_ctx->comment_preview['site']       = '';
            $_ctx->comment_preview['preview']    = false;
            $_ctx->comment_preview['remember']   = false;

            dcCore::app()->blog->withoutPassword(true);

            if ($_ctx->posts->isEmpty()) {
                # The specified page does not exist.
                self::p404();
            } else {
                $post_id       = $_ctx->posts->post_id;
                $post_password = $_ctx->posts->post_password;

                # Password protected entry
                if ($post_password != '' && !$_ctx->preview) {
                    # Get passwords cookie
                    if (isset($_COOKIE['dc_passwd'])) {
                        $pwd_cookie = json_decode($_COOKIE['dc_passwd']);
                        if ($pwd_cookie === null) {
                            $pwd_cookie = [];
                        } else {
                            $pwd_cookie = (array) $pwd_cookie;
                        }
                    } else {
                        $pwd_cookie = [];
                    }

                    # Check for match
                    # Note: We must prefix post_id key with '#'' in pwd_cookie array in order to avoid integer conversion
                    # because MyArray["12345"] is treated as MyArray[12345]
                    if ((!empty($_POST['password']) && $_POST['password'] == $post_password)
                        || (isset($pwd_cookie['#' . $post_id]) && $pwd_cookie['#' . $post_id] == $post_password)) {
                        $pwd_cookie['#' . $post_id] = $post_password;
                        setcookie('dc_passwd', json_encode($pwd_cookie), 0, '/');
                    } else {
                        self::serveDocument('password-form.html', 'text/html', false);

                        return;
                    }
                }

                $post_comment = isset($_POST['c_name']) && isset($_POST['c_mail']) && isset($_POST['c_site']) && isset($_POST['c_content']) && $_ctx->posts->commentsActive();

                # Posting a comment
                if ($post_comment) {
                    # Spam trap
                    if (!empty($_POST['f_mail'])) {
                        http::head(412, 'Precondition Failed');
                        header('Content-Type: text/plain');
                        echo 'So Long, and Thanks For All the Fish';
                        # Exits immediately the application to preserve the server.
                        exit;
                    }

                    $name    = $_POST['c_name'];
                    $mail    = $_POST['c_mail'];
                    $site    = $_POST['c_site'];
                    $content = $_POST['c_content'];
                    $preview = !empty($_POST['preview']);

                    if ($content != '') {
                        # --BEHAVIOR-- publicBeforeCommentTransform
                        $buffer = dcCore::app()->callBehavior('publicBeforeCommentTransform', $content);
                        if ($buffer != '') {
                            $content = $buffer;
                        } else {
                            if (dcCore::app()->blog->settings->system->wiki_comments) {
                                dcCore::app()->initWikiComment();
                            } else {
                                dcCore::app()->initWikiSimpleComment();
                            }
                            $content = dcCore::app()->wikiTransform($content);
                        }
                        $content = dcCore::app()->HTMLfilter($content);
                    }

                    $_ctx->comment_preview['content']    = $content;
                    $_ctx->comment_preview['rawcontent'] = $_POST['c_content'];
                    $_ctx->comment_preview['name']       = $name;
                    $_ctx->comment_preview['mail']       = $mail;
                    $_ctx->comment_preview['site']       = $site;

                    if ($preview) {
                        # --BEHAVIOR-- publicBeforeCommentPreview
                        dcCore::app()->callBehavior('publicBeforeCommentPreview', $_ctx->comment_preview);

                        $_ctx->comment_preview['preview'] = true;
                    } else {
                        # Post the comment
                        $cur                  = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'comment');
                        $cur->comment_author  = $name;
                        $cur->comment_site    = html::clean($site);
                        $cur->comment_email   = html::clean($mail);
                        $cur->comment_content = $content;
                        $cur->post_id         = $_ctx->posts->post_id;
                        $cur->comment_status  = dcCore::app()->blog->settings->system->comments_pub ? 1 : -1;
                        $cur->comment_ip      = http::realIP();

                        $redir = $_ctx->posts->getURL();
                        $redir .= dcCore::app()->blog->settings->system->url_scan == 'query_string' ? '&' : '?';

                        try {
                            if (!text::isEmail($cur->comment_email)) {
                                throw new Exception(__('You must provide a valid email address.'));
                            }

                            # --BEHAVIOR-- publicBeforeCommentCreate
                            dcCore::app()->callBehavior('publicBeforeCommentCreate', $cur);
                            if ($cur->post_id) {
                                $comment_id = dcCore::app()->blog->addComment($cur);

                                # --BEHAVIOR-- publicAfterCommentCreate
                                dcCore::app()->callBehavior('publicAfterCommentCreate', $cur, $comment_id);
                            }

                            if ($cur->comment_status == 1) {
                                $redir_arg = 'pub=1';
                            } else {
                                $redir_arg = 'pub=0';
                            }

                            header('Location: ' . $redir . $redir_arg);
                        } catch (Exception $e) {
                            $_ctx->form_error = $e->getMessage();
                        }
                    }
                }

                # The entry
                if ($_ctx->posts->trackbacksActive()) {
                    header('X-Pingback: ' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('xmlrpc', dcCore::app()->blog->id));
                }

                $tplset = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'tplset');
                if (!empty($tplset) && is_dir(__DIR__ . '/default-templates/' . $tplset)) {
                    dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates/' . $tplset);
                } else {
                    dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates/' . DC_DEFAULT_TPLSET);
                }
                self::serveDocument('page.html');
            }
        }
    }

    public static function pagespreview($args)
    {
        $_ctx = $GLOBALS['_ctx'];

        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', $args, $m)) {
            # The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!dcCore::app()->auth->checkUser($user_id, null, $user_key)) {
                # The user has no access to the entry.
                self::p404();
            } else {
                $_ctx->preview = true;
                if (defined('DC_ADMIN_URL')) {
                    $_ctx->xframeoption = DC_ADMIN_URL;
                }

                self::pages($post_url);
            }
        }
    }
}

class tplPages
{
    # Widget function
    public static function pagesWidget($w)
    {
        global $_ctx;

        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type)) || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))) {
            return;
        }

        $params['post_type']     = 'page';
        $params['limit']         = abs((int) $w->limit);
        $params['no_content']    = true;
        $params['post_selected'] = false;

        $sort = $w->sortby;
        if (!in_array($sort, ['post_title', 'post_position', 'post_dt'])) {
            $sort = 'post_title';
        }

        $order = $w->orderby;
        if ($order != 'asc') {
            $order = 'desc';
        }
        $params['order'] = $sort . ' ' . $order;

        $rs = dcCore::app()->blog->getPosts($params);

        if ($rs->isEmpty()) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') . '<ul>';

        while ($rs->fetch()) {
            $class = '';
            if ((dcCore::app()->url->type == 'pages' && $_ctx->posts instanceof record && $_ctx->posts->post_id == $rs->post_id)) {
                $class = ' class="page-current"';
            }
            $res .= '<li' . $class . '><a href="' . $rs->getURL() . '">' .
            html::escapeHTML($rs->post_title) . '</a></li>';
        }

        $res .= '</ul>';

        return $w->renderDiv($w->content_only, 'pages ' . $w->class, '', $res);
    }
}
