<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup dcLegacyEditor
 */
class Manage extends Process
{
    public static function init(): bool
    {
        App::backend()->editor_is_admin    = self::status(My::checkContext(My::MANAGE));
        App::backend()->editor_std_active  = self::status(My::checkContext(My::MANAGE)) && My::settings()->active;
        App::backend()->editor_std_dynamic = self::status(My::checkContext(My::MANAGE)) && My::settings()->dynamic;

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                App::backend()->editor_std_active = !empty($_POST['dclegacyeditor_active']);
                My::settings()->put('active', App::backend()->editor_std_active, 'boolean');

                App::backend()->editor_std_dynamic = !empty($_POST['dclegacyeditor_dynamic']);
                My::settings()->put('dynamic', App::backend()->editor_std_dynamic, 'boolean');

                Notices::addSuccessNotice(__('The configuration has been updated.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::openModule(My::name());

        echo
        Page::breadcrumb([
            __('Plugins')        => '',
            __('dcLegacyEditor') => '',
        ]) .
        Notices::getNotices();

        if (App::backend()->editor_is_admin) {
            $fields = [];

            // Activation
            $fields[] = (new Fieldset())
                ->legend(new Legend(__('Plugin activation')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('dclegacyeditor_active', App::backend()->editor_std_active))
                                ->value(1)
                                ->label((new Label(__('Enable standard editor plugin'), Label::INSIDE_TEXT_AFTER))),
                        ]),
                ]);

            // Settings
            if (App::backend()->editor_std_active) {
                $fields[] = (new Fieldset())
                    ->legend(new Legend(__('Plugin settings')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Checkbox('dclegacyeditor_dynamic', App::backend()->editor_std_dynamic))
                                    ->value(1)
                                    ->label((new Label(__('Adjust height of input area during editing'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                    ]);
            }

            // Buttons
            $fields[] = (new Para())
                ->class('form-buttons')
                ->items([
                    ...My::hiddenFields(),
                    (new Submit(['saveconfig'], __('Save configuration'))),
                    (new Button(['back'], __('Back')))
                        ->class(['go-back', 'reset', 'hidden-if-no-js']),
                ]);

            echo (new Form('dclegacyeditor_form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields($fields)
            ->render();
        }

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
