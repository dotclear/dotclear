<?php

/**
 * @brief       DotclearWatch admin page: compatibility audit of modules with the next Dotclear version.
 * @ingroup     DotclearWatch
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\DotclearWatch;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Date;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Li,
    Note,
    Para,
    Span,
    Submit,
    Table,
    Td,
    Text,
    Th,
    Thead,
    Tbody,
    Tr,
    Ul
};
use Dotclear\Helper\Html\Html;

class Manage
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $active = is_bool(My::settings()->getGlobal('active')) ? My::settings()->getGlobal('active') : false;
        if ($active === false) {
            // Redirect to config page
            App::backend()->url()->redirect(
                'admin.plugins',
                [
                    'module' => My::id(),
                    'conf'   => '1',
                ]
            );
        }

        // Check modules now
        if (!empty($_POST['audit_run'])) {
            if (Audit::run()) {
                Notices::addSuccessNotice(__('Modules have been checked.'));
            } else {
                Notices::addErrorNotice(__('There are no compatibility rules yet: send a report first.'));
            }

            My::redirect();
        }

        // Send report now, to get new rules
        if (!empty($_POST['send_report'])) {
            Utils::sendReport(true);
            $error = Utils::getError();
            if ($error !== '') {
                Notices::addWarningNotice($error);
            } elseif (Audit::getRules() === null) {
                Notices::addWarningNotice(__('Report sent, but the server did not answer with compatibility rules.'));
            } else {
                Notices::addSuccessNotice(__('Report sent, compatibility rules received.'));
            }

            My::redirect();
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(My::name(), My::cssLoad('manage') . My::jsLoad('manage'));

        echo Page::breadcrumb(
            [
                __('System')              => '',
                My::name()                => '',
                __('Compatibility audit') => '',
            ]
        ) .
        Notices::getNotices();

        $tz     = is_string(App::auth()->getInfo('user_tz')) ? App::auth()->getInfo('user_tz') : '';
        $rules  = Audit::getRules();
        $result = Audit::getResult();
        $next   = $rules['dotclear']['next'] ?? '';

        echo (new Text('p', __('Check if the installed plugins and themes will still work with the next Dotclear version. Distributed modules are not checked. Nothing about your modules is sent.')))
            ->render();

        // Rules
        if ($rules === null) {
            echo (new Text('p', __('There are no compatibility rules yet: they come with the answer to the report of this Dotclear. Send a report now to get them.')))
                ->class('info')
                ->render();
        } else {
            echo (new Note())
                ->class('form-note')
                ->text(sprintf(
                    __('Rules to go from your Dotclear %1$s to Dotclear %2$s, received on %3$s: %4$d rules.'),
                    Html::escapeHTML(App::config()->dotclearVersion()),
                    Html::escapeHTML($next),
                    Date::str(__('%Y-%m-%d %H:%M'), $rules['date'], $tz),
                    count($rules['rules'])
                ))
                ->render();

            // PHP version
            if (Audit::checkPhp() !== null) {
                echo (new Text('p', Html::escapeHTML(sprintf(
                    __('Dotclear %s requires PHP %s or later, this server uses PHP %s.'),
                    $next,
                    $rules['dotclear']['php_min'] ?? '',
                    PHP_VERSION
                ))))->class('warning-msg')->render();
            }
        }

        // Buttons
        echo (new Form('dcw-audit'))
            ->action(My::manageUrl())
            ->method('post')
            ->fields([
                $rules === null ?
                    (new Para())->items([
                        (new Submit('send_report'))->value(__('Send report now')),
                        ...My::hiddenFields(),
                    ]) :
                    (new Div())->items([
                        (new Note())
                            ->class('form-note')
                            ->text(__('Checking reads all files of the modules: it may take a while and slow down the site meanwhile.')),
                        (new Para())->items([
                            (new Submit('audit_run'))->value(__('Check modules now')),
                            (new Text(null, ' ')),
                            (new Submit('send_report'))->class('button')->value(__('Send report now')),
                            ...My::hiddenFields(),
                        ]),
                    ]),
            ])
            ->render();

        // Result
        if ($result !== null) {
            echo (new Text('h3', sprintf(__('Result of %s'), Date::str(__('%Y-%m-%d %H:%M'), $result['date'], $tz))))->render();

            if ($result['outdated']) {
                echo (new Text('p', __('Rules, modules or Dotclear have changed since this check: check modules again.')))
                    ->class('warning-msg')
                    ->render();
            }

            echo (new Text('p', self::summary($result['alerts'], $result['notices'], count($result['results']))))->render();

            $rows = [];
            foreach ($result['results'] as $module) {
                $rows[] = self::row($module);
                if (!empty($module['findings']) && is_array($module['findings'])) {
                    $rows[] = self::details($module);
                }
            }

            echo (new Div())->class('table-outer')->items([
                (new Table('dcw-modules'))->class('dcw-modules')->items([
                    (new Thead())->items([
                        (new Tr())->items([
                            (new Th())->scope('col')->class('first')->text(__('Module')),
                            (new Th())->scope('col')->class('nowrap')->text(__('Type')),
                            (new Th())->scope('col')->class('nowrap')->text(__('Version')),
                            (new Th())->scope('col')->class('nowrap')->text(__('Result')),
                        ]),
                    ]),
                    (new Tbody())->items($rows),
                ]),
            ])->render();
        }

        Page::helpBlock('DotclearWatch');
        Page::closeModule();
    }

    /**
     * Get the summary line of a result.
     */
    public static function summary(int $alerts, int $notices, int $total): string
    {
        return sprintf(
            __('%1$d modules checked: %2$d with alerts (will break with the next Dotclear version), %3$d with notices (deprecated, will break later or in some cases).'),
            $total,
            $alerts,
            $notices
        );
    }

    /**
     * Get the badge of a level.
     */
    public static function badge(string $level): Span
    {
        return (new Span(match ($level) {
            Audit::ALERT  => __('Alert'),
            Audit::NOTICE => __('Notice'),
            default       => __('Compatible'),
        }))->class('dcw-level dcw-' . (in_array($level, [Audit::ALERT, Audit::NOTICE], true) ? $level : 'ok'));
    }

    /**
     * Get a module line.
     *
     * @param   array<mixed>    $module
     */
    private static function row(array $module): Tr
    {
        $level = is_string($module['level'] ?? null) ? $module['level'] : 'ok';
        $id    = is_string($module['id'] ?? null) ? $module['id'] : '';
        $type  = is_string($module['type'] ?? null) ? $module['type'] : '';
        $name  = is_string($module['name'] ?? null) ? $module['name'] : $id;
        $count = [];
        if (!empty($module['alerts']) && is_numeric($module['alerts'])) {
            $count[] = sprintf(__('%d alert', '%d alerts', (int) $module['alerts']), (int) $module['alerts']);
        }

        if (!empty($module['notices']) && is_numeric($module['notices'])) {
            $count[] = sprintf(__('%d notice', '%d notices', (int) $module['notices']), (int) $module['notices']);
        }

        $line = (new Tr())->class('line' . (empty($module['enabled']) ? ' offline' : '') . (empty($module['findings']) ? '' : ' dcw-line dcw-line-' . $level));
        if (!empty($module['findings'])) {
            $line->id('dcw-' . $type . '-' . $id);
        }

        return $line->items([
            (new Td())->class('maximal')->items([
                (new Text(null, Html::escapeHTML($name))),
                (new Text(null, ' <code>' . Html::escapeHTML($id) . '</code>')),
                (new Text(null, empty($module['enabled']) ? ' (' . __('disabled') . ')' : '')),
            ]),
            (new Td())->class('nowrap')->text($type === 'theme' ? __('Theme') : __('Plugin')),
            (new Td())->class('nowrap')->text(Html::escapeHTML(is_string($module['version'] ?? null) ? $module['version'] : '')),
            (new Td())->class('nowrap')->items([
                self::badge($level),
                (new Text(null, $count !== [] ? ' ' . implode(', ', $count) : '')),
            ]),
        ]);
    }

    /**
     * Get the details line of a module, findings grouped by file.
     *
     * @param   array<mixed>    $module
     */
    private static function details(array $module): Tr
    {
        $id     = is_string($module['id'] ?? null) ? $module['id'] : '';
        $type   = is_string($module['type'] ?? null) ? $module['type'] : '';
        $byfile = [];
        foreach ((array) ($module['findings'] ?? []) as $f) {
            if (is_array($f)) {
                $byfile[is_string($f['file'] ?? null) ? $f['file'] : ''][] = $f;
            }
        }

        $files = [];
        foreach ($byfile as $file => $findings) {
            $items = [];
            foreach ($findings as $f) {
                $str     = fn (string $k): string => is_scalar($f[$k] ?? null) ? (string) $f[$k] : '';
                $markers = [];
                if ($str('match') === 'probable') {
                    $markers[] = __('probable');
                }

                if (!empty($f['guarded'])) {
                    $markers[] = __('the module may already handle it');
                }

                if (!empty($f['forecast'])) {
                    $markers[] = sprintf(__('planned for Dotclear %s'), $str('since'));
                }

                $items[] = (new Li())->items([
                    self::badge($str('level')),
                    (new Text(null, ' ' . sprintf(__('line %d:'), (int) $str('line')) . ' <code>' . Html::escapeHTML($str('symbol')) . '</code>')),
                    (new Text(null, $markers !== [] ? ' (' . implode(', ', $markers) . ')' : '')),
                    (new Text(null, $str('note') !== '' ? '<br>' . Html::escapeHTML($str('note')) : '')),
                    (new Text(null, $str('replace') !== '' ? '<br>' . __('Use instead:') . ' <code>' . Html::escapeHTML($str('replace')) . '</code>' : '')),
                ]);
            }

            $files[] = (new Div())->class('dcw-file')->items([
                (new Text('p', '<strong>' . Html::escapeHTML($file) . '</strong>')),
                (new Ul())->items($items),
            ]);
        }

        return (new Tr('dcw-' . $type . '-' . $id . '-details'))
            ->class('dcw-details')
            ->items([
                (new Td())->colspan(4)->class('expand')->items($files),
            ]);
    }
}
