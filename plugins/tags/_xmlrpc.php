<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

dcCore::app()->addBehavior('xmlrpcGetPostInfo', ['tagsXMLRPCbehaviors', 'getPostInfo']);
dcCore::app()->addBehavior('xmlrpcAfterNewPost', ['tagsXMLRPCbehaviors', 'editPost']);
dcCore::app()->addBehavior('xmlrpcAfterEditPost', ['tagsXMLRPCbehaviors', 'editPost']);

class tagsXMLRPCbehaviors
{
    public static function getPostInfo($x, $type, $res)
    {
        $res = &$res[0];

        $rs = dcCore::app()->meta->getMetadata([
            'meta_type' => 'tag',
            'post_id'   => $res['postid'], ]);

        $m = [];
        while ($rs->fetch()) {
            $m[] = $rs->meta_id;
        }

        $res['mt_keywords'] = implode(', ', $m);
    }

    # Same function for newPost and editPost
    public static function editPost($x, $post_id, $cur, $content, $struct, $publish)
    {
        # Check if we have mt_keywords in struct
        if (isset($struct['mt_keywords'])) {
            dcCore::app()->meta->delPostMeta($post_id, 'tag');

            foreach (dcCore::app()->meta->splitMetaValues($struct['mt_keywords']) as $m) {
                dcCore::app()->meta->setPostMeta($post_id, 'tag', $m);
            }
        }
    }
}
