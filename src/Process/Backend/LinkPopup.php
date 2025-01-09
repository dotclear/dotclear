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
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;

/**
 * @since 2.27 Before as admin/popup_link.php
 */
class LinkPopup extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        App::backend()->href      = $_GET['href']     ?? '';
        App::backend()->hreflang  = $_GET['hreflang'] ?? '';
        App::backend()->title     = $_GET['title']    ?? '';
        App::backend()->plugin_id = empty($_GET['plugin_id']) ? '' : Html::sanitizeURL($_GET['plugin_id']);

        if (App::themes()->isEmpty()) {
            // Loading themes, may be useful for some configurable theme --
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        // Languages combo
        App::backend()->lang_combo = Combos::getLangsCombo(
            App::blog()->getLangs([
                'order_by' => 'nb_post',
                'order'    => 'desc',
            ]),
            true
        );

        return self::status(true);
    }

    public static function render(): void
    {
        # --BEHAVIOR-- adminPopupLink -- string
        Page::openPopup(
            __('Add a link'),
            Page::jsLoad('js/_popup_link.js') . App::behavior()->callBehavior('adminPopupLink', App::backend()->plugin_id)
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
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Para())
                            ->items([
                                (new Url('href', Html::escapeHTML(App::backend()->href)))
                                    ->size(35)
                                    ->maxlength(512)
                                    ->required(true)
                                    ->placeholder(__('URL'))
                                    ->label((new Label((new Text('span', '*'))->render() . __('Link URL:'), Label::OL_TF))->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('title', Html::escapeHTML(App::backend()->title)))
                                    ->type('text')
                                    ->size(35)
                                    ->maxlength(512)
                                    ->label((new Label(__('Link title:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('hreflang'))
                                    ->items(App::backend()->lang_combo)
                                    ->value(App::backend()->hreflang)
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

        Page::closePopup();
    }
}
