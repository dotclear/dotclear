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
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   Growup replay helper.
 *
 * @since   2.29
 */
class Replay
{
    use TraitProcess;

    /**
     * List of version having growup actions.
     *
     * @var     Option[] $versions
     */
    private static array $versions = [];

    public static function init(): bool
    {
        App::upgrade()->page()->checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        $versions = [];
        foreach (array_reverse(App::upgrade()->upgrade()->getGrowUpVersions()) as $version) {
            $versions[] = new Option($version['version'], $version['version']);
        }
        self::$versions = $versions;

        if (!empty($_POST['replay_version'])) {
            try {
                App::upgrade()->upgrade()->growUp($_POST['replay_version']);

                App::upgrade()->notices()->addSuccessNotice(sprintf(__('Grow up from version %s successfully replayed.'), $_POST['replay_version']));
                App::upgrade()->url()->redirect('upgrade.replay');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        App::upgrade()->page()->open(
            __('Replay'),
            '',
            App::upgrade()->page()->breadcrumb(
                [
                    __('Dotclear update')       => '',
                    __('Replay update actions') => '',
                ]
            )
        );

        echo (new Div())
            ->items([
                (new Note())
                    ->class('static-msg')
                    ->text(__('On this page, you can try to replay update action from a given version if some files remain from last update.')),
                (new Form('replay'))
                    ->class('fieldset')
                    ->action(App::upgrade()->url()->get('upgrade.replay'))
                    ->method('post')
                    ->fields([
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
                        (new Note())
                            ->text(__('Replay version lower than the last one can break your installation, do it at your own risk.'))
                            ->class('warning'),
                    ]),
            ])
            ->render();

        App::upgrade()->page()->close();
    }
}
