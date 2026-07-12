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

use Dotclear\App;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/popup_posts.php
 */
class PostsPopup
{
    use TraitProcess;

    protected static string $q;
    protected static string $plugin_id;
    protected static int $page;
    protected static int $nb_per_page;
    protected static ?string $type;

    /**
     * @var array<string, string> $type_combo
     */
    protected static array $type_combo;

    /**
     * @var array<string, mixed> $params
     */
    protected static array $params;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        // Get data helpers
        $_Int = fn (string $name, int $default = 0): int => isset($_GET[$name]) && is_numeric($val = $_GET[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($_GET[$name]) && is_string($val = $_GET[$name]) ? $val : $default;

        self::$q           = $_Str('q');
        self::$plugin_id   = Html::sanitizeURL($_Str('plugin_id'));
        self::$page        = $_Int('page');
        self::$nb_per_page = 10;
        self::$type        = isset($_GET['type']) ? $_Str('type') : null;

        if (self::$page < 1) {
            self::$page = 1;
        }

        $post_types = App::postTypes()->dump();
        $type_combo = [];
        foreach (array_keys($post_types) as $k) {
            $type_combo[__($k)] = (string) $k;
        }
        self::$type_combo = $type_combo;

        if (self::$type === '' && !in_array(self::$type, $type_combo)) {
            self::$type = null;
        }

        $params = [];

        $params['limit']      = [(self::$page - 1) * self::$nb_per_page, self::$nb_per_page];
        $params['no_content'] = true;
        $params['order']      = 'post_dt DESC';

        if (self::$q !== '') {
            $params['search'] = self::$q;
        }

        if (self::$type) {
            $params['post_type'] = self::$type;
        }

        self::$params = $params;

        if (App::themes()->isEmpty()) {
            // Loading themes, may be useful for some configurable theme --
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        $post_list = false;

        try {
            $posts     = App::blog()->getPosts(self::$params);
            $counter   = App::blog()->getPosts(self::$params, true);
            $post_list = App::backend()->listing()->postsMini($posts, $counter->cardinal());
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        App::backend()->page()->openPopup(
            __('Add a link to an entry'),
            App::backend()->page()->jsLoad('js/_posts_list.js') .
            App::backend()->page()->jsLoad('js/_popup_posts.js') .
            App::behavior()->callBehavior('adminPopupPosts', self::$plugin_id)
        );

        echo
        (new Set())
            ->items([
                (new Text('h2', __('Add a link to an entry')))
                    ->class('page-title'),
                (new Form('entry-type-form'))
                    ->method('get')
                    ->action(App::backend()->url()->get('admin.posts.popup'))
                    ->fields([
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Select('type'))
                                    ->items(self::$type_combo)
                                    ->default(self::$type)
                                    ->label(new Label(__('Entry type:'), Label::IL_TF)),
                                (new Submit('type-submit', __('Ok'))),
                                (new Hidden('plugin_id', Html::escapeHTML(self::$plugin_id))),
                                (new Hidden('popup', '1')),
                                (new Hidden('process', 'PostsPopup')),
                            ]),
                    ]),
                (new Form('entry-search-form'))
                    ->method('get')
                    ->action(App::backend()->url()->get('admin.posts.popup'))
                    ->fields([
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Input('q'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$q))
                                    ->label(new Label(__('Search entry:'), Label::IL_TF)),
                                (new Submit('search-submit', __('Search'))),
                                (new Hidden('plugin_id', Html::escapeHTML(self::$plugin_id))),
                                (new Hidden('popup', '1')),
                                (new Hidden('process', 'PostsPopup')),
                                (new Hidden('type', self::$type)),
                            ]),
                    ]),
                (new Div('form-entries'))   // I know it's not a form but we just need the ID
                    ->items([
                        $post_list !== false ?
                        (new Capture(
                            $post_list->display(...),
                            [
                                self::$page,
                                self::$nb_per_page,
                            ]
                        )) :
                        (new None()),
                    ]),
                (new Para())
                    ->items([
                        (new Button('link-insert-cancel', __('cancel'))),
                    ]),
            ])
        ->render();

        App::backend()->page()->closePopup();
    }
}
