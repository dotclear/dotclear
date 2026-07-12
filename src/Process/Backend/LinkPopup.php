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
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @since 2.27 Before as admin/popup_link.php
 */
class LinkPopup
{
    use TraitProcess;

    protected static string $href;

    protected static string $hreflang;

    protected static string $title;

    protected static string $plugin_id;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        // Variable data helpers
        $_Str = fn (string $name, string $default = ''): string => isset($_GET[$name]) && is_string($val = $_GET[$name]) ? $val : $default;

        self::$href      = $_Str('href');
        self::$hreflang  = $_Str('hreflang');
        self::$title     = $_Str('title');
        self::$plugin_id = Html::sanitizeURL($_Str('plugin_id'));

        if (App::themes()->isEmpty()) {
            // Loading themes, may be useful for some configurable theme --
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        // Languages combo
        $lang_combo = App::backend()->combos()->getLangsCombo(
            App::blog()->getLangs([
                'order_by' => 'nb_post',
                'order'    => 'desc',
            ]),
            true,
            true
        );

        # --BEHAVIOR-- adminPopupLink -- string
        App::backend()->page()->openPopup(
            __('Add a link'),
            App::backend()->page()->jsLoad('js/_popup_link.js') . App::behavior()->callBehavior('adminPopupLink', self::$plugin_id)
        );

        echo (new Set())
            ->items([
                (new Text('h2', __('Add a link')))
                    ->class('page-title'),
                (new Form('link-insert-form'))
                    ->method('get')
                    ->fields([
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                        (new Para())
                            ->items([
                                (new Url('href', Html::escapeHTML(self::$href)))
                                    ->size(35)
                                    ->maxlength(512)
                                    ->required(true)
                                    ->placeholder(__('URL'))
                                    ->translate(false)
                                    ->label((new Label((new Span('*'))->render() . __('Link URL:'), Label::OL_TF))->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('title', Html::escapeHTML(self::$title)))
                                    ->type('text')
                                    ->size(35)
                                    ->maxlength(512)
                                    ->label((new Label(__('Link title:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('hreflang'))
                                    ->items($lang_combo)
                                    ->value(self::$hreflang)
                                    ->translate(false)
                                    ->label((new Label(__('Link language:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Button('link-insert-cancel', __('Cancel')))
                                    ->class('reset'),
                                (new Submit('link-insert-ok', __('Insert'))),
                            ]),
                    ]),
            ])
        ->render();

        App::backend()->page()->closePopup();
    }
}
