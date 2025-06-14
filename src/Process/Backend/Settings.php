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
use Dotclear\Core\Backend\ModulesList;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Single;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Module\ModuleDefine;
use Dotclear\Plugin\widgets\Widgets;

/**
 * @since 2.35
 */
class Settings extends Process
{
    public static function init(): bool
    {
        return self::status(true);
    }

    public static function process(): bool
    {
        return true;
    }

    public static function render(): void
    {
        // -- Page header --
        Page::open(
            __('Plugins settings'),
            Page::jsLoad('js/_settings.js') .
            # --BEHAVIOR-- settingsHeaders
            App::behavior()->callBehavior('settingsHeaders'),
            Page::breadcrumb(
                [
                    __('System')           => '',
                    __('Plugins settings') => '',
                ]
            )
        );

        $widgets = [];
        // Check widget permission
        if (
            App::plugins()->moduleExists('widgets') && (App::blog()->isDefined() && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]), App::blog()->id()))) {
            // Init default widgets
            Widgets::init();
            // Get list of registered plugins for existing widgets
            foreach (Widgets::$widgets->elements() as $w) {
                $id = $w->pluginID();
                if ($id && !in_array($id, $widgets)) {
                    $widgets[] = $id;
                }
            }
        }

        // -- Display modules lists --
        $plugins = App::plugins()->getDefines(['state' => ModuleDefine::STATE_ENABLED]);
        uasort($plugins, static fn ($a, $b): int => strtolower((string) $a->getId()) <=> strtolower((string) $b->getId()));

        $makeLink = function (string $type, string $url): Link {
            $title = match ($type) {
                'config'  => __('Configuration'),
                'blog'    => __('Blog parameters'),
                'pref'    => __('User preferences'),
                'self'    => __('Settings'),
                'other'   => __('Other settings'),
                'manage'  => __('Management'),
                'widgets' => __('Widgets'),
                default   => __('Unknown'),
            };

            return (new Link())
                ->href($url)
                ->text($title);
        };

        // Prepare status (columns presence)
        $cols = [
            'description' => false,
            'config'      => false,
            'blog'        => false,
            'pref'        => false,
            'self'        => false,
            'other'       => false,
            'manage'      => false,
            'widgets'     => false,
        ];
        foreach ($plugins as $plugin) {
            $id       = $plugin->getId();
            $name     = $plugin->get('name');
            $settings = ModulesList::getSettingsUrls($id, true, keys: true, url_only: true);
            if ($settings !== []) {
                if ($name !== $id) {
                    $cols['description'] = true;
                }
                if (isset($settings['config'])) {
                    $cols['config'] = true;
                }
                if (isset($settings['blog'])) {
                    $cols['blog'] = true;
                }
                if (isset($settings['pref'])) {
                    $cols['pref'] = true;
                }
                if (isset($settings['self'])) {
                    $cols['self'] = true;
                }
                if (isset($settings['other'])) {
                    $cols['other'] = true;
                }
                if (isset($settings['manage']) && (!isset($settings['self']) || $settings['manage'] !== $settings['self'])) {
                    $cols['manage'] = true;
                }
            }
            if (in_array($id, $widgets)) {
                $cols['widgets'] = true;
            }
        }

        // Compose rows
        $rows = [];
        foreach ($plugins as $plugin) {
            $id       = $plugin->getId();
            $name     = $plugin->get('name');
            $settings = ModulesList::getSettingsUrls($id, true, keys: true, url_only: true);
            if ($settings !== [] || in_array($id, $widgets)) {
                $rows[] = (new Tr())
                    ->class('line')
                    ->items([
                        (new Td())
                            ->items([
                                (new Strong($id)),
                            ]),
                        $cols['description'] ?
                            (new Td())
                                ->text($name !== $id ? __($name) : '') :
                            (new None()),
                        $cols['config'] ?
                            (new Td())
                                ->items([
                                    isset($settings['config']) ? $makeLink('config', $settings['config']) : (new None()),
                                ]) :
                            (new None()),
                        $cols['blog'] ?
                            (new Td())
                                ->items([
                                    isset($settings['blog']) ? $makeLink('blog', $settings['blog']) : (new None()),
                                ]) :
                            (new None()),
                        $cols['pref'] ?
                            (new Td())
                                ->items([
                                    isset($settings['pref']) ? $makeLink('pref', $settings['pref']) : (new None()),
                                ]) :
                            (new None()),
                        $cols['self'] ?
                            (new Td())
                                ->items([
                                    isset($settings['self']) ? $makeLink('self', $settings['self']) : (new None()),
                                ]) :
                            (new None()),
                        $cols['other'] ?
                            (new Td())
                                ->items([
                                    isset($settings['other']) ? $makeLink('other', $settings['other']) : (new None()),
                                ]) :
                            (new None()),
                        $cols['manage'] ?
                            (new Td())
                                ->items([
                                    isset($settings['manage']) ?
                                    (isset($settings['self']) && $settings['manage'] === $settings['self'] ?
                                        (new None()) :
                                        $makeLink('manage', $settings['manage'])) :
                                    (new None()),
                                ]) :
                            (new None()),
                        $cols['widgets'] ?
                            (new Td())
                                ->items([
                                    in_array($id, $widgets) ?
                                    $makeLink('widgets', App::backend()->url()->get('admin.plugin.widgets')) :
                                    (new None()),
                                ]) :
                            (new None()),
                    ]);
            }
        }

        echo (new Div())
            ->items([
                App::auth()->isSuperAdmin() ?
                    (new Note())
                        ->class(['form-note', 'warn'])
                        ->text(sprintf(__('This page does not allow you to manage plugins (update, install, uninstall, activate, deactivate, etc.). If you need to, go to <a href="%s">this page</a>.'), App::backend()->url()->get('admin.plugins'))) :
                    (new None()),
                (new Table('settings'))
                    ->thead((new Thead())
                        ->items([
                            (new Tr())
                                ->items([
                                    (new Th())
                                        ->text(__('ID')),
                                    $cols['description'] ?
                                        (new Th())
                                            ->text(__('Description')) :
                                        (new None()),
                                    $cols['config'] ?
                                        (new Th())
                                            ->text(__('Configuration')) :
                                        (new None()),
                                    $cols['blog'] ?
                                        (new Th())
                                            ->text(__('Blog')) :
                                        (new None()),
                                    $cols['pref'] ?
                                        (new Th())
                                            ->text(__('User')) :
                                        (new None()),
                                    $cols['self'] ?
                                        (new Th())
                                            ->text(__('Settings')) :
                                        (new None()),
                                    $cols['other'] ?
                                        (new Th())
                                            ->text(__('Other settings')) :
                                        (new None()),
                                    $cols['manage'] ?
                                        (new Th())
                                            ->text(__('Management')) :
                                        (new None()),
                                    $cols['widgets'] ?
                                        (new Th())
                                            ->text(__('Widgets')) :
                                        (new None()),
                                ]),
                        ]))
                    ->tbody((new Tbody())
                        ->items($rows)),
                (new Div())
                    ->class(['form-note', 'info'])
                    ->items([
                        (new Text(null, __('Columns description:'))),
                        (new Single('br')),
                        (new Ul())
                            ->items([
                                $cols['config'] ?
                                    (new Li())
                                        ->text(__('<strong>Configuration</strong> indicates that plugin has a specific configuration page.')) :
                                    (new None()),
                                $cols['blog'] ?
                                    (new Li())
                                        ->text(__('<strong>Blog</strong> indicates that plugin has specific settings in blog parameters, generally used to add or modify some behaviors or aspects of the public blog.')) :
                                    (new None()),
                                $cols['pref'] ?
                                    (new Li())
                                        ->text(__('<strong>User</strong> indicates that the plugin has a specific configuration in the user preferences, generally used to add or modify certain behaviors or aspects of blog administration for the user.')) :
                                    (new None()),
                                $cols['self'] ?
                                    (new Li())
                                        ->text(__('<strong>Settings</strong> indicates that the plugin has specific parameters on its management page.')) :
                                    (new None()),
                                $cols['other'] ?
                                    (new Li())
                                        ->text(__('<strong>Other settings</strong> indicates that plugin has specific settings in other context.')) :
                                    (new None()),
                                $cols['manage'] ?
                                    (new Li())
                                        ->text(__('<strong>Management</strong> indicates that the plugin has a specific management page.')) :
                                    (new None()),
                                $cols['widgets'] ?
                                    (new Li())
                                        ->text(__('<strong>Widgets</strong> indicates that the plugin provide one or more widgets.')) :
                                    (new None()),
                            ]),
                    ]),
            ])
        ->render();

        Page::helpBlock('core_settings');
        Page::close();
    }
}
