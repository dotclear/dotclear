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

        // -- Display modules lists --
        $plugins = App::plugins()->getDefines(['state' => ModuleDefine::STATE_ENABLED]);
        uasort($plugins, static fn ($a, $b): int => strtolower((string) $a->getId()) <=> strtolower((string) $b->getId()));

        $status   = [];
        $makeLink = function (string $type, string $url) use (&$status): Link {
            $status[$type] = true;
            $title         = match ($type) {
                'config' => __('Configuration'),
                'blog'   => __('Blog parameters'),
                'pref'   => __('User preferences'),
                'self'   => __('Settings'),
                'other'  => __('Other settings'),
                'manage' => __('Management'),
                default  => __('Unknown'),
            };

            return (new Link())
                ->href($url)
                ->text($title);
        };

        $rows = [];
        foreach ($plugins as $plugin) {
            $id       = $plugin->getId();
            $name     = $plugin->get('name');
            $settings = ModulesList::getSettingsUrls($id, true, keys: true, url_only: true);
            if ($settings !== []) {
                $rows[] = (new Tr())
                    ->class('line')
                    ->items([
                        (new Td())
                            ->items([
                                (new Strong($id)),
                            ]),
                        (new Td())
                            ->text($name !== $id ? $name : ''),
                        (new Td())
                            ->items([
                                isset($settings['config']) ? $makeLink('config', $settings['config']) : (new None()),
                            ]),
                        (new Td())
                            ->items([
                                isset($settings['blog']) ? $makeLink('blog', $settings['blog']) : (new None()),
                            ]),
                        (new Td())
                            ->items([
                                isset($settings['pref']) ? $makeLink('pref', $settings['pref']) : (new None()),
                            ]),
                        (new Td())
                            ->items([
                                isset($settings['self']) ? $makeLink('self', $settings['self']) : (new None()),
                            ]),
                        (new Td())
                            ->items([
                                isset($settings['other']) ? $makeLink('other', $settings['other']) : (new None()),
                            ]),
                        (new Td())
                            ->items([
                                isset($settings['manage']) ?
                                (isset($settings['self']) && $settings['manage'] === $settings['self'] ?
                                    (new None()) :
                                    $makeLink('manage', $settings['manage'])) :
                                (new None()),
                            ]),
                    ]);
            }
        }

        echo (new Div())
            ->items([
                (new Table('settings'))
                    ->thead((new Thead())
                        ->items([
                            (new Tr())
                                ->items([
                                    (new Th())
                                        ->text(__('ID')),
                                    (new Th())
                                        ->text(__('Description')),
                                    (new Th())
                                        ->text(isset($status['config']) ? __('Configuration') : ''),
                                    (new Th())
                                        ->text(isset($status['blog']) ? __('Blog') : ''),
                                    (new Th())
                                        ->text(isset($status['pref']) ? __('User') : ''),
                                    (new Th())
                                        ->text(isset($status['self']) ? __('Settings') : ''),
                                    (new Th())
                                        ->text(isset($status['other']) ? __('Other settings') : ''),
                                    (new Th())
                                        ->text(isset($status['manage']) ? __('Management') : ''),
                                ]),
                        ]))
                    ->tbody((new Tbody())
                        ->items($rows)),
                (new Div())
                    ->class(['form-note', 'info'])
                    ->items([
                        (new Text(null, __('Column description:'))),
                        (new Single('br')),
                        (new Ul())
                            ->items([
                                (new Li())
                                    ->text(__('“Configuration”: indicates that plugin has a specific configuration page.')),
                                (new Li())
                                    ->text(__('“Blog”: indicates that plugin has specific settings in blog parameters, generally used to add or modify some behaviors or aspects of the public blog.')),
                                (new Li())
                                    ->text(__('“user”: indicates that the plugin has a specific configuration in the user preferences, generally used to add or modify certain behaviors or aspects of blog administration for the user.')),
                                (new Li())
                                    ->text(__('“self”: indicates that when the plugin has specific parameters on its management page.')),
                                (new Li())
                                    ->text(__('“Other”: indicates that plugin has specific settings in other context.')),
                                (new Li())
                                    ->text(__('“Management”: indicates that the plugin has a specific management page.')),
                            ]),
                        (new Text(null, __('Some of these columns may not be displayed if no plugin has a link in them.'))),
                    ]),
            ])
        ->render();

        Page::helpBlock('core_settings');
        Page::close();
    }
}
