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
use Dotclear\Core\Upgrade\Upgrade;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Form,
    Label,
    Para,
    Select,
    Submit,
    Text
};
use Exception;

/**
 * @brief   Cache cleaner helper.
 *
 * @since   2.29
 */
class Replay extends Process
{
    /**
     * List of version having growup actions.
     *
     * @var     array<string, string> $versions
     */
    private static array $versions = [];

    public static function init(): bool
    {
        Page::checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        $versions = [];
        foreach (array_reverse(Upgrade::getGrowUpVersions()) as $version) {
            $versions[$version['version']] = $version['version'];
        }
        self::$versions = $versions;

        if (!empty($_POST['replay_version'])) {
            try {
                Upgrade::growUp($_POST['replay_version']);

                Notices::addSuccessNotice(sprintf(__('Grow up from version %s successfully replayed.'), $_POST['replay_version']));
                App::upgrade()->url()->redirect('upgrade.replay');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            __('Replay'),
            '',
            Page::breadcrumb(
                [
                    __('Dotclear update')       => '',
                    __('Replay update actions') => '',
                ]
            )
        );

        echo (new Form('replay'))
            ->action(App::upgrade()->url()->get('upgrade.replay'))
            ->method('post')
            ->fields([
                (new Text('h3', __('Replay update actions'))),
                (new Text('p', __('If some files remain from last update, you can try to replay update action from a given version.'))),
                (new Para())
                    ->items([
                        App::nonce()->formNonce(),
                        (new Label(__('Replay grow up action from version:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('replay_version'),
                        (new Select('replay_version'))
                            ->items(self::$versions)
                            ->default(''),
                        (new Submit(['submit'], __('Replay'))),
                    ]),

            ])
            ->render();

        Page::close();
    }
}
