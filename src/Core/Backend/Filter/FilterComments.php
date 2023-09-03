<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Comments list filters form helper
 *
 * @since 2.20
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Filter;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;

class FilterComments extends Filters
{
    public function __construct()
    {
        parent::__construct('comments');

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            FiltersLibrary::getCurrentBlogFilter(),
            $this->getCommentAuthorFilter(),
            $this->getCommentTypeFilter(),
            $this->getCommentStatusFilter(),
            $this->getCommentIpFilter(),
            FiltersLibrary::getInputFilter('email', __('Email:'), 'comment_email'),
            FiltersLibrary::getInputFilter('site', __('Web site:'), 'comment_site'),
        ]);

        # --BEHAVIOR-- adminCommentFilter -- ArrayObject
        App::behavior()->callBehavior('adminCommentFilterV2', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Comment author select
     */
    public function getCommentAuthorFilter(): Filter
    {
        return (new Filter('author'))
            ->param('q_author')
            ->form('input')
            ->title(__('Author:'));
    }

    /**
     * Comment type select
     */
    public function getCommentTypeFilter(): Filter
    {
        return (new Filter('type'))
            ->param('comment_trackback', fn ($f) => $f[0] == 'tb')
            ->title(__('Type:'))
            ->options([
                '-'             => '',
                __('Comment')   => 'co',
                __('Trackback') => 'tb',
            ])
            ->prime(true);
    }

    /**
     * Comment status select
     */
    public function getCommentStatusFilter(): Filter
    {
        return (new Filter('status'))
            ->param('comment_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getCommentStatusesCombo()
            ))
            ->prime(true);
    }

    /**
     * Common IP field
     */
    public function getCommentIpFilter(): ?Filter
    {
        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            return null;
        }

        return (new Filter('ip'))
            ->param('comment_ip')
            ->form('input')
            ->title(__('IP address:'));
    }
}
