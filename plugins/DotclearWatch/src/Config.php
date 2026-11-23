<?php

/**
 * @brief       DotclearWatch configuration class.
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
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;

class Config
{
    use TraitProcess;

    /**
     * List of hidden modules.
     */
    private static string $hidden_modules = '';

    /**
     * Distant API URL.
     */
    private static string $distant_api_url = '';

    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (App::auth()->prefs()->get('interface')->getBool('colorsyntax', false)) {
            App::behavior()->addBehavior('pluginsToolsHeadersV2', fn (bool $plugin): string => Page::jsLoadCodeMirror(App::auth()->prefs()->get('interface')->getStr('colorsyntax_theme', false)));
        }

        self::$hidden_modules  = is_string(My::settings()->getGlobal('hidden_modules')) ? My::settings()->getGlobal('hidden_modules') : '';
        self::$distant_api_url = is_string(My::settings()->getGlobal('distant_api_url')) ? My::settings()->getGlobal('distant_api_url') : '';

        if (empty($_POST)) {
            return true;
        }

        if (isset($_POST['clear_cache']) && !empty($_POST['clear_cache'])) {
            Utils::clearCache();
            Notices::addSuccessNotice(__('Cache directory successfully cleared.'));
        }

        self::$distant_api_url = !empty($_POST['distant_api_url']) && is_string($_POST['distant_api_url']) ? $_POST['distant_api_url'] : Utils::DISTANT_API_URL;
        $hiddens               = [];
        foreach (explode(',', (isset($_POST['hidden_modules']) && is_string($_POST['hidden_modules']) ? $_POST['hidden_modules'] : '')) as $hidden) {
            $hidden = trim($hidden);
            if ($hidden !== '') {
                $hiddens[] = $hidden;
            }
        }

        self::$hidden_modules = implode(',', array_unique($hiddens));

        My::settings()->put('hidden_modules', self::$hidden_modules, 'string', 'Hidden modules from report', true, true);
        My::settings()->put('distant_api_url', self::$distant_api_url, 'string', 'Distant API report URL', true, true);
        Notices::addSuccessNotice(__('Settings successfully updated.'));

        if (isset($_POST['send_report']) && !empty($_POST['send_report'])) {
            // Use new URL, settings are not reloaded yet
            Utils::sendReport(true, self::$distant_api_url);
            $error = Utils::getError();
            if (!empty($error)) {
                Notices::addWarningNotice($error);
            } else {
                Notices::addSuccessNotice(__('Report sent.'));
            }
        }

        App::backend()->url()->redirect('admin.plugins', ['module' => My::id(), 'conf' => '1']);

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        echo
        (new Div())->items([
            (new Text('p', __('Settings are globals. Reports are by blog.')))->class('message'),
            (Utils::getError() !== '' ? (new Text('p', sprintf(__('Last report failed: %s'), Html::escapeHTML(Utils::getError()))))->class('warning-msg') : (new None())),
            (new Ul())->items([
                (new Li())->text(sprintf(__('API: %s'), Utils::DISTANT_API_VERSION)),
                (new Li())->text(sprintf(__('UID: %s'), Utils::getClient())),
            ]),
            (new Para())->items([
                (new Label(__('Hidden modules:')))->for('hidden_modules'),
                (new Input('hidden_modules'))->class('maximal')->size(65)->maxlength(255)->value(self::$hidden_modules),
            ]),
            (new Note())->class('form-note')->text(__('This is the comma separated list of plugins IDs and themes IDs to ignore in report.')),
            (new Para())->items([
                (new Label(__('Distant API URL:')))->for('distant_api_url'),
                (new Input('distant_api_url'))->class('maximal')->size(65)->maxlength(255)->value(self::$distant_api_url),
            ]),
            (new Note())->class('form-note')->text(__('This is the URL of the API to send report. Leave empty to reset value.')),
            (new Para())->items([
                (new Checkbox('clear_cache', false))->value(1),
                (new Label(__('Clear reports cache directory'), Label::OUTSIDE_LABEL_AFTER))->for('clear_cache')->class('classic'),
            ]),
            (new Note())->class('form-note')->text(__('This deletes all blogs reports in cache.')),
            (new Para())->items([
                (new Checkbox('send_report', false))->value(1),
                (new Label(__('Send report now'), Label::OUTSIDE_LABEL_AFTER))->for('send_report')->class('classic'),
            ]),
            (new Note())->class('form-note')->text(__('This sent report for current blog even if report exists in cache.')),
        ])->render();

        $contents = Utils::getReport();
        if (!empty($contents)) {
            echo
            (new Para())->items([
                (new Label(__('Report that will be sent for this blog:')))->for('report_contents'),
                (new Textarea('report_contents', Html::escapeHTML($contents)))
                ->cols(165)
                ->rows(14)
                ->readonly(true)
                ->class('maximal'),
            ])->render() .
            (
                App::auth()->prefs()->get('interface')->getBool('colorsyntax', false) ?
                Page::jsRunCodeMirror(My::id() . 'editor', 'report_contents', 'json', App::auth()->prefs()->get('interface')->getStr('colorsyntax_theme', false)) : ''
            );
        }
    }
}
