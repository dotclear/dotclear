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

namespace Dotclear\Backend;

use dcAdminCombos;
use dcCore;
use dcNsProcess;
use dcPage;
use dcThemes;
use Dotclear\Helper\Html\Html;
use form;

class LinkPopup extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]));

        dcCore::app()->admin->href      = !empty($_GET['href']) ? $_GET['href'] : '';
        dcCore::app()->admin->hreflang  = !empty($_GET['hreflang']) ? $_GET['hreflang'] : '';
        dcCore::app()->admin->title     = !empty($_GET['title']) ? $_GET['title'] : '';
        dcCore::app()->admin->plugin_id = !empty($_GET['plugin_id']) ? Html::sanitizeURL($_GET['plugin_id']) : '';

        if (dcCore::app()->themes === null) {
            # -- Loading themes, may be useful for some configurable theme --
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }

        // Languages combo
        $rs                              = dcCore::app()->blog->getLangs(['order' => 'asc']);
        dcCore::app()->admin->lang_combo = dcAdminCombos::getLangsCombo($rs, true);

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        # --BEHAVIOR-- adminPopupLink -- string
        dcPage::openPopup(__('Add a link'), dcPage::jsLoad('js/_popup_link.js') . dcCore::app()->callBehavior('adminPopupLink', dcCore::app()->admin->plugin_id));

        echo '<h2 class="page-title">' . __('Add a link') . '</h2>';

        echo
        '<form id="link-insert-form" action="#" method="get">' .
        '<p><label class="required" for="href"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Link URL:') . '</label> ' .
        form::field('href', 35, 512, [
            'default'    => Html::escapeHTML(dcCore::app()->admin->href),
            'extra_html' => 'required placeholder="' . __('URL') . '"',
        ]) .
        '</p>' .
        '<p><label for="title">' . __('Link title:') . '</label> ' .
        form::field('title', 35, 512, Html::escapeHTML(dcCore::app()->admin->title)) . '</p>' .
        '<p><label for="hreflang">' . __('Link language:') . '</label> ' .
        form::combo('hreflang', dcCore::app()->admin->lang_combo, dcCore::app()->admin->hreflang) .
        '</p>' .

        '</form>' .

        '<p><button type="button" class="reset" id="link-insert-cancel">' . __('Cancel') . '</button> - ' .
        '<button type="button" id="link-insert-ok"><strong>' . __('Insert') . '</strong></button></p>' . "\n";

        dcPage::closePopup();
    }
}
