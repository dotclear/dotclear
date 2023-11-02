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
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Label,
    Para,
    Radio,
    Submit,
    Text
};
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   Core backup and restore page.
 *
 * @since   2.29
 */
class Backup extends Process
{
    /**
     * Backups archives.
     *
     * @var     array<int, string>  $archives
     */
    private static array $archives = [];

    public static function init(): bool
    {
        Page::checkSuper();

        // Check backup path existence
        if (!is_dir(App::config()->backupRoot())) {
            Page::open(
                __('Dotclear update'),
                '',
                Page::breadcrumb(
                    [
                        __('Dotclear update')     => '',
                        __('Backups and restore') => '',
                    ]
                )
            );

            echo (new Para())
                ->items([
                    (new Text('h3', __('Precheck update error'))),
                    (new Text('p', __('It seems that backup directory does not exist, upgrade can not be performed.'))),
                ])
                ->render();

            Page::close();
            exit;
        }

        $archives = [];
        foreach (Files::scanDir(App::config()->backupRoot()) as $v) {
            if (preg_match('/backup-([0-9A-Za-z\.-]+).zip/', $v)) {
                $archives[] = $v;
            }
        }
        if (!empty($archives)) {
            usort($archives, fn ($a, $b) => $a <=> $b);
        }

        self::$archives = $archives;

        return self::status(true);
    }

    public static function process(): bool
    {
        # Revert or delete backup file
        if (!empty($_POST['backup_file']) && in_array($_POST['backup_file'], self::$archives)) {
            $b_file = $_POST['backup_file'];

            try {
                if (!empty($_POST['b_del'])) {
                    if (!@unlink(App::config()->backupRoot() . '/' . $b_file)) {
                        throw new Exception(sprintf(__('Unable to delete file %s'), Html::escapeHTML($b_file)));
                    }
                    Notices::addSuccessNotice(__('Backup deleted.'));
                    App::upgrade()->url()->redirect('upgrade.backup');
                }

                if (!empty($_POST['b_revert'])) {
                    $zip = new Unzip(App::config()->backupRoot() . '/' . $b_file);
                    $zip->unzipAll(App::config()->backupRoot() . '/');
                    @unlink(App::config()->backupRoot() . '/' . $b_file);
                    Notices::addSuccessNotice(__('Backup restored.'));
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
        if (empty(self::$archives)) {
            $items[] = (new Text('p', __('There are no backups available.')))
                ->class('info');
        } else {
            $archives = self::$archives;
            $items[]  = (new Text('h3', __('Update backup files')));
            $items[]  = (new Text('p', __('The following files are backups of previously updates. You can revert your previous installation or delete theses files.')));

            echo
            '<form action="' . App::upgrade()->url()->get('upgrade.backup') . '" method="post">';

            $options = [];
            $i       = 0;
            foreach ($archives as $archive) {
                $i++;
                $options[] = (new Para())
                    ->items([
                        (new Radio(['backup_file', 'backup_file' . $i]))
                            ->value(Html::escapeHTML($archive)),
                        (new Label(Html::escapeHTML($archive), Label::OUTSIDE_LABEL_AFTER, 'backup_file' . $i))
                            ->class('classic'),
                    ]);
            }

            $items[] = (new Form('bck'))
                ->method('post')
                ->action(App::upgrade()->url()->get('upgrade.backup'))
                ->fields([
                    ...$options,
                    (new Para())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', __('Please note that reverting your Dotclear version may have some unwanted side-effects. Consider reverting only if you experience strong issues with this new version.'))),
                            (new Text('', sprintf(__('You should not revert to version prior to last one (%s).'), end($archives)))),
                        ]),
                    (new Para())
                        ->separator(' ')
                        ->items([
                            App::nonce()->formNonce(),
                            (new Submit(['b_del'], __('Delete selected file')))
                                ->class('delete'),
                            (new Submit(['b_revert'], __('Revert to selected file'))),
                        ]),
                ]);
        }

        Page::open(
            __('Backups'),
            '',
            Page::breadcrumb(
                [
                    __('Dotclear update')     => '',
                    __('Backups and restore') => '',
                ]
            )
        );

        echo (new Div())->items($items)->render();

        Page::helpBlock('core_backup');
        Page::close();
    }
}
