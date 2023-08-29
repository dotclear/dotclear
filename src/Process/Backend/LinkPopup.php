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

use dcCore;
use dcThemes;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use form;

class LinkPopup extends Process
{
    public static function init(): bool
    {
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Core::backend()->href      = !empty($_GET['href']) ? $_GET['href'] : '';
        Core::backend()->hreflang  = !empty($_GET['hreflang']) ? $_GET['hreflang'] : '';
        Core::backend()->title     = !empty($_GET['title']) ? $_GET['title'] : '';
        Core::backend()->plugin_id = !empty($_GET['plugin_id']) ? Html::sanitizeURL($_GET['plugin_id']) : '';

        if (Core::themes()->isEmpty()) {
            # -- Loading themes, may be useful for some configurable theme --
            Core::themes()->loadModules(Core::blog()->themes_path, 'admin', Core::lang());
        }

        // Languages combo
        $rs                         = Core::blog()->getLangs(['order' => 'asc']);
        Core::backend()->lang_combo = Combos::getLangsCombo($rs, true);

        return self::status(true);
    }

    public static function render(): void
    {
        # --BEHAVIOR-- adminPopupLink -- string
        Page::openPopup(__('Add a link'), Page::jsLoad('js/_popup_link.js') . Core::behavior()->callBehavior('adminPopupLink', Core::backend()->plugin_id));

        echo '<h2 class="page-title">' . __('Add a link') . '</h2>';

        echo
        '<form id="link-insert-form" action="#" method="get">' .
        '<p><label class="required" for="href"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Link URL:') . '</label> ' .
        form::field('href', 35, 512, [
            'default'    => Html::escapeHTML(Core::backend()->href),
            'extra_html' => 'required placeholder="' . __('URL') . '"',
        ]) .
        '</p>' .
        '<p><label for="title">' . __('Link title:') . '</label> ' .
        form::field('title', 35, 512, Html::escapeHTML(Core::backend()->title)) . '</p>' .
        '<p><label for="hreflang">' . __('Link language:') . '</label> ' .
        form::combo('hreflang', Core::backend()->lang_combo, Core::backend()->hreflang) .
        '</p>' .

        '</form>' .

        '<p><button type="button" class="reset" id="link-insert-cancel">' . __('Cancel') . '</button> - ' .
        '<button type="button" id="link-insert-ok"><strong>' . __('Insert') . '</strong></button></p>' . "\n";

        Page::closePopup();
    }
}
