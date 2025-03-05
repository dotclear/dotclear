<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module backend manage tags process.
 * @ingroup tags
 */
class Manage extends Process
{
    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(($_REQUEST['m'] ?? 'tags') === 'tag_posts' ? ManagePosts::init() : true);
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (($_REQUEST['m'] ?? 'tags') === 'tag_posts') {
            return ManagePosts::process();
        }

        App::backend()->tags = App::meta()->getMetadata(['meta_type' => 'tag', 'post_type' => '']);
        App::backend()->tags = App::meta()->computeMetaStats(App::backend()->tags);
        App::backend()->tags->lexicalSort('meta_id_lower', 'asc');

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (($_REQUEST['m'] ?? 'tags') === 'tag_posts') {
            ManagePosts::render();

            return;
        }

        Page::openModule(
            My::name(),
            My::cssLoad('style')
        );

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => '',
            ]
        ) .
        Notices::getNotices();

        $current_letter = null;
        $colonnes       = [[], []];
        $colonne        = 0;
        while (App::backend()->tags->fetch()) {
            $letter = mb_strtoupper(mb_substr((string) App::backend()->tags->meta_id_lower, 0, 1));
            if ($current_letter !== $letter) {
                if (App::backend()->tags->index() >= round(App::backend()->tags->count() / 2)) {
                    $colonne = 1;
                }
                $colonnes[$colonne][] = (new Tr())
                    ->class('tagLetter')
                    ->items([
                        (new Td())
                            ->colspan(2)
                            ->items([
                                (new Text('span', $letter)),
                            ]),
                    ]);
            }

            $colonnes[$colonne][] = (new Tr())->class('line')->items([
                (new Td())
                    ->class('maximal')
                    ->items([
                        (new Link())
                            ->href(App::backend()->getPageURL() . '&m=tag_posts&tag=' . rawurlencode((string) App::backend()->tags->meta_id))->text(App::backend()->tags->meta_id),
                    ]),
                (new Td())
                    ->class(['nowrap', 'count'])
                    ->separator(' ')
                    ->items([
                        (new Text('strong', App::backend()->tags->count)),
                        (new Text(null, (int) App::backend()->tags->count === 1 ? __('entry') : __('entries'))),
                    ]),
            ]);

            $current_letter = $letter;
        }

        if ($colonnes[0]) {
            echo (new Div())
                ->class('two-cols')
                ->items([
                    (new Div())
                        ->class('col')
                        ->items([
                            (new Table())
                                ->class('tags')
                                ->items($colonnes[0]),
                        ]),
                    $colonnes[1] ?
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Table())
                                    ->class('tags')
                                    ->items($colonnes[1]),
                            ]) :
                        (new None()),
                ])
            ->render();
        } else {
            echo (new Note())
                ->text(__('No tags on this blog.'))
            ->render();
        }

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
