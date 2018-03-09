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

if (!defined('DC_RC_PATH')) {return;}

$core->addBehavior('xmlrpcGetPostInfo', array('tagsXMLRPCbehaviors', 'getPostInfo'));
$core->addBehavior('xmlrpcAfterNewPost', array('tagsXMLRPCbehaviors', 'editPost'));
$core->addBehavior('xmlrpcAfterEditPost', array('tagsXMLRPCbehaviors', 'editPost'));

class tagsXMLRPCbehaviors
{
    public static function getPostInfo($x, $type, $res)
    {
        $res = &$res[0];

        $rs = $x->core->meta->getMetadata(array(
            'meta_type' => 'tag',
            'post_id'   => $res['postid']));

        $m = array();
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
            $x->core->meta->delPostMeta($post_id, 'tag');

            foreach ($x->core->meta->splitMetaValues($struct['mt_keywords']) as $m) {
                $x->core->meta->setPostMeta($post_id, 'tag', $m);
            }
        }
    }
}
