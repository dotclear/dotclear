<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Dotclear\Core\Core;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\Widgets as dcWidgets;

class Widgets
{
    /**
     * Initializes the blogroll widget.
     *
     * @param      WidgetsStack  $widgets  The widgets
     */
    public static function initWidgets(WidgetsStack $widgets): void
    {
        $blogroll  = new Blogroll(Core::blog());
        $hierarchy = $blogroll->getLinksHierarchy($blogroll->getLinks());

        $hierarchy_cat    = array_keys($hierarchy);
        $categories_combo = [__('All categories') => ''];
        foreach ($hierarchy_cat as $category) {
            if ($category) {
                $categories_combo[$category] = $category;
            }
        }

        $widgets
            ->create('links', My::name(), [FrontendTemplate::class, 'linksWidget'], null, 'Blogroll list')
            ->addTitle(__('Links'))
            ->setting('category', __('Category'), '', 'combo', $categories_combo)
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    /**
     * Add blogroll widget to default set
     *
     * @param      WidgetsStack  $widgets          The widgets
     * @param      array      $default_widgets  The default widgets
     */
    public static function initDefaultWidgets(WidgetsStack $widgets, array $default_widgets)
    {
        $default_widgets[dcWidgets::WIDGETS_EXTRA]->append($widgets->links);
    }
}
