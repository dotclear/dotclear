<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

$core->addBehavior('xmlrpcGetPostInfo',array('tagsXMLRPCbehaviors','getPostInfo'));
$core->addBehavior('xmlrpcAfterNewPost',array('tagsXMLRPCbehaviors','editPost'));
$core->addBehavior('xmlrpcAfterEditPost',array('tagsXMLRPCbehaviors','editPost'));

class tagsXMLRPCbehaviors
{
	public static function getPostInfo($x,$type,$res)
	{
		$res =& $res[0];
		
		$rs = $x->core->meta->getMetadata(array(
			'meta_type' => 'tag',
			'post_id' => $res['postid']));
		
		$m = array();
		while($rs->fetch()) {
			$m[] = $rs->meta_id;
		}
		
		$res['mt_keywords'] = implode(', ',$m);
	}
	
	# Same function for newPost and editPost
	public static function editPost($x,$post_id,$cur,$content,$struct,$publish)
	{
		# Check if we have mt_keywords in struct
		if (isset($struct['mt_keywords']))
		{
			$x->core->meta->delPostMeta($post_id,'tag');
			
			foreach ($x->core->meta->splitMetaValues($struct['mt_keywords']) as $m) {
				$x->core->meta->setPostMeta($post_id,'tag',$m);
			}
		}
	}
}
?>