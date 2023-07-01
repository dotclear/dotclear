<?php
/**
 * @brief pings, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use dcCore;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->admin->pings_uris = [];

        try {
            // Pings URIs are managed globally (for all blogs)
            dcCore::app()->admin->pings_uris = My::settings()->getGlobal('pings_uris');
            if (!dcCore::app()->admin->pings_uris) {
                dcCore::app()->admin->pings_uris = [];
            }

            if (isset($_POST['pings_srv_name'])) {
                $pings_srv_name = is_array($_POST['pings_srv_name']) ? $_POST['pings_srv_name'] : [];
                $pings_srv_uri  = is_array($_POST['pings_srv_uri']) ? $_POST['pings_srv_uri'] : [];
                $pings_uris     = [];

                foreach ($pings_srv_name as $k => $v) {
                    if (trim((string) $v) && trim((string) $pings_srv_uri[$k])) {
                        $pings_uris[trim((string) $v)] = trim((string) $pings_srv_uri[$k]);
                    }
                }
                // Settings for all blogs
                My::settings()->put('pings_active', !empty($_POST['pings_active']), null, null, true, true);
                My::settings()->put('pings_uris', $pings_uris, null, null, true, true);
                // Settings for current blog only
                My::settings()->put('pings_auto', !empty($_POST['pings_auto']), null, null, true, false);

                Page::addSuccessNotice(__('Settings have been successfully updated.'));
                My::redirect();
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        Page::openModule(My::name());

        echo
        Page::breadcrumb(
            [
                __('Plugins')             => '',
                __('Pings configuration') => '',
            ]
        ) .
        '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .
        '<p><label for="pings_active" class="classic">' .
        form::checkbox('pings_active', 1, My::settings()->pings_active) .
        __('Activate pings extension') . '</label></p>';

        $i = 0;
        foreach (dcCore::app()->admin->pings_uris as $name => $uri) {
            echo
            '<p><label for="pings_srv_name-' . $i . '" class="classic">' . __('Service name:') . '</label> ' .
            form::field(['pings_srv_name[]', 'pings_srv_name-' . $i], 20, 128, Html::escapeHTML((string) $name)) . ' ' .
            '<label for="pings_srv_uri-' . $i . '" class="classic">' . __('Service URI:') . '</label> ' .
            form::url(['pings_srv_uri[]', 'pings_srv_uri-' . $i], [
                'size'    => 40,
                'default' => Html::escapeHTML($uri),
            ]);

            if (!empty($_GET['test'])) {
                try {
                    PingsAPI::doPings($uri, 'Example site', 'http://example.com');
                    echo ' <img src="images/check-on.png" alt="OK" />';
                } catch (Exception $e) {
                    echo ' <img src="images/check-off.png" alt="' . __('Error') . '" /> ' . $e->getMessage();
                }
            }

            echo '</p>';
            $i++;
        }

        echo
        '<p><label for="pings_srv_name2" class="classic">' . __('Service name:') . '</label> ' .
        form::field(['pings_srv_name[]', 'pings_srv_name2'], 20, 128) . ' ' .
        '<label for="pings_srv_uri2" class="classic">' . __('Service URI:') . '</label> ' .
        form::url(['pings_srv_uri[]', 'pings_srv_uri2'], 40) .
        '</p>' .

        '<p><label for="pings_auto" class="classic">' .
        form::checkbox('pings_auto', 1, My::settings()->pings_auto) .
        __('Auto pings all services on first publication of entry (current blog only)') . '</label></p>' .

        '<p><input type="submit" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        dcCore::app()->formNonce() . '</p>' .
        '</form>' .

        '<p><a class="button" href="' . dcCore::app()->admin->getPageURL() . '&amp;test=1">' . __('Test ping services') . '</a></p>';

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
