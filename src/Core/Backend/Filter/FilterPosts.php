<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Posts list filters form helper.
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use dcCore;
use dcUtils;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Core;
use Dotclear\Helper\Html\Html;
use Exception;

class FilterPosts extends Filters
{
    protected $post_type = 'post';

    public function __construct(string $type = 'posts', string $post_type = '')
    {
        parent::__construct($type);

        if (Core::postTypes()->exists($post_type)) {
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
        Core::behavior()->callBehavior('adminPostFilterV2', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Posts users select
     */
    public function getPostUserFilter(): ?Filter
    {
        $users = null;

        try {
            $users = Core::blog()->getPostsUsers($this->post_type);
            if ($users->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return null;
        }

        $combo = Combos::getUsersCombo($users);
        dcUtils::lexicalKeySort($combo, dcUtils::ADMIN_LOCALE);

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
     * Posts categories select
     */
    public function getPostCategoriesFilter(): ?Filter
    {
        $categories = null;

        try {
            $categories = Core::blog()->getCategories(['post_type' => $this->post_type]);
            if ($categories->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return null;
        }

        $combo = [
            '-'            => '',
            __('(No cat)') => 'NULL',
        ];
        while ($categories->fetch()) {
            $combo[
                str_repeat('&nbsp;', ($categories->level - 1) * 4) .
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
     * Posts status select
     */
    public function getPostStatusFilter(): Filter
    {
        return (new Filter('status'))
            ->param('post_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getPostStatusesCombo()
            ))
            ->prime(true);
    }

    /**
     * Posts format select
     */
    public function getPostFormatFilter(): Filter
    {
        $core_formaters    = Core::formater()->getFormaters();
        $available_formats = [];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[Core::formater()->getFormaterName($format)] = $format;
            }
        }

        return (new Filter('format'))
            ->param('where', fn ($f) => " AND post_format = '" . $f[0] . "' ")
            ->title(__('Format:'))
            ->options(array_merge(
                ['-' => ''],
                $available_formats
            ))
            ->prime(true);
    }

    /**
     * Posts password state select
     */
    public function getPostPasswordFilter(): Filter
    {
        return (new Filter('password'))
            ->param('where', fn ($f) => ' AND post_password IS ' . ($f[0] ? 'NOT ' : '') . 'NULL ')
            ->title(__('Password:'))
            ->options([
                '-'                    => '',
                __('With password')    => '1',
                __('Without password') => '0',
            ])
            ->prime(true);
    }

    /**
     * Posts selected state select
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
     * Posts attachment state select
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
     * Posts by month select
     */
    public function getPostMonthFilter(): ?Filter
    {
        $dates = null;

        try {
            $dates = Core::blog()->getDates([
                'type'      => 'month',
                'post_type' => $this->post_type,
            ]);
            if ($dates->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return null;
        }

        return (new Filter('month'))
            ->param('post_month', fn ($f) => substr($f[0], 4, 2))
            ->param('post_year', fn ($f) => substr($f[0], 0, 4))
            ->title(__('Month:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getDatesCombo($dates)
            ));
    }

    /**
     * Posts lang select
     */
    public function getPostLangFilter(): ?Filter
    {
        $langs = null;

        try {
            $langs = Core::blog()->getLangs(['post_type' => $this->post_type]);
            if ($langs->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return null;
        }

        return (new Filter('lang'))
            ->param('post_lang')
            ->title(__('Lang:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getLangsCombo($langs, false)
            ));
    }

    /**
     * Posts comments state select
     */
    public function getPostCommentFilter(): Filter
    {
        return (new Filter('comment'))
            ->param('where', fn ($f) => " AND post_open_comment = '" . $f[0] . "' ")
            ->title(__('Comments:'))
            ->options([
                '-'          => '',
                __('Opened') => '1',
                __('Closed') => '0',
            ]);
    }

    /**
     * Posts trackbacks state select
     */
    public function getPostTrackbackFilter(): Filter
    {
        return (new Filter('trackback'))
            ->param('where', fn ($f) => " AND post_open_tb = '" . $f[0] . "' ")
            ->title(__('Trackbacks:'))
            ->options([
                '-'          => '',
                __('Opened') => '1',
                __('Closed') => '0',
            ]);
    }
}
