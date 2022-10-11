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
class urlPages extends dcUrlHandlers
{
    /**
     * Output the Page page
     *
     * @param      null|string  $args   The arguments
     */
    public static function pages(?string $args): void
    {
        if ($args == '') {
            // No page was specified.
            self::p404();
        } else {
            dcCore::app()->blog->withoutPassword(false);

            $params = new ArrayObject([
                'post_type' => 'page',
                'post_url'  => $args, ]);

            dcCore::app()->callBehavior('publicPagesBeforeGetPosts', $params, $args);

            dcCore::app()->ctx->posts = dcCore::app()->blog->getPosts($params);

            dcCore::app()->ctx->comment_preview               = new ArrayObject();
            dcCore::app()->ctx->comment_preview['content']    = '';
            dcCore::app()->ctx->comment_preview['rawcontent'] = '';
            dcCore::app()->ctx->comment_preview['name']       = '';
            dcCore::app()->ctx->comment_preview['mail']       = '';
            dcCore::app()->ctx->comment_preview['site']       = '';
            dcCore::app()->ctx->comment_preview['preview']    = false;
            dcCore::app()->ctx->comment_preview['remember']   = false;

            dcCore::app()->blog->withoutPassword(true);

            if (dcCore::app()->ctx->posts->isEmpty()) {
                # The specified page does not exist.
                self::p404();
            } else {
                $post_id       = dcCore::app()->ctx->posts->post_id;
                $post_password = dcCore::app()->ctx->posts->post_password;

                # Password protected entry
                if ($post_password != '' && !dcCore::app()->ctx->preview) {
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

                $post_comment = isset($_POST['c_name']) && isset($_POST['c_mail']) && isset($_POST['c_site']) && isset($_POST['c_content']) && dcCore::app()->ctx->posts->commentsActive();

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

                    dcCore::app()->ctx->comment_preview['content']    = $content;
                    dcCore::app()->ctx->comment_preview['rawcontent'] = $_POST['c_content'];
                    dcCore::app()->ctx->comment_preview['name']       = $name;
                    dcCore::app()->ctx->comment_preview['mail']       = $mail;
                    dcCore::app()->ctx->comment_preview['site']       = $site;

                    if ($preview) {
                        # --BEHAVIOR-- publicBeforeCommentPreview
                        dcCore::app()->callBehavior('publicBeforeCommentPreview', dcCore::app()->ctx->comment_preview);

                        dcCore::app()->ctx->comment_preview['preview'] = true;
                    } else {
                        # Post the comment
                        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

                        $cur->comment_author  = $name;
                        $cur->comment_site    = html::clean($site);
                        $cur->comment_email   = html::clean($mail);
                        $cur->comment_content = $content;
                        $cur->post_id         = dcCore::app()->ctx->posts->post_id;
                        $cur->comment_status  = dcCore::app()->blog->settings->system->comments_pub ? dcBlog::COMMENT_PUBLISHED : dcBlog::COMMENT_PENDING;
                        $cur->comment_ip      = http::realIP();

                        $redir = dcCore::app()->ctx->posts->getURL();
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

                            if ($cur->comment_status == dcBlog::COMMENT_PUBLISHED) {
                                $redir_arg = 'pub=1';
                            } else {
                                $redir_arg = 'pub=0';
                            }

                            header('Location: ' . $redir . $redir_arg);
                        } catch (Exception $e) {
                            dcCore::app()->ctx->form_error = $e->getMessage();
                        }
                    }
                }

                # The entry
                if (dcCore::app()->ctx->posts->trackbacksActive()) {
                    header('X-Pingback: ' . dcCore::app()->blog->url . dcCore::app()->url->getURLFor('xmlrpc', dcCore::app()->blog->id));
                }

                $tplset           = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'tplset');
                $default_template = substr(__DIR__, 0, -strlen(basename(__DIR__))) . 'default-templates' . DIRECTORY_SEPARATOR;
                if (!empty($tplset) && is_dir($default_template . $tplset)) {
                    dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), $default_template . $tplset);
                } else {
                    dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), $default_template . DC_DEFAULT_TPLSET);
                }
                self::serveDocument('page.html');
            }
        }
    }

    /**
     * Output the Page preview page
     *
     * @param      null|string  $args   The arguments
     */
    public static function pagespreview(?string $args): void
    {
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
                dcCore::app()->ctx->preview = true;
                if (defined('DC_ADMIN_URL')) {
                    dcCore::app()->ctx->xframeoption = DC_ADMIN_URL;
                }

                self::pages($post_url);
            }
        }
    }
}
