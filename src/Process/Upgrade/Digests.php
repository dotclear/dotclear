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
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Label,
    Link,
    Note,
    Para,
    Submit,
    Text
};
use Dotclear\Helper\L10n;
use Exception;

/**
 * @brief   Upgrade process corrupted files helper.
 *
 * Taken from plugin fakemeup.
 *
 * @author  Franck Paul and contributors
 *
 * @since   2.29
 */
class Digests extends Process
{
    private static string $path_backup;
    private static string $path_helpus;
    private static string $path_disclaimer;
    private static string $zip_name = '';

    /**
     * List of changes.
     *
     * @var     array<string, array<string, mixed>>    $changes
     */
    private static array $changes = [
        'same'    => [],
        'changed' => [],
        'removed' => [],
    ];

    public static function init(): bool
    {
        Page::checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        self::$path_backup = implode(DIRECTORY_SEPARATOR, [App::config()->dotclearRoot(), 'inc', 'digests.bak']);
        self::$path_helpus = (string) (L10n::getFilePath(App::config()->l10nRoot(), 'help/core_fmu_helpus.html', App::lang()->getLang()) ?:
            L10n::getFilePath(App::config()->l10nRoot(), 'help/core_fmu_helpus.html', 'en'));
        self::$path_disclaimer = (string) (L10n::getFilePath(App::config()->l10nRoot(), 'help/core_fmu_disclaimer.html', App::lang()->getLang()) ?:
            L10n::getFilePath(App::config()->l10nRoot(), 'help/core_fmu_disclaimer.html', 'en'));

        if (isset($_POST['erase_backup']) && is_file(self::$path_backup)) {
            @unlink(self::$path_backup);
        }

        try {
            if (isset($_POST['override'])) {
                $changes = self::check(App::config()->dotclearRoot(), App::config()->digestsRoot());
                $arr     = $changes['same'];
                foreach ($changes['changed'] as $k => $v) {
                    $arr[$k] = $v['new'];
                }
                ksort($arr);
                self::$changes = $changes;

                $digest = '';
                foreach ($arr as $k => $v) {
                    $digest .= sprintf("%s  %s\n", $v, $k);
                }
                rename(App::config()->digestsRoot(), self::$path_backup);
                file_put_contents(App::config()->digestsRoot(), $digest);
                self::$zip_name = self::backup(self::$changes);
            } elseif (isset($_POST['disclaimer_ok'])) {
                self::$changes = self::check(App::config()->dotclearRoot(), App::config()->digestsRoot());
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        // Mesasges
        if (isset($_POST['override'])) {
            if (empty(self::$zip_name)) {
                Notices::addSuccessNotice(__('The updates have been performed.'));
            }
        } elseif (isset($_POST['disclaimer_ok'])) {
            if (count(self::$changes['changed']) == 0 && count(self::$changes['removed']) == 0) {
                Notices::addWarningNotice(__('No changed filed have been found, nothing to do!'));
            }
        } elseif (file_exists(self::$path_backup)) {
            Notices::addErrorNotice(__('This tool has already been run once.'));
        }

        return true;
    }

    public static function render(): void
    {
        if (!empty($_GET['download']) && preg_match('/^fmu_backup_[0-9]{14}.zip$/', (string) $_GET['download'])) {
            $f = App::config()->varRoot() . DIRECTORY_SEPARATOR . $_GET['download'];
            if (is_file($f)) {
                $c = (string) file_get_contents($f);
                header('Content-Disposition: attachment;filename=' . $_GET['download']);
                header('Content-Type: application/x-zip');
                header('Content-Length: ' . strlen($c));
                echo $c;
                exit;
            }
        }

        Page::open(
            __('Files'),
            '',
            Page::breadcrumb(
                [
                    __('Dotclear update') => '',
                    __('Corrupted files') => '',
                ]
            )
        );

        if (App::error()->flag()) {
            Page::close();

            return;
        }

        echo (new Div())
            ->items([
                (new Note())
                    ->class('static-msg')
                    ->text(__('On this page, you can bypass corrupted files or modified files in order to perform update.')),
            ])
            ->render();

        if (isset($_POST['override'])) {
            $item = empty(self::$zip_name) ? (new Text()) : (new Text(
                null,
                is_file(self::$path_helpus) ?
                sprintf((string) file_get_contents(self::$path_helpus), App::upgrade()->url()->get('upgrade.digests', ['download' => self::$zip_name]), self::$zip_name, 'fakemeup@dotclear.org') :
                '<a href="' . App::upgrade()->url()->get('upgrade.digests', ['download' => self::$zip_name]) . '">' . __('Download backup of digests file.') . '</a>'
            ));

            echo (new Div())
                ->class('fieldset')
                ->items([
                    $item,
                    (new Para())->items([
                        (new Link())
                            ->class('button submit')
                            ->href(App::upgrade()->url()->get('upgrade.upgrade'))
                            ->text(__('Update Dotclear')),
                    ]),
                ])
            ->render();
        } elseif (isset($_POST['disclaimer_ok'])) {
            if (count(self::$changes['changed']) == 0 && count(self::$changes['removed']) == 0) {
                echo (new Div())
                    ->class('fieldset')
                    ->items([
                        (new Text('p', __('Digests file is up to date.'))),
                        (new Link())
                            ->class('button submit')
                            ->href(App::upgrade()->url()->get('upgrade.upgrade'))
                            ->text(__('Update Dotclear')),
                    ])
                    ->render();
            } else {
                $changed       = [];
                $block_changed = '';
                if (count(self::$changes['changed']) != 0) {
                    foreach (self::$changes['changed'] as $k => $v) {
                        $changed[] = (new Text('li', sprintf('%s [old:%s, new:%s]', $k, $v['old'], $v['new'])));
                    }
                    $block_changed = (new Div())
                        ->items([
                            (new Para())
                                ->items([
                                    (new Text(null, __('The following files will have their checksum faked:'))),
                                ]),
                            (new Para(null, 'ul'))
                                ->items($changed),
                        ])
                        ->render();
                }
                $removed       = [];
                $block_removed = '';
                if (count(self::$changes['removed']) != 0) {
                    foreach (self::$changes['removed'] as $k => $v) {
                        $removed[] = (new Text('li', (string) $k));
                    }
                    $block_removed = (new Div())
                        ->items([
                            (new Para())
                                ->items([
                                    (new Text(null, __('The following files digests will have their checksum cleaned:'))),
                                ]),
                            (new Para(null, 'ul'))
                                ->items($removed),
                        ])
                        ->render();
                }

                echo (new Form('frm-override'))
                    ->class('fieldset')
                    ->action(App::upgrade()->url()->get('upgrade.digests'))
                    ->method('post')
                    ->fields([
                        (new Text(null, $block_changed)),
                        (new Text(null, $block_removed)),
                        (new Submit(['confirm'], __('Still ok to continue'))),
                        (new Hidden(['override'], (string) 1)),
                        App::nonce()->formNonce(),
                    ])
                    ->render();
            }
        } else {
            if (file_exists(self::$path_backup)) {
                echo (new Form('frm-erase'))
                    ->class('fieldset')
                    ->action(App::upgrade()->url()->get('upgrade.digests'))
                    ->method('post')
                    ->fields([
                        (new Para())
                            ->items([
                                (new Checkbox('erase_backup'))
                                    ->value(1)
                                    ->label((new Label(__('Remove the backup digest file, I want to play again'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Submit(['confirm'], __('Continue'))),
                                App::nonce()->formNonce(),
                            ]),
                    ])
                ->render();
            } else {
                echo (new Form('frm-disclaimer'))
                    ->class('fieldset')
                    ->action(App::upgrade()->url()->get('upgrade.digests'))
                    ->method('post')
                    ->fields([
                        (new Div())
                            ->items([(new Text(null, is_file(self::$path_disclaimer) ? (string) file_get_contents(self::$path_disclaimer) : '...'))]),
                        (new Para())
                            ->items([
                                (new Checkbox('disclaimer_ok'))
                                    ->value(1)
                                    ->label((new Label(__('I have read and understood the disclaimer and wish to continue anyway.'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                        (new Para())
                            ->items([
                                (new Submit(['confirm'], __('Continue'))),
                                App::nonce()->formNonce(),
                            ]),
                    ])
                    ->render();
            }
        }

        Page::close();
    }

    /**
     * Check digest file.
     *
     * @param   string  $root           The root
     * @param   string  $digests_file   The digests file
     *
     * @throws  Exception
     *
     * @return  array<string, array<string, mixed>>
     */
    private static function check(string $root, string $digests_file): array
    {
        if (!is_readable($digests_file)) {
            throw new Exception(__('Unable to read digests file.'));
        }

        $opts     = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
        $contents = file($digests_file, $opts);
        if (!$contents) {
            return [
                'same'    => [],
                'changed' => [],
                'removed' => [],
            ];
        }

        $changed = [];
        $same    = [];
        $removed = [];

        foreach ($contents as $digest) {
            if (!preg_match('#^([\da-f]{32})\s+(.+?)$#', $digest, $m)) {
                continue;
            }

            $md5      = $m[1];
            $filename = $root . '/' . $m[2];

            # Invalid checksum
            if (is_readable($filename)) {
                $md5_new = md5_file($filename);
                if ($md5 == $md5_new) {
                    $same[$m[2]] = $md5;
                } else {
                    $changed[$m[2]] = ['old' => $md5,'new' => $md5_new];
                }
            } else {
                $removed[$m[2]] = true;
            }
        }

        # No checksum found in digests file
        if (empty($md5)) {
            throw new Exception(__('Invalid digests file.'));
        }

        return [
            'same'    => $same,
            'changed' => $changed,
            'removed' => $removed,
        ];
    }

    /**
     * Backup digest.
     *
     * @param   array<string, array<string, mixed>>     $changes    The changes
     *
     * @return  string  False on error, zip name on success
     */
    private static function backup(array $changes)
    {
        $zip_name      = sprintf('fmu_backup_%s.zip', date('YmdHis'));
        $zip_file      = sprintf('%s/%s', App::config()->varRoot(), $zip_name);
        $checksum_file = sprintf('%s/fmu_checksum_%s.txt', App::config()->varRoot(), date('Ymd'));

        $c_data = 'Fake Me Up Checksum file - ' . date('d/m/Y H:i:s') . "\n\n" .
            'Dotclear version : ' . App::config()->dotclearVersion() . "\n\n";
        if (count($changes['removed'])) {
            $c_data .= "== Removed files ==\n";
            foreach ($changes['removed'] as $k => $v) {
                $c_data .= sprintf(" * %s\n", $k);
            }
            $c_data .= "\n";
        }
        if (file_exists($zip_file)) {
            @unlink($zip_file);
        }

        $b_fp = @fopen($zip_file, 'wb');
        if ($b_fp === false) {
            return '';
        }
        $b_zip = new Zip($b_fp);

        if (count($changes['changed'])) {
            $c_data .= "== Invalid checksum files ==\n";
            foreach ($changes['changed'] as $k => $v) {
                $name = substr($k, 2);
                $c_data .= sprintf(" * %s [expected: %s ; current: %s]\n", $k, $v['old'], $v['new']);

                try {
                    $b_zip->addFile(App::config()->dotclearRoot() . '/' . $name, $name);
                } catch (Exception $e) {
                    $c_data .= $e->getMessage();
                }
            }
        }
        file_put_contents($checksum_file, $c_data);
        $b_zip->addFile($checksum_file, basename($checksum_file));

        $b_zip->write();
        fclose($b_fp);
        $b_zip->close();

        @unlink($checksum_file);

        return $zip_name;
    }
}
