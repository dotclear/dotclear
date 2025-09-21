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
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   Core backup and restore page.
 *
 * @since   2.29
 */
class Backup
{
    use TraitProcess;

    /**
     * Backups archives.
     *
     * @var     array<int, string>  $archives
     */
    private static array $archives = [];

    public static function init(): bool
    {
        App::upgrade()->page()->checkSuper();

        // Check backup path existence
        if (!is_dir(App::config()->backupRoot())) {
            App::upgrade()->page()->open(
                __('Dotclear update'),
                '',
                App::upgrade()->page()->breadcrumb(
                    [
                        __('Dotclear update')     => '',
                        __('Backups and restore') => '',
                    ]
                )
            );

            echo (new Para())
                ->items([
                    (new Text('h3', __('Precheck update error'))),
                    (new Note())
                        ->text(__('It seems that backup directory does not exist, upgrade can not be performed.')),
                ])
                ->render();

            App::upgrade()->page()->close();
            dotclear_exit();
        }

        $archives = [];
        foreach (Files::scandir(App::config()->backupRoot()) as $v) {
            if (preg_match('/backup-([0-9A-Za-z\.-]+).zip/', $v)) {
                $archives[] = $v;
            }
        }
        if ($archives !== []) {
            usort($archives, fn (string $a, string $b): int => $a <=> $b);
        }

        self::$archives = $archives;

        return self::status(true);
    }

    public static function process(): bool
    {
        # delete all backup files except the last one
        if (!empty($_POST['b_delall']) && count(self::$archives) > 1) {
            $done  = false;
            $stack = self::$archives;
            array_pop($stack); // keep last backup
            foreach ($stack as $b_file) {
                try {
                    @unlink(App::config()->backupRoot() . '/' . $b_file);
                    $done = true;
                } catch (Exception) {
                    App::error()->add(sprintf(__('Unable to delete file %s'), Html::escapeHTML($b_file)));
                }
            }
            if ($done) {
                App::upgrade()->notices()->addSuccessNotice(__('Backup deleted.'));
            }
            App::upgrade()->url()->redirect('upgrade.backup');
        }

        # Revert or delete a backup file
        if (!empty($_POST['backup_file']) && in_array($_POST['backup_file'], self::$archives)) {
            $b_file = $_POST['backup_file'];

            try {
                if (!empty($_POST['b_del'])) {
                    if (!@unlink(App::config()->backupRoot() . '/' . $b_file)) {
                        throw new Exception(sprintf(__('Unable to delete file %s'), Html::escapeHTML($b_file)));
                    }
                    App::upgrade()->notices()->addSuccessNotice(__('Backup deleted.'));
                    App::upgrade()->url()->redirect('upgrade.backup');
                }

                if (!empty($_POST['b_revert'])) {
                    $zip = new Unzip(App::config()->backupRoot() . '/' . $b_file);
                    $zip->unzipAll(App::config()->backupRoot() . '/');
                    @unlink(App::config()->backupRoot() . '/' . $b_file);
                    App::upgrade()->notices()->addSuccessNotice(__('Backup restored.'));
                    App::upgrade()->url()->redirect('upgrade.backup');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (self::$archives === []) {
            $items[] = (new Note())
                ->text(__('There are no backups available.'))
                ->class('message');
        } else {
            $archives = self::$archives;

            $options = [];
            $i       = 0;
            foreach ($archives as $archive) {
                $i++;
                $options[] = (new Tr())
                    ->class('line')
                    ->items([
                        (new Td())
                            ->class('minimal')
                            ->items([
                                (new Radio(['backup_file', 'backup_file' . $i]))
                                    ->value(Html::escapeHTML($archive)),
                            ]),
                        (new Td())
                            ->class('maximal')
                            ->items([
                                (new Label(Html::escapeHTML($archive), Label::OUTSIDE_LABEL_AFTER, 'backup_file' . $i))
                                    ->class('classic'),
                            ]),
                    ]);
            }

            $items[] = (new Form('bck'))
                ->class('fieldset')
                ->method('post')
                ->action(App::upgrade()->url()->get('upgrade.backup'))
                ->fields([
                    (new Text('h4', __('Backups of previously updates'))),
                    (new Div())
                        ->class('table-outer')
                        ->items([
                            (new Table())
                                ->items($options),
                        ]),
                    (new Para())
                        ->separator(' ')
                        ->items([
                            App::nonce()->formNonce(),
                            (new Submit(['b_revert'], __('Revert to selected file'))),
                            (new Submit(['b_del'], __('Delete selected file')))
                                ->class('delete'),
                        ]),
                    (new Para())
                        ->class('warning')
                        ->separator(' ')
                        ->items([
                            (new Strong(__('Please note that reverting your Dotclear version may have some unwanted side-effects. Consider reverting only if you experience strong issues with this new version.'))),
                            (new Text(null, sprintf(__('You should not revert to version prior to last one (%s).'), end($archives)))),
                        ]),
                    (new Para())
                        ->items([
                            (new Submit(['b_delall'], __('Delete all files but last')))
                                ->class('delete'),
                        ]),
                ]);
        }

        App::upgrade()->page()->open(
            __('Backups'),
            App::upgrade()->page()->jsLoad('js/_backup.js'),
            App::upgrade()->page()->breadcrumb(
                [
                    __('Dotclear update')     => '',
                    __('Backups and restore') => '',
                ]
            )
        );

        echo (new Div())
            ->items([
                (new Note())
                        ->class('static-msg')
                        ->text(__('On this page you can revert your previous installation or delete theses files.')),
                ...$items,
            ])
            ->render();

        App::upgrade()->page()->helpBlock('core_backup');
        App::upgrade()->page()->close();
    }
}
