<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;

/**
 * @brief   The module frontend URL.
 * @ingroup pages
 */
class FrontendUrl extends Url
{
    /**
     * Output the Page page.
     *
     * @param   null|string     $args   The arguments
     */
    public static function pages(?string $args): void
    {
        if ($args == '') {
            // No page was specified.
            self::p404();
        } else {
            App::blog()->withoutPassword(false);

            $params = new ArrayObject([
                'post_type' => 'page',
                'post_url'  => $args, ]);

            # --BEHAVIOR-- publicPagesBeforeGetPosts -- ArrayObject, string
            App::behavior()->callBehavior('publicPagesBeforeGetPosts', $params, $args);

            App::frontend()->context()->posts = App::blog()->getPosts($params);

            /**
             * @var        ArrayObject<string, mixed>
             */
            $cp               = new ArrayObject();
            $cp['content']    = '';
            $cp['rawcontent'] = '';
            $cp['name']       = '';
            $cp['mail']       = '';
            $cp['site']       = '';
            $cp['preview']    = false;
            $cp['remember']   = false;

            App::frontend()->context()->comment_preview = $cp;

            App::blog()->withoutPassword(true);

            if (App::frontend()->context()->posts->isEmpty()) {
                # The specified page does not exist.
                self::p404();
            } else {
                $post_id       = App::frontend()->context()->posts->post_id;
                $post_password = App::frontend()->context()->posts->post_password;

                # Password protected entry
                if ($post_password != '' && !App::frontend()->context()->preview) {
                    # Get passwords cookie
                    if (isset($_COOKIE['dc_passwd'])) {
                        $pwd_cookie = json_decode((string) $_COOKIE['dc_passwd'], null, 512, JSON_THROW_ON_ERROR);
                        $pwd_cookie = $pwd_cookie === null ? [] : (array) $pwd_cookie;
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

                $post_comment = isset($_POST['c_name']) && isset($_POST['c_mail']) && isset($_POST['c_site']) && isset($_POST['c_content']) && App::frontend()->context()->posts->commentsActive();

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
                        $buffer = App::behavior()->callBehavior('publicBeforeCommentTransform', $content);
                        if ($buffer !== '') {
                            $content = $buffer;
                        } else {
                            if (App::blog()->settings()->system->wiki_comments) {
                                App::filter()->initWikiComment();
                            } else {
                                App::filter()->initWikiSimpleComment();
                            }
                            $content = App::filter()->wikiTransform($content);
                        }
                        $content = App::filter()->HTMLfilter($content);
                    }

                    App::frontend()->context()->comment_preview['content']    = $content;
                    App::frontend()->context()->comment_preview['rawcontent'] = $_POST['c_content'];
                    App::frontend()->context()->comment_preview['name']       = $name;
                    App::frontend()->context()->comment_preview['mail']       = $mail;
                    App::frontend()->context()->comment_preview['site']       = $site;

                    if ($preview) {
                        # --BEHAVIOR-- publicBeforeCommentPreview -- ArrayObject
                        App::behavior()->callBehavior('publicBeforeCommentPreview', App::frontend()->context()->comment_preview);

                        App::frontend()->context()->comment_preview['preview'] = true;
                    } else {
                        # Post the comment
                        $cur = App::blog()->openCommentCursor();

                        $cur->comment_author  = $name;
                        $cur->comment_site    = Html::clean($site);
                        $cur->comment_email   = Html::clean($mail);
                        $cur->comment_content = $content;
                        $cur->post_id         = App::frontend()->context()->posts->post_id;
                        $cur->comment_status  = App::blog()->settings()->system->comments_pub ? App::status()->comment()::PUBLISHED : App::status()->comment()::PENDING;
                        $cur->comment_ip      = Http::realIP();

                        $redir = App::frontend()->context()->posts->getURL();
                        $redir .= App::blog()->settings()->system->url_scan == 'query_string' ? '&' : '?';

                        try {
                            if (!Text::isEmail($cur->comment_email)) {
                                throw new Exception(__('You must provide a valid email address.'));
                            }

                            # --BEHAVIOR-- publicBeforeCommentCreate -- Cursor
                            App::behavior()->callBehavior('publicBeforeCommentCreate', $cur);
                            if ($cur->post_id) {
                                $comment_id = App::blog()->addComment($cur);

                                # --BEHAVIOR-- publicAfterCommentCreate -- Cursor, int
                                App::behavior()->callBehavior('publicAfterCommentCreate', $cur, $comment_id);
                            }

                            $redir_arg = App::status()->comment()->isRestricted($cur->comment_status) ? 'pub=0' : 'pub=1';

                            header('Location: ' . $redir . $redir_arg);
                        } catch (Exception $e) {
                            App::frontend()->context()->form_error = $e->getMessage();
                        }
                    }
                }

                # The entry
                if (App::frontend()->context()->posts->trackbacksActive()) {
                    // Send additional headers if pingbacks/webmentions are allowed
                    header('X-Pingback: ' . App::blog()->url() . App::url()->getURLFor('xmlrpc', App::blog()->id()));
                    header('Link: <' . App::blog()->url() . App::url()->getURLFor('webmention') . '>; rel="webmention"');
                }

                $tplset           = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
                $default_template = Path::real(App::plugins()->moduleInfo('pages', 'root')) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
                if (!empty($tplset) && is_dir($default_template . $tplset)) {
                    App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
                } else {
                    App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . App::config()->defaultTplset());
                }
                self::serveDocument('page.html');
            }
        }
    }

    /**
     * Output the Page preview page.
     *
     * @param   null|string     $args   The arguments
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
            if (!App::auth()->checkUser($user_id, null, $user_key)) {
                # The user has no access to the entry.
                self::p404();
            } else {
                App::frontend()->context()->preview = true;
                if (App::config()->adminUrl() !== '') {
                    App::frontend()->context()->xframeoption = App::config()->adminUrl();
                }

                self::pages($post_url);
            }
        }
    }
}
