<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\breadcrumb;

use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Dotclear\Interface\Core\BlogWorkspaceInterface;

/**
 * @brief   The module backend behaviors.
 * @ingroup breadcrumb
 */
class BackendBehaviors
{
    /**
     * Display breadcrumb fieldset settings.
     *
     * @param   BlogSettingsInterface   $settings   The settings
     */
    public static function adminBlogPreferencesForm(BlogSettingsInterface $settings): void
    {
        $enabled = is_bool($enabled = $settings->breadcrumb->breadcrumb_enabled) && $enabled;
        $alone   = is_bool($alone = $settings->breadcrumb->breadcrumb_alone)     && $alone;
        $home    = is_string($home = $settings->breadcrumb->breadcrumb_home) ? $home : '';

        echo (new Fieldset('breadcrumb_params'))
            ->legend(new Legend(My::name()))
            ->fields([
                (new Para())
                    ->items([
                        (new Checkbox('breadcrumb_enabled', $enabled))
                            ->value(1)
                            ->label((new Label(__('Enable breadcrumb for this blog'), Label::INSIDE_TEXT_AFTER))),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('The {{tpl:Breadcrumb [separator=" &amp;rsaquo; "]}} tag should be present (or inserted if not) in the template.')),
                (new Para())
                    ->items([
                        (new Input('breadcrumb_home'))
                            ->value($home)
                            ->label((new Label(__('Link label to the home page:')))),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(__('Leave empty to use default label: %s.'), __('Home'))),
                (new Para())
                    ->items([
                        (new Checkbox('breadcrumb_alone', $alone))
                            ->value(1)
                            ->label((new Label(__('Do not encapsulate breadcrumb in a paragraph HTML tag.'), Label::INSIDE_TEXT_AFTER))),
                    ]),
            ])
        ->render();
    }

    /**
     * Save breadcrumb settings.
     *
     * @param   BlogSettingsInterface   $settings   The settings
     */
    public static function adminBeforeBlogSettingsUpdate(BlogSettingsInterface $settings): void
    {
        $home = isset($_POST['breadcrumb_home']) && is_string($home = $_POST['breadcrumb_home']) ? $home : '';

        $settings->breadcrumb->put('breadcrumb_enabled', !empty($_POST['breadcrumb_enabled']), BlogWorkspaceInterface::NS_BOOL);
        $settings->breadcrumb->put('breadcrumb_alone', !empty($_POST['breadcrumb_alone']), BlogWorkspaceInterface::NS_BOOL);
        $settings->breadcrumb->put('breadcrumb_home', $home, BlogWorkspaceInterface::NS_STRING);
    }
}
