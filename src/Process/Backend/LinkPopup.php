<?php
/**
 * @since 2.27 Before as admin/popup_link.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use form;

class LinkPopup extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        App::backend()->href      = !empty($_GET['href']) ? $_GET['href'] : '';
        App::backend()->hreflang  = !empty($_GET['hreflang']) ? $_GET['hreflang'] : '';
        App::backend()->title     = !empty($_GET['title']) ? $_GET['title'] : '';
        App::backend()->plugin_id = !empty($_GET['plugin_id']) ? Html::sanitizeURL($_GET['plugin_id']) : '';

        if (App::themes()->isEmpty()) {
            # -- Loading themes, may be useful for some configurable theme --
            App::themes()->loadModules(App::blog()->themes_path, 'admin', App::lang());
        }

        // Languages combo
        $rs                        = App::blog()->getLangs(['order' => 'asc']);
        App::backend()->lang_combo = Combos::getLangsCombo($rs, true);

        return self::status(true);
    }

    public static function render(): void
    {
        # --BEHAVIOR-- adminPopupLink -- string
        Page::openPopup(__('Add a link'), Page::jsLoad('js/_popup_link.js') . App::behavior()->callBehavior('adminPopupLink', App::backend()->plugin_id));

        echo '<h2 class="page-title">' . __('Add a link') . '</h2>';

        echo
        '<form id="link-insert-form" action="#" method="get">' .
        '<p><label class="required" for="href"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Link URL:') . '</label> ' .
        form::field('href', 35, 512, [
            'default'    => Html::escapeHTML(App::backend()->href),
            'extra_html' => 'required placeholder="' . __('URL') . '"',
        ]) .
        '</p>' .
        '<p><label for="title">' . __('Link title:') . '</label> ' .
        form::field('title', 35, 512, Html::escapeHTML(App::backend()->title)) . '</p>' .
        '<p><label for="hreflang">' . __('Link language:') . '</label> ' .
        form::combo('hreflang', App::backend()->lang_combo, App::backend()->hreflang) .
        '</p>' .

        '</form>' .

        '<p><button type="button" class="reset" id="link-insert-cancel">' . __('Cancel') . '</button> - ' .
        '<button type="button" id="link-insert-ok"><strong>' . __('Insert') . '</strong></button></p>' . "\n";

        Page::closePopup();
    }
}
