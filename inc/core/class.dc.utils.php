<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
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
	
	/**
	Cleanup a list of IDs
	
	@param	ids			<b>mixed</b>	ID(s)
	@return	<b>array</b>
	*/
	public static function cleanIds($ids)
	{
		$clean_ids = array();
		
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		
		foreach($ids as $id)
		{
			$id = abs((integer) $id);
			
			if (!empty($id)) {
				$clean_ids[] = $id;
			}
		}
		return $clean_ids;
	}

	/**
	 * Compare two versions with option of using only main numbers.
	 *
	 * @param  string	$current_version	Current version
	 * @param  string	$required_version	Required version
	 * @param  string	$operator			Comparison operand
	 * @param  boolean	$strict				Use full version
	 * @return boolean	True if comparison success
	 */
	public static function versionsCompare($current_version, $required_version, $operator='>=', $strict=true)
	{
		if ($strict) {
			$current_version = preg_replace('!-r(\d+)$!', '-p$1', $current_version);
			$required_version = preg_replace('!-r(\d+)$!', '-p$1', $required_version);
		}
		else {
			$current_version = preg_replace('/^([0-9\.]+)(.*?)$/', '$1', $current_version);
			$required_version = preg_replace('/^([0-9\.]+)(.*?)$/', '$1', $required_version);
		}

		return (boolean) version_compare($current_version, $required_version, $operator);
	}
}

?>