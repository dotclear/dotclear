<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRevisions, a plugin for Dotclear.
#
# Copyright (c) 2010 Tomtom and contributors
# http://blog.zenstyle.fr/
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

class dcRevisionsExtensions
{
	public static function getDate($rs,$format = null)
	{
		$format === null ? $format = $rs->core->blog->settings->system->date_format : $format;
		
		return dt::dt2str($format,$rs->revision_dt,$rs->revision_tz);
	}
	
	public static function getTime($rs,$format = null)
	{	
		$format === null ? $format = $rs->core->blog->settings->system->time_format : $format;

		return dt::dt2str($format,$rs->revision_dt,$rs->revision_tz);
	}
	
	public static function getAuthorCN($rs)
	{
		return dcUtils::getUserCN($rs->user_id, $rs->user_name,
		$rs->user_firstname, $rs->user_displayname);
	}
	
	public static function getAuthorLink($rs)
	{
		$res = '%1$s';
		$url = $rs->user_url;
		if ($url) {
			$res = '<a href="%2$s">%1$s</a>';
		}
		
		return sprintf($res,html::escapeHTML($rs->getAuthorCN()),html::escapeHTML($url));
	}
	
	public static function canPatch($rs)
	{
		# If user is super admin, true
		if ($rs->core->auth->isSuperAdmin()) {
			return true;
		}
		
		# If user is admin or contentadmin, true
		if ($rs->core->auth->check('contentadmin',$rs->core->blog->id)) {
			return true;
		}
		
		# No user id in result ? false
		if (!$rs->exists('user_id')) {
			return false;
		}
		
		# If user is usage and owner of the entrie
		if ($rs->core->auth->check('usage',$rs->core->blog->id)
		&& $rs->user_id == $rs->core->auth->userID()) {
			return true;
		}
		
		return false;
	}
}

?>