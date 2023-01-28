<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class publicPages
{
    /**
     * Public init
     */
    public static function publicPrepend(): void
    {
        // Localized string we find in template
        __('Published on');
        __('This page\'s comments feed');
    }

    /**
     * Add pages to search engine
     *
     * @param      ArrayObject  $params  The parameters
     */
    public static function coreBlogBeforeGetPosts(ArrayObject $params): void
    {
        if (dcCore::app()->url->type === 'search') {
            // Add page post type for searching
            if (isset($params['post_type'])) {
                if (!is_array($params['post_type'])) {
                    // Convert it in array
                    $params['post_type'] = [$params['post_type']];
                }
                if (!in_array('page', $params['post_type'])) {
                    // Add page post type
                    $params['post_type'][] = 'page';
                }
            } else {
                // Dont miss default post type (aka post)
                $params['post_type'] = ['post', 'page'];
            }
        }
    }
}
