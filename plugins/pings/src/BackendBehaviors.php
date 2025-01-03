<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module backend behaviors.
 * @ingroup pings
 */
class BackendBehaviors
{
    /**
     * Add attachment fieldset in entry sidebar.
     *
     * @param   ArrayObject<string, mixed>     $main       The main part of the entry form
     * @param   ArrayObject<string, mixed>     $sidebar    The sidebar part of the entry form
     */
    public static function pingsFormItems(ArrayObject $main, ArrayObject $sidebar): void
    {
        if (!My::settings()->pings_active) {
            return;
        }

        $pings_uris = My::settings()->pings_uris;
        if (empty($pings_uris) || !is_array($pings_uris)) {
            return;
        }

        $pings_do = !empty($_POST['pings_do']) && is_array($_POST['pings_do']) ? $_POST['pings_do'] : [];

        $index    = 0;
        $services = [];
        foreach ($pings_uris as $name => $uri) {
            $services[] = (new Para())
                ->class('ping-services')
                ->items([
                    (new Checkbox(['pings_do[]', 'pings_do-' . $index], in_array($uri, $pings_do)))
                        ->value(Html::escapeHTML($uri))
                        ->class('check-ping-services')
                        ->label(new Label(Html::escapeHTML((string) $name), Label::IL_FT)),
                ]);
            $index++;
        }

        $div = (new Div())
            ->items([
                (new Text('h5', __('Pings')))->class('ping-services'),
                ...$services,
            ])
        ->render();

        $sidebar['options-box']['items']['pings'] = $div;
    }

    /**
     * Do pings.
     */
    public static function doPings(): void
    {
        if (empty($_POST['pings_do']) || !is_array($_POST['pings_do'])) {
            return;
        }

        if (!My::settings()->pings_active) {
            return;
        }

        $pings_uris = My::settings()->pings_uris;
        if (empty($pings_uris) || !is_array($pings_uris)) {
            return;
        }

        foreach ($_POST['pings_do'] as $uri) {
            if (in_array($uri, $pings_uris)) {
                try {
                    PingsAPI::doPings($uri, App::blog()->name(), App::blog()->url());
                } catch (Exception) {
                }
            }
        }
    }
}
