<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

/**
@ingroup DC_CORE
@brief Dotclear helper methods

Provides some Dotclear helpers
*/
class dcUtils
{
	/**
	Static function that returns user's common name given to his
	<var>user_id</var>, <var>user_name</var>, <var>user_firstname</var> and
	<var>user_displayname</var>.
	
	@param	user_id			<b>string</b>	User ID
	@param	user_name			<b>string</b>	User's name
	@param	user_firstname		<b>string</b>	User's first name
	@param	user_displayname	<b>string</b>	User's display name
	@return	<b>string</b>
	*/
	public static function getUserCN($user_id, $user_name, $user_firstname, $user_displayname)
	{
		if (!empty($user_displayname)) {
			return $user_displayname;
		}
		
		if (!empty($user_name)) {
			if (!empty($user_firstname)) {
				return $user_firstname.' '.$user_name;
			} else {
				return $user_name;
			}
		} elseif (!empty($user_firstname)) {
			return $user_firstname;
		}
		
		return $user_id;
	}
}

?>