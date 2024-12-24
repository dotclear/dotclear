<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcCKEditor;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup dcCKEditor
 */
class Manage extends Process
{
    public static function init(): bool
    {
        // Menu is only accessible if admin/superadmin
        App::backend()->editor_is_admin = self::status(My::checkContext(My::MENU));

        if (!self::status()) {
            return false;
        }

        App::backend()->editor_cke_active                      = My::settings()->active;
        App::backend()->editor_cke_alignment_buttons           = My::settings()->alignment_buttons;
        App::backend()->editor_cke_list_buttons                = My::settings()->list_buttons;
        App::backend()->editor_cke_textcolor_button            = My::settings()->textcolor_button;
        App::backend()->editor_cke_background_textcolor_button = My::settings()->background_textcolor_button;
        App::backend()->editor_cke_custom_color_list           = My::settings()->custom_color_list;
        App::backend()->editor_cke_colors_per_row              = My::settings()->colors_per_row;
        App::backend()->editor_cke_cancollapse_button          = My::settings()->cancollapse_button;
        App::backend()->editor_cke_format_select               = My::settings()->format_select;
        App::backend()->editor_cke_format_tags                 = My::settings()->format_tags;
        App::backend()->editor_cke_table_button                = My::settings()->table_button;
        App::backend()->editor_cke_clipboard_buttons           = My::settings()->clipboard_buttons;
        App::backend()->editor_cke_action_buttons              = My::settings()->action_buttons;
        App::backend()->editor_cke_disable_native_spellchecker = My::settings()->disable_native_spellchecker;

        if (!empty($_GET['config'])) {
            // text/javascript response stop stream just after including file
            ManagePostConfig::load();
            exit();
        }

        if (!App::backend()->editor_is_admin) {
            // Avoid any further process if not admin/superadmin
            return false;
        }

        App::backend()->editor_cke_was_actived = App::backend()->editor_cke_active;

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                App::backend()->editor_cke_active = (empty($_POST['dcckeditor_active'])) ? false : true;
                My::settings()->put('active', App::backend()->editor_cke_active, 'boolean');

                // change other settings only if they were in HTML page
                if (App::backend()->editor_cke_was_actived) {
                    App::backend()->editor_cke_alignement_buttons = (empty($_POST['dcckeditor_alignment_buttons'])) ? false : true;
                    My::settings()->put('alignment_buttons', App::backend()->editor_cke_alignement_buttons, 'boolean');

                    App::backend()->editor_cke_list_buttons = (empty($_POST['dcckeditor_list_buttons'])) ? false : true;
                    My::settings()->put('list_buttons', App::backend()->editor_cke_list_buttons, 'boolean');

                    App::backend()->editor_cke_textcolor_button = (empty($_POST['dcckeditor_textcolor_button'])) ? false : true;
                    My::settings()->put('textcolor_button', App::backend()->editor_cke_textcolor_button, 'boolean');

                    App::backend()->editor_cke_background_textcolor_button = (empty($_POST['dcckeditor_background_textcolor_button'])) ? false : true;
                    My::settings()->put('background_textcolor_button', App::backend()->editor_cke_background_textcolor_button, 'boolean');

                    App::backend()->editor_cke_custom_color_list = str_replace(['#', ' '], '', $_POST['dcckeditor_custom_color_list']);
                    My::settings()->put('custom_color_list', App::backend()->editor_cke_custom_color_list, 'string');

                    App::backend()->editor_cke_colors_per_row = abs((int) $_POST['dcckeditor_colors_per_row']);
                    My::settings()->put('colors_per_row', App::backend()->editor_cke_colors_per_row);

                    App::backend()->editor_cke_cancollapse_button = (empty($_POST['dcckeditor_cancollapse_button'])) ? false : true;
                    My::settings()->put('cancollapse_button', App::backend()->editor_cke_cancollapse_button, 'boolean');

                    App::backend()->editor_cke_format_select = (empty($_POST['dcckeditor_format_select'])) ? false : true;
                    My::settings()->put('format_select', App::backend()->editor_cke_format_select, 'boolean');

                    // default tags : p;h1;h2;h3;h4;h5;h6;pre;address
                    App::backend()->editor_cke_format_tags = 'p;h1;h2;h3;h4;h5;h6;pre;address';

                    $allowed_tags = explode(';', App::backend()->editor_cke_format_tags);
                    if (!empty($_POST['dcckeditor_format_tags'])) {
                        $tags     = explode(';', (string) $_POST['dcckeditor_format_tags']);
                        $new_tags = true;
                        foreach ($tags as $tag) {
                            if (!in_array($tag, $allowed_tags)) {
                                $new_tags = false;

                                break;
                            }
                        }
                        if ($new_tags) {
                            App::backend()->editor_cke_format_tags = $_POST['dcckeditor_format_tags'];
                        }
                    }
                    My::settings()->put('format_tags', App::backend()->editor_cke_format_tags, 'string');

                    App::backend()->editor_cke_table_button = (empty($_POST['dcckeditor_table_button'])) ? false : true;
                    My::settings()->put('table_button', App::backend()->editor_cke_table_button, 'boolean');

                    App::backend()->editor_cke_clipboard_buttons = (empty($_POST['dcckeditor_clipboard_buttons'])) ? false : true;
                    My::settings()->put('clipboard_buttons', App::backend()->editor_cke_clipboard_buttons, 'boolean');

                    App::backend()->editor_cke_action_buttons = (empty($_POST['dcckeditor_action_buttons'])) ? false : true;
                    My::settings()->put('action_buttons', App::backend()->editor_cke_action_buttons, 'boolean');

                    App::backend()->editor_cke_disable_native_spellchecker = (empty($_POST['dcckeditor_disable_native_spellchecker'])) ? false : true;
                    My::settings()->put('disable_native_spellchecker', App::backend()->editor_cke_disable_native_spellchecker, 'boolean');
                }

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
        if (!self::status()) {
            return;
        }

        Page::openModule(My::name());

        echo
        Page::breadcrumb([
            __('Plugins')    => '',
            __('dcCKEditor') => '',
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
                            (new Checkbox('dcckeditor_active', App::backend()->editor_cke_active))
                                ->value(1)
                                ->label((new Label(__('Enable dcCKEditor plugin'), Label::INSIDE_TEXT_AFTER))),
                        ]),
                ]);

            if (App::backend()->editor_cke_active) {
                // Settings
                $fields[] = (new Fieldset())
                    ->legend(new Legend(__('Options')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_alignment_buttons', App::backend()->editor_cke_alignment_buttons))
                                    ->value(1)
                                    ->label((new Label(__('Add alignment buttons'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_list_buttons', App::backend()->editor_cke_list_buttons))
                                    ->value(1)
                                    ->label((new Label(__('Add lists buttons'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_textcolor_button', App::backend()->editor_cke_textcolor_button))
                                    ->value(1)
                                    ->label((new Label(__('Add text color button'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_background_textcolor_button', App::backend()->editor_cke_background_textcolor_button))
                                    ->value(1)
                                    ->label((new Label(__('Add background text color button'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Fieldset())
                            ->legend((new Legend(__('Custom colors list:')))->id('dcckeditor_custom_color_list_label'))
                            ->fields([
                                (new Para())
                                    ->class('area')
                                    ->items([
                                        (new Textarea('dcckeditor_custom_color_list', Html::escapeHTML(App::backend()->editor_cke_custom_color_list)))
                                            //->label(new Label(__('Custom colors list:'), Label::INSIDE_TEXT_BEFORE))
                                            ->extra('aria-labelledby="dcckeditor_custom_color_list_label"')
                                            ->cols(60)
                                            ->rows(5),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->items([
                                        (new Text(null, __('Add colors without # separated by a comma.'))),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->items([
                                        (new Text(null, __('Leave empty to use the default palette:'))),
                                        (new Text('blockquote', '<pre><code>1abc9c,2ecc71,3498db,9b59b6,4e5f70,f1c40f,16a085,27ae60,2980b9,8e44ad,2c3e50,f39c12,e67e22,e74c3c,ecf0f1,95a5a6,dddddd,ffffff,d35400,c0392b,bdc3c7,7f8c8d,999999,000000</code></pre>')),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->items([
                                        (new Text(null, __('Example of custom color list:'))),
                                        (new Text('blockquote', '<pre><code>000,800000,8b4513,2f4f4f,008080,000080,4b0082,696969,b22222,a52a2a,daa520,006400,40e0d0,0000cd,800080,808080,f00,ff8c00,ffd700,008000,0ff,00f,ee82ee,a9a9a9,ffa07a,ffa500,ffff00,00ff00,afeeee,add8e6,dda0dd,d3d3d3,fff0f5,faebd7,ffffe0,f0fff0,f0ffff,f0f8ff,e6e6fa,fff</code></pre>')),
                                    ]),
                            ]),
                        (new Para())
                            ->items([
                                (new Number('dcckeditor_colors_per_row', 4, 16, (int) App::backend()->editor_cke_colors_per_row))
                                    ->default(6)
                                    ->label((new Label(__('Colors per row in palette:'), Label::INSIDE_TEXT_BEFORE))),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Valid range: 4 to 16')),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_cancollapse_button', App::backend()->editor_cke_cancollapse_button))
                                    ->value(1)
                                    ->label((new Label(__('Add collapse button'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_format_select', App::backend()->editor_cke_format_select))
                                    ->value(1)
                                    ->label((new Label(__('Add format selection'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Fieldset())
                            ->legend((new Legend(__('Custom formats')))->id('dcckeditor_format_tags_label'))
                            ->fields([
                                (new Para())
                                    ->items([
                                        (new Input('dcckeditor_format_tags'))
                                            //->label((new Label(__('Custom formats'), Label::INSIDE_TEXT_BEFORE)))
                                            ->extra('aria-labelledby="dcckeditor_format_tags_label"')
                                            ->size(100)
                                            ->maxlength(255)
                                            ->value(Html::escapeHTML(App::backend()->editor_cke_format_tags))
                                            ->placeholder('p;h1;h2;h3;h4;h5;h6;pre;address'),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->text(__('Default formats are p;h1;h2;h3;h4;h5;h6;pre;address')),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_table_button', App::backend()->editor_cke_table_button))
                                    ->value(1)
                                    ->label((new Label(__('Add table button'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_clipboard_buttons', App::backend()->editor_cke_clipboard_buttons))
                                    ->value(1)
                                    ->label((new Label(__('Add clipboard buttons'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Copy, Paste, Paste Text, Paste from Word')),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_action_buttons', App::backend()->editor_cke_action_buttons))
                                    ->value(1)
                                    ->label((new Label(__('Add undo/redo buttons'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('dcckeditor_disable_native_spellchecker', App::backend()->editor_cke_disable_native_spellchecker))
                                    ->value(1)
                                    ->label((new Label(__('Disables the built-in spell checker if the browser provides one'), Label::INSIDE_TEXT_AFTER))),
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

            // Render form
            echo (new Form('dcckeditor_form'))
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->fields($fields)
            ->render();
        }

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
