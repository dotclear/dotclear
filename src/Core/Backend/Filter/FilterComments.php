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

/**
 * @brief   Comments list filters form helper
 *
 * @since   2.20
 */
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

        $this->add($filters);   // @phpstan-ignore-line
    }

    /**
     * Comment author select.
     *
     * @return  Filter  The comment author Filter instance.
     */
    public function getCommentAuthorFilter(): Filter
    {
        return (new Filter('author'))
            ->param('q_author')
            ->form('input')
            ->title(__('Author:'));
    }

    /**
     * Comment type select.
     *
     * @return  Filter  The comment type Filter instance.
     */
    public function getCommentTypeFilter(): Filter
    {
        return (new Filter('type'))
            ->param('comment_trackback', fn ($f): bool => $f[0] == 'tb')
            ->title(__('Type:'))
            ->options([
                '-'             => '',
                __('Comment')   => 'co',
                __('Trackback') => 'tb',
            ])
            ->prime(true);
    }

    /**
     * Comment status select.
     *
     * @return  Filter  The comment status Filter instance.
     */
    public function getCommentStatusFilter(): Filter
    {
        return (new Filter('status'))
            ->param('comment_status')
            ->title(__('Status:'))
            ->options(['-' => '', ...Combos::getCommentStatusesCombo()])
            ->prime(true);
    }

    /**
     * Common IP field.
     *
     * @return  Filter  The commnet IP Filter instance.
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
