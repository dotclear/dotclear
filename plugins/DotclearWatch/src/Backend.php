<?php

/**
 * @brief   DotclearWatch backend class.
 * @ingroup DotclearWatch
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\DotclearWatch;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Menus;
use Dotclear\Helper\Date;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Ul;

class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Compatibility audit page (super admin)
        My::addBackendMenuItem(Menus::MENU_SYSTEM);

        App::behavior()->addBehaviors([
            // Add JS for asynchronous report sending
            'adminDashboardHeaders' => fn (): string => My::jsLoad('service', App::version()->getVersion(My::id())) .
                    (My::checkContext(My::MANAGE) ? My::cssLoad('manage') : ''),

            // Compatibility audit on dashboard (super admin, once rules are received)
            'adminDashboardItemsV2' => function (ArrayObject $__dashboard_items): void {
                if (!My::checkContext(My::MANAGE) || !self::showAudit() || ($rules = Audit::getRules()) === null) {
                    return;
                }

                $result = Audit::getResult();
                $tz     = is_string(App::auth()->getInfo('user_tz')) ? App::auth()->getInfo('user_tz') : '';
                $next   = $rules['dotclear']['next'] ?? '';
                $php    = Audit::checkPhp();

                // @phpstan-ignore method.nonObject
                $__dashboard_items[0]->append(
                    (new Div(My::id() . '_audit'))
                    //->legend(new Legend((new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . sprintf(__('Compatibility with Dotclear %s'), $next)))
                    ->class('box small')
                    ->items([
                        (new Text('h3', (new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . sprintf(__('Compatibility with Dotclear %s'), $next))),
                        $php !== null ?
                            (new Text('p', sprintf(__('PHP %s required, this server uses PHP %s.'), $rules['dotclear']['php_min'] ?? '', PHP_VERSION)))->class('warn') :
                            (new None()),
                        $result === null ?
                            (new Text('p', __('Modules have not been checked yet.'))) :
                            (new Para())->items([
                                Manage::badge($result['alerts'] > 0 ? Audit::ALERT : ($result['notices'] > 0 ? Audit::NOTICE : 'ok')),
                                (new Text(null, ' ' . sprintf(__('%d module with alerts', '%d modules with alerts', $result['alerts']), $result['alerts']) . ', ' . sprintf(__('%d with notices.', '%d with notices.', $result['notices']), $result['notices']))),
                                (new Text(null, '<br>' . sprintf(__('Checked on %s.'), Date::str(__('%Y-%m-%d %H:%M'), $result['date'], $tz)))),
                                (new Text(null, $result['outdated'] ? ' <strong>' . __('Check again.') . '</strong>' : '')),
                            ]),
                        (new Para())->items([
                            (new Link())
                                ->href(My::manageUrl())
                                ->text($result === null ? __('Check modules') : __('See details')),
                        ]),
                    ])
                    ->render()
                );
            },

            // Show or hide the compatibility audit on dashboard (user preferences, super admin)
            'adminDashboardOptionsFormV2' => function (): void {
                if (!My::checkContext(My::MANAGE)) {
                    return;
                }

                echo (new Fieldset(My::id() . '_prefs'))
                    ->legend(new Legend((new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . My::name()))
                    ->items([
                        (new Para())->items([
                            (new Checkbox(My::id() . '_dashboard_audit', self::showAudit()))
                                ->value(1)
                                ->label(new Label(__('Show the compatibility audit of modules on dashboard'), Label::IL_FT)),
                        ]),
                    ])
                    ->render();
            },

            'adminAfterDashboardOptionsUpdate' => function (?string $user_id): void {
                if (!My::checkContext(My::MANAGE)) {
                    return;
                }

                My::prefs()->put('dashboard_audit', !empty($_POST[My::id() . '_dashboard_audit']), 'boolean');
            },

            // Add icon on bottom of dashboard sidebar menu
            'adminPageFooterV2' => function (): void {
                // Shared statistics only exist for the default API
                $api_url = My::settings()->getGlobal('distant_api_url');
                if (!is_string($api_url) || $api_url === '' || rtrim($api_url, '/') === rtrim(Utils::DISTANT_API_URL, '/')) {
                    echo (new Ul())->items([
                        (new Li())->items([
                            (new Link())
                                ->class('outgoing')
                                ->href(Utils::STATISTICS_URL)
                                ->title(__('Uses DotclearWatch plugin statistics'))
                                ->text(__('Shared statistics'))
                                ->items([
                                    (new Img(My::fileURL('icon.svg')))->alt(''),
                                ]),
                        ]),
                    ])->render();
                }
            },
        ]);

        App::rest()->addFunction(
            // Add REST service for asynchronous report sending
            'adminDotclearWatchSendReport',
            function (): array {
                Utils::sendReport();

                return [
                    'ret' => true,
                    'msg' => 'report sent',
                ];
            },
        );

        return true;
    }

    /**
     * Check if the compatibility audit is shown on dashboard (shown until the user hides it).
     */
    private static function showAudit(): bool
    {
        return My::prefs()->get('dashboard_audit') !== false;
    }
}
