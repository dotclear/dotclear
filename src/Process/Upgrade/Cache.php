<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Note,
    Para,
    Submit,
    Text
};
use Exception;

/**
 * @brief   Cache cleaner helper.
 *
 * @since   2.29
 */
class Cache extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        try {
            if (!empty($_POST['cleartplcache'])) {
                App::cache()->emptyTemplatesCache();
                Notices::addSuccessNotice(__('Templates cache directory emptied.'));
                App::upgrade()->url()->redirect('upgrade.cache');
            }
            if (!empty($_POST['clearrepocache'])) {
                App::cache()->emptyModulesStoreCache();
                Notices::addSuccessNotice(__('Repositories cache directory emptied.'));
                App::upgrade()->url()->redirect('upgrade.cache');
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            __('Cache'),
            '',
            Page::breadcrumb(
                [
                    __('Dotclear update')  => '',
                    __('Cache management') => '',
                ]
            )
        );

        echo (new Form('cache'))
            ->action(App::upgrade()->url()->get('upgrade.cache'))
            ->method('post')
            ->fields([
                (new Note())
                    ->class('static-msg')
                    ->text(__('On this page, you can clear templates and repositories cache.')),
                (new Div())
                    ->class('fieldset')
                    ->items([
                        (new Text('h4', __('Cache folders'))),
                        (new Para())
                            ->separator(' ')
                            ->items([
                                (new Submit(['cleartplcache']))
                                    ->value(__('Empty templates cache directory')),
                                (new Submit(['clearrepocache']))
                                    ->value(__('Empty repositories cache directory')),
                                App::nonce()->formNonce(),
                            ]),
                    ]),
            ])
            ->render();

        Page::close();
    }
}
