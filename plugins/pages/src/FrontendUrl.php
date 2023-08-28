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
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use dcBlog;
use dcCore;
use Dotclear\Core\Core;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;

class FrontendUrl extends Url
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
            Core::blog()->withoutPassword(false);

            $params = new ArrayObject([
                'post_type' => 'page',
                'post_url'  => $args, ]);

            # --BEHAVIOR-- publicPagesBeforeGetPosts -- ArrayObject, string
            Core::behavior()->callBehavior('publicPagesBeforeGetPosts', $params, $args);

            Core::frontend()->ctx->posts = Core::blog()->getPosts($params);

            Core::frontend()->ctx->comment_preview               = new ArrayObject();
            Core::frontend()->ctx->comment_preview['content']    = '';
            Core::frontend()->ctx->comment_preview['rawcontent'] = '';
            Core::frontend()->ctx->comment_preview['name']       = '';
            Core::frontend()->ctx->comment_preview['mail']       = '';
            Core::frontend()->ctx->comment_preview['site']       = '';
            Core::frontend()->ctx->comment_preview['preview']    = false;
            Core::frontend()->ctx->comment_preview['remember']   = false;

            Core::blog()->withoutPassword(true);

            if (Core::frontend()->ctx->posts->isEmpty()) {
                # The specified page does not exist.
                self::p404();
            } else {
                $post_id       = Core::frontend()->ctx->posts->post_id;
                $post_password = Core::frontend()->ctx->posts->post_password;

                # Password protected entry
                if ($post_password != '' && !Core::frontend()->ctx->preview) {
                    # Get passwords cookie
                    if (isset($_COOKIE['dc_passwd'])) {
                        $pwd_cookie = json_decode($_COOKIE['dc_passwd'], null, 512, JSON_THROW_ON_ERROR);
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
                        setcookie('dc_passwd', json_encode($pwd_cookie, JSON_THROW_ON_ERROR), ['expires' => 0, 'path' => '/']);
                    } else {
                        self::serveDocument('password-form.html', 'text/html', false);

                        return;
                    }
                }

                $post_comment = isset($_POST['c_name']) && isset($_POST['c_mail']) && isset($_POST['c_site']) && isset($_POST['c_content']) && Core::frontend()->ctx->posts->commentsActive();

                # Posting a comment
                if ($post_comment) {
                    # Spam trap
                    if (!empty($_POST['f_mail'])) {
                        Http::head(412, 'Precondition Failed');
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
                        # --BEHAVIOR-- publicBeforeCommentTransform -- string
                        $buffer = Core::behavior()->callBehavior('publicBeforeCommentTransform', $content);
                        if ($buffer != '') {
                            $content = $buffer;
                        } else {
                            if (Core::blog()->settings->system->wiki_comments) {
                                Core::filter()->initWikiComment();
                            } else {
                                Core::filter()->initWikiSimpleComment();
                            }
                            $content = Core::filter()->wikiTransform($content);
                        }
                        $content = Core::filter()->HTMLfilter($content);
                    }

                    Core::frontend()->ctx->comment_preview['content']    = $content;
                    Core::frontend()->ctx->comment_preview['rawcontent'] = $_POST['c_content'];
                    Core::frontend()->ctx->comment_preview['name']       = $name;
                    Core::frontend()->ctx->comment_preview['mail']       = $mail;
                    Core::frontend()->ctx->comment_preview['site']       = $site;

                    if ($preview) {
                        # --BEHAVIOR-- publicBeforeCommentPreview -- ArrayObject
                        Core::behavior()->callBehavior('publicBeforeCommentPreview', Core::frontend()->ctx->comment_preview);

                        Core::frontend()->ctx->comment_preview['preview'] = true;
                    } else {
                        # Post the comment
                        $cur = Core::con()->openCursor(Core::con()->prefix() . dcBlog::COMMENT_TABLE_NAME);

                        $cur->comment_author  = $name;
                        $cur->comment_site    = Html::clean($site);
                        $cur->comment_email   = Html::clean($mail);
                        $cur->comment_content = $content;
                        $cur->post_id         = Core::frontend()->ctx->posts->post_id;
                        $cur->comment_status  = Core::blog()->settings->system->comments_pub ? dcBlog::COMMENT_PUBLISHED : dcBlog::COMMENT_PENDING;
                        $cur->comment_ip      = Http::realIP();

                        $redir = Core::frontend()->ctx->posts->getURL();
                        $redir .= Core::blog()->settings->system->url_scan == 'query_string' ? '&' : '?';

                        try {
                            if (!Text::isEmail($cur->comment_email)) {
                                throw new Exception(__('You must provide a valid email address.'));
                            }

                            # --BEHAVIOR-- publicBeforeCommentCreate -- Cursor
                            Core::behavior()->callBehavior('publicBeforeCommentCreate', $cur);
                            if ($cur->post_id) {
                                $comment_id = Core::blog()->addComment($cur);

                                # --BEHAVIOR-- publicAfterCommentCreate -- Cursor, int
                                Core::behavior()->callBehavior('publicAfterCommentCreate', $cur, $comment_id);
                            }

                            if ($cur->comment_status == dcBlog::COMMENT_PUBLISHED) {
                                $redir_arg = 'pub=1';
                            } else {
                                $redir_arg = 'pub=0';
                            }

                            header('Location: ' . $redir . $redir_arg);
                        } catch (Exception $e) {
                            Core::frontend()->ctx->form_error = $e->getMessage();
                        }
                    }
                }

                # The entry
                if (Core::frontend()->ctx->posts->trackbacksActive()) {
                    header('X-Pingback: ' . Core::blog()->url . dcCore::app()->url->getURLFor('xmlrpc', Core::blog()->id));
                }

                $tplset           = dcCore::app()->themes->moduleInfo(Core::blog()->settings->system->theme, 'tplset');
                $default_template = Path::real(Core::plugins()->moduleInfo('pages', 'root')) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
                if (!empty($tplset) && is_dir($default_template . $tplset)) {
                    Core::frontend()->tpl->setPath(Core::frontend()->tpl->getPath(), $default_template . $tplset);
                } else {
                    Core::frontend()->tpl->setPath(Core::frontend()->tpl->getPath(), $default_template . DC_DEFAULT_TPLSET);
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
        if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#', (string) $args, $m)) {
            # The specified Preview URL is malformed.
            self::p404();
        } else {
            $user_id  = $m[1];
            $user_key = $m[2];
            $post_url = $m[3];
            if (!Core::auth()->checkUser($user_id, null, $user_key)) {
                # The user has no access to the entry.
                self::p404();
            } else {
                Core::frontend()->ctx->preview = true;
                if (defined('DC_ADMIN_URL')) {
                    Core::frontend()->ctx->xframeoption = DC_ADMIN_URL;
                }

                self::pages($post_url);
            }
        }
    }
}
