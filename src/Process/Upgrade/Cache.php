<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   Cache cleaner helper.
 *
 * @since   2.29
 */
class Cache
{
    use TraitProcess;

    public static function init(): bool
    {
        App::upgrade()->page()->checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        try {
            if (!empty($_POST['cleartplcache'])) {
                App::cache()->emptyTemplatesCache();
                App::upgrade()->notices()->addSuccessNotice(__('Templates cache directory emptied.'));
                App::upgrade()->url()->redirect('upgrade.cache');
            }
            if (!empty($_POST['clearrepocache'])) {
                App::cache()->emptyModulesStoreCache();
                App::upgrade()->notices()->addSuccessNotice(__('Repositories cache directory emptied.'));
                App::upgrade()->url()->redirect('upgrade.cache');
            }
            if (!empty($_POST['clearversionscache'])) {
                App::cache()->emptyDotclearVersionsCache();
                App::upgrade()->notices()->addSuccessNotice(__('Dotclear versions cache directory emptied.'));
                App::upgrade()->url()->redirect('upgrade.cache');
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        App::upgrade()->page()->open(
            __('Cache'),
            '',
            App::upgrade()->page()->breadcrumb(
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
                            ->class('form-buttons')
                            ->separator(' ')
                            ->items([
                                (new Submit(['cleartplcache']))
                                    ->value(__('Empty templates cache directory')),
                                (new Submit(['clearrepocache']))
                                    ->value(__('Empty repositories cache directory')),
                                (new Submit(['clearversionscache']))
                                    ->value(__('Empty Dotclear versions cache directory')),
                                App::nonce()->formNonce(),
                            ]),
                    ]),
            ])
            ->render();

        App::upgrade()->page()->close();
    }
}
