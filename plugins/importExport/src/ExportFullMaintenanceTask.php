<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\maintenance\MaintenanceTask;

/**
 * @brief   The export full maintenance task.
 * @ingroup importExport
 */
class ExportFullMaintenanceTask extends MaintenanceTask
{
    protected string $tab   = 'backup';
    protected string $group = 'zipfull';

    protected string $export_name;
    protected string $export_type;

    /**
     * Initialize task object.
     */
    protected function init(): void
    {
        $this->name = __('Database export');
        $this->task = __('Download database of all blogs');

        $this->export_name = 'dotclear-backup.txt';
        $this->export_type = 'export_all';
    }

    public function execute(): bool|int
    {
        // Create zip file
        if (!empty($_POST['file_name'])) {
            if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                $this->error = __('Password verification failed');

                return false;
            }

            // This process make an http redirect
            $task = new ExportFlatMaintenanceTask();
            $task->setURL((string) $this->id);
            $task->process($this->export_type);
        }
        // Go to step and show form
        else {
            return 1;
        }

        return true;
    }

    public function step(): ?string
    {
        // Download zip file
        if (isset($_SESSION['export_file']) && file_exists($_SESSION['export_file'])) {
            // Log task execution here as we sent file and stop script
            $this->log();

            // This process send file by http and stop script
            $task = new ExportFlatMaintenanceTask();
            $task->setURL((string) $this->id);
            $task->process('ok');

            return null;
        }

        return (new Set())->items([
            (new Note())
                ->class('form-note')
                ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
            (new Para())->items([
                (new Input('file_name'))
                    ->size(50)
                    ->maxlength(255)
                    ->value(Html::escapeHTML(date('Y-m-d-H-i-') . $this->export_name))
                    ->required(true)
                    ->label(
                        (new Label(
                            (new Span('*'))->render() . __('File name:'),
                            Label::INSIDE_TEXT_BEFORE
                        ))
                    )
                    ->title(__('Required field')),
            ]),
            (new Para())->items([
                (new Checkbox('file_zip'))
                ->label(new Label(__('Compress file'), Label::INSIDE_LABEL_AFTER)),
            ]),
            (new Para())->items([
                (new Password('your_pwd'))
                ->size(20)
                ->maxlength(255)
                ->required(true)
                ->placeholder(__('Password'))
                ->autocomplete('current-password')
                ->label(
                    (new Label(
                        (new Span('*'))->render() . __('Your password:'),
                        Label::INSIDE_TEXT_BEFORE
                    ))
                )
                ->title(__('Required field')),
            ]),
        ])
        ->render();
    }
}
