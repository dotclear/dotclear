<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module backend manage process.
 * @ingroup pings
 */
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

        App::backend()->pings_uris = [];

        try {
            // Pings URIs are managed globally (for all blogs)
            App::backend()->pings_uris = My::settings()->getGlobal('pings_uris');
            if (!App::backend()->pings_uris) {
                App::backend()->pings_uris = [];
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

                Notices::addSuccessNotice(__('Settings have been successfully updated.'));
                My::redirect();
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        Page::openModule(My::name());

        echo
        Page::breadcrumb(
            [
                __('Plugins')             => '',
                __('Pings configuration') => '',
            ]
        );

        $rows  = [];
        $index = 0;
        foreach (App::backend()->pings_uris as $name => $uri) {
            if (!empty($_GET['test'])) {
                try {
                    PingsAPI::doPings($uri, 'Example site', 'http://example.com');
                    $status = (new Img('images/check-on.svg'))->class(['mark', 'mark-check-on'])->alt('OK');
                } catch (Exception $e) {
                    $status = (new Set())
                        ->items([
                            (new Img('images/check-off.svg'))->class(['mark', 'mark-check-off'])->alt(__('Error')),
                            (new Text(null, ' ' . $e->getMessage())),
                        ]);
                }
            } else {
                $status = new None();
            }

            $rows[] = (new Tr())
                ->class('line')
                ->cols([
                    (new Td())
                        ->items([
                            (new Input(['pings_srv_name[]', 'pings_srv_name-' . $index]))
                                ->size(20)
                                ->maxlength(128)
                                ->value(Html::escapeHTML((string) $name)),
                        ]),
                    (new Td())
                        ->items([
                            (new Url(['pings_srv_uri[]', 'pings_srv_uri-' . $index]))
                                ->size(40)
                                ->value(Html::escapeHTML($uri)),
                        ]),
                    (new Td())
                        ->items([$status]),
                ]);

            $index++;
        }

        $rows[] = (new Tr())
            ->class('line')
            ->cols([
                (new Td())
                    ->items([
                        (new Input(['pings_srv_name[]', 'pings_srv_name2']))
                            ->size(20)
                            ->maxlength(128),
                    ]),
                (new Td())
                    ->items([
                        (new Url(['pings_srv_uri[]', 'pings_srv_uri2']))
                            ->size(40),
                    ]),
                (new Td()),
            ]);

        $table = (new Table())
            ->thead((new Thead())
                ->rows([
                    (new Tr())->cols([
                        (new Th())->text(__('Service name:'))->class('minimal'),
                        (new Th())->text(__('Service URI:'))->class('minimal'),
                        (new Th())->text(empty($_GET['test']) ? '' : __('Status:')),
                    ]),
                ]))
            ->tbody((new Tbody())
                ->rows($rows));

        echo (new Form('pings-form'))
            ->method('post')
            ->action(App::backend()->getPageURL())
            ->fields([
                (new Para())
                    ->items([
                        (new Checkbox('pings_active', (bool) My::settings()->pings_active))
                            ->value(1)
                            ->label(new Label(__('Activate pings extension'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Checkbox('pings_auto', (bool) My::settings()->pings_auto))
                            ->value(1)
                            ->label(new Label(__('Auto pings all services on first publication of entry (current blog only)'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Link('test'))
                            ->class('button')
                            ->href(App::backend()->getPageURL() . '&amp;test=1')
                            ->text(__('Test ping services')),
                    ]),
                $table,
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        ...My::hiddenFields(),
                        (new Submit(['save'], __('Save'))),
                        (new Button(['back'], __('Back')))
                            ->class(['go-back', 'reset', 'hidden-if-no-js']),
                    ]),
            ])
        ->render();

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
