<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Stack\Filter;
use Exception;

/**
 * @brief   Posts list filters form helper.
 *
 * @since   2.20
 */
class FilterPosts extends Filters
{
    /**
     * The post type.
     */
    protected string $post_type = 'post';

    public function __construct(string $type = 'posts', string $post_type = '')
    {
        parent::__construct($type);

        if (App::postTypes()->exists($post_type)) {
            $this->post_type = $post_type;
            $this->add((new Filter('post_type', $post_type))->param('post_type'));
        }

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            FiltersLibrary::getCurrentBlogFilter(),
            $this->getPostUserFilter(),
            $this->getPostCategoriesFilter(),
            $this->getPostStatusFilter(),
            $this->getPostFormatFilter(),
            $this->getPostPasswordFilter(),
            $this->getPostSelectedFilter(),
            $this->getPostAttachmentFilter(),
            $this->getPostMonthFilter(),
            $this->getPostLangFilter(),
            $this->getPostCommentFilter(),
            $this->getPostTrackbackFilter(),
        ]);

        # --BEHAVIOR-- adminPostFilter -- ArrayObject
        App::behavior()->callBehavior('adminPostFilterV2', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);   // @phpstan-ignore-line
    }

    /**
     * Posts users select.
     *
     * @return  ?Filter     The post user Filter instance if possible.
     */
    public function getPostUserFilter(): ?Filter
    {
        $users = null;

        try {
            $users = App::blog()->getPostsUsers($this->post_type);
            if ($users->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        $combo = Combos::getUsersCombo($users);
        App::lexical()->lexicalKeySort($combo, App::lexical()::ADMIN_LOCALE);

        return (new Filter('user_id'))
            ->param()
            ->title(__('Author:'))
            ->options(array_merge(
                ['-' => ''],
                $combo
            ))
            ->prime(true);
    }

    /**
     * Posts categories select.
     *
     * @return  ?Filter     The post category Filter instance if possible.
     */
    public function getPostCategoriesFilter(): ?Filter
    {
        $categories = null;

        try {
            $categories = App::blog()->getCategories(['post_type' => $this->post_type]);
            if ($categories->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        $combo = [
            '-'            => '',
            __('(No cat)') => 'NULL',
        ];
        while ($categories->fetch()) {
            $combo[
                str_repeat('&nbsp;', (int) (($categories->level - 1) * 4)) .
                Html::escapeHTML($categories->cat_title) . ' (' . $categories->nb_post . ')'
            ] = $categories->cat_id;
        }

        return (new Filter('cat_id'))
            ->param()
            ->title(__('Category:'))
            ->options($combo)
            ->prime(true);
    }

    /**
     * Posts status select.
     *
     * @return  Filter  The post status Filter instance.
     */
    public function getPostStatusFilter(): Filter
    {
        return (new Filter('status'))
            ->param('post_status')
            ->title(__('Status:'))
            ->options(['-' => '', ...Combos::getPostStatusesCombo()])
            ->prime(true);
    }

    /**
     * Posts format select.
     *
     * @return  Filter  The post format Filter instance.
     */
    public function getPostFormatFilter(): Filter
    {
        $core_formaters    = App::formater()->getFormaters();
        $available_formats = [];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[App::formater()->getFormaterName($format)] = $format;
            }
        }

        return (new Filter('format'))
            ->param('where', fn ($f): string => " AND post_format = '" . $f[0] . "' ")
            ->title(__('Format:'))
            ->options(['-' => '', ...$available_formats])
            ->prime(true);
    }

    /**
     * Posts password state select.
     *
     * @return  Filter  The post password Filter instance.
     */
    public function getPostPasswordFilter(): Filter
    {
        return (new Filter('password'))
            ->param('where', fn ($f): string => ' AND post_password IS ' . ($f[0] ? 'NOT ' : '') . 'NULL ')
            ->title(__('Password:'))
            ->options([
                '-'                    => '',
                __('With password')    => '1',
                __('Without password') => '0',
            ])
            ->prime(true);
    }

    /**
     * Posts selected state select.
     *
     * @return  Filter  The post selected Filter instance.
     */
    public function getPostSelectedFilter(): Filter
    {
        return (new Filter('selected'))
            ->param('post_selected')
            ->title(__('Selected:'))
            ->options([
                '-'                => '',
                __('Selected')     => '1',
                __('Not selected') => '0',
            ]);
    }

    /**
     * Posts attachment state select.
     *
     * @return  Filter  The post attachment Filter instance.
     */
    public function getPostAttachmentFilter(): Filter
    {
        return (new Filter('attachment'))
            ->param('media')
            ->param('link_type', 'attachment')
            ->title(__('Attachments:'))
            ->options([
                '-'                       => '',
                __('With attachments')    => '1',
                __('Without attachments') => '0',
            ]);
    }

    /**
     * Posts by month select.
     *
     * @return  ?Filter  The post month Filter instance if possible.
     */
    public function getPostMonthFilter(): ?Filter
    {
        $dates = null;

        try {
            $dates = App::blog()->getDates([
                'type'      => 'month',
                'post_type' => $this->post_type,
            ]);
            if ($dates->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        return (new Filter('month'))
            ->param('post_month', fn ($f): string => substr((string) $f[0], 4, 2))
            ->param('post_year', fn ($f): string => substr((string) $f[0], 0, 4))
            ->title(__('Month:'))
            ->options(['-' => '', ...Combos::getDatesCombo($dates)]);
    }

    /**
     * Posts lang select.
     *
     * @return  ?Filter  The post lang Filter instance if possible.
     */
    public function getPostLangFilter(): ?Filter
    {
        $langs = null;

        try {
            $langs = App::blog()->getLangs(['post_type' => $this->post_type]);
            if ($langs->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        return (new Filter('lang'))
            ->param('post_lang')
            ->title(__('Lang:'))
            ->options(['-' => '', ...Combos::getLangsCombo($langs, false)]);
    }

    /**
     * Posts comments state select.
     *
     * @return  Filter  The post comment status Filter.
     */
    public function getPostCommentFilter(): Filter
    {
        return (new Filter('comment'))
            ->param('where', fn ($f): string => " AND post_open_comment = '" . $f[0] . "' ")
            ->title(__('Comments:'))
            ->options([
                '-'          => '',
                __('Opened') => '1',
                __('Closed') => '0',
            ]);
    }

    /**
     * Posts trackbacks state select.
     *
     * @return  Filter  The post trackback status Filter.
     */
    public function getPostTrackbackFilter(): Filter
    {
        return (new Filter('trackback'))
            ->param('where', fn ($f): string => " AND post_open_tb = '" . $f[0] . "' ")
            ->title(__('Trackbacks:'))
            ->options([
                '-'          => '',
                __('Opened') => '1',
                __('Closed') => '0',
            ]);
    }
}
