<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Interface\Core\BlogSettingsInterface;

/**
 * @brief   The module backend behaviors.
 * @ingroup antispam
 */
class BackendBehaviors
{
    /**
     * Add an antispam help ID if necessary.
     *
     * @param   ArrayObject<string, mixed>     $blocks     The blocks
     */
    public static function adminPageHelpBlock(ArrayObject $blocks): void
    {
        if (in_array('core_comments', $blocks->getArrayCopy(), true)) {
            $blocks->append('antispam_comments');
        }
    }

    /**
     * Display information about spam deletion.
     */
    public static function adminCommentsSpamForm(): string
    {
        $ttl = (int) My::settings()->antispam_moderation_ttl;
        if ($ttl >= 0) {
            echo (new Para())
                ->items([
                    (new Text(
                        null,
                        sprintf(
                            __('All spam comments older than %s day(s) will be automatically deleted.'),
                            $ttl
                        ) . ' ' . sprintf(
                            __('You can modify this duration in the %s.'),
                            (new Link())
                                ->href(App::backend()->url()->get('admin.blog.pref') . '#antispam_moderation_ttl')
                                ->text(__('Blog settings'))
                            ->render()
                        )
                    )),
                ])
            ->render();
        }

        return '';
    }

    /**
     * Display fieldset for spam deletion setting.
     *
     * @param   BlogSettingsInterface   $settings   The settings
     */
    public static function adminBlogPreferencesForm(BlogSettingsInterface $settings): void
    {
        echo (new Fieldset('antispam_params'))
            ->legend((new Legend('Antispam')))
            ->items([
                (new Para())->items([
                    (new Number('antispam_moderation_ttl', -1, 999, (int) $settings->antispam->antispam_moderation_ttl))
                        ->default(-1)
                        ->label((new Label(__('Delete junk comments older than'), Label::INSIDE_TEXT_BEFORE))->suffix(__('days'))),
                ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('Set -1 to disabled this feature ; recommended delay is 7 days.')),
                (new Para())->items([
                    (new Link())
                        ->href(My::manageUrl())
                        ->text(__('Set spam filters.')),
                ]),
            ])
        ->render();
    }

    /**
     * Save the spam deletion setting.
     *
     * @param   BlogSettingsInterface   $settings   The settings
     */
    public static function adminBeforeBlogSettingsUpdate(BlogSettingsInterface $settings): void
    {
        $settings->antispam->put('antispam_moderation_ttl', (int) $_POST['antispam_moderation_ttl']);
    }
}
