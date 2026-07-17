<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup dcLegacyEditor
 */
class Manage
{
    use TraitProcess;

    protected static bool $is_admin;

    protected static bool $active;

    protected static bool $dynamic;

    public static function init(): bool
    {
        self::$is_admin = self::status(My::checkContext(My::MANAGE));
        self::$active   = self::status(My::checkContext(My::MANAGE)) && My::settings()->getBool('active');
        self::$dynamic  = self::status(My::checkContext(My::MANAGE)) && My::settings()->getBool('dynamic');

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                self::$active = !empty($_POST['dclegacyeditor_active']);
                My::settings()->put('active', self::$active, App::blogWorkspace()::NS_BOOL);

                self::$dynamic = !empty($_POST['dclegacyeditor_dynamic']);
                My::settings()->put('dynamic', self::$dynamic, App::blogWorkspace()::NS_BOOL);

                App::backend()->notices()->addSuccessNotice(__('The configuration has been updated.'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        App::backend()->page()->openModule(My::name());

        echo
        App::backend()->page()->breadcrumb([
            __('Plugins')         => '',
            __('Dotclear editor') => '',
        ]) .
        App::backend()->notices()->getNotices();

        if (self::$is_admin) {
            $fields = [];

            $active  = self::$active;
            $dynamic = self::$dynamic;

            // Activation
            $fields[] = (new Fieldset())
                ->legend(new Legend(__('Plugin activation')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('dclegacyeditor_active', $active))
                                ->value(1)
                                ->label((new Label(__('Enable standard editor plugin'), Label::INSIDE_TEXT_AFTER))),
                        ]),
                ]);

            // Settings
            if ($active) {
                $fields[] = (new Fieldset())
                    ->legend(new Legend(__('Plugin settings')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Checkbox('dclegacyeditor_dynamic', $dynamic))
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

        App::backend()->page()->helpBlock(My::id());

        App::backend()->page()->closeModule();
    }
}
