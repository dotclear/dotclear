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

function dotclearUpgrade($core)
{
	$version = $core->getVersion('core');
	
	if ($version === null) {
		return false;
	}
	
	if (version_compare($version,DC_VERSION,'<') == 1)
	{
		try
		{
			if ($core->con->driver() == 'sqlite') {
				throw new Exception(__('SQLite Database Schema cannot be upgraded.'));
			}
			
			# Database upgrade
			$_s = new dbStruct($core->con,$core->prefix);
			require dirname(__FILE__).'/db-schema.php';
			
			$si = new dbStruct($core->con,$core->prefix);
			$changes = $si->synchronize($_s);
			
			/* Some other upgrades
			------------------------------------ */
			# Populate media_dir field (since 2.0-beta3.3)
			if (version_compare($version,'2.0-beta3.3','<'))
			{
				$strReq = 'SELECT media_id, media_file FROM '.$core->prefix.'media ';
				$rs_m = $core->con->select($strReq);
				while($rs_m->fetch()) {
					$cur = $core->con->openCursor($core->prefix.'media');
					$cur->media_dir = dirname($rs_m->media_file);
					$cur->update('WHERE media_id = '.(integer) $rs_m->media_id);
				}
			}
			
			if (version_compare($version,'2.0-beta7.3','<'))
			{
				# Blowup becomes default theme
				$strReq = 'UPDATE '.$core->prefix.'setting '.
						"SET setting_value = '%s' ".
						"WHERE setting_id = 'theme' ".
						"AND setting_value = '%s' ".
						'AND blog_id IS NOT NULL ';
				$core->con->execute(sprintf($strReq,'blueSilence','default'));
				$core->con->execute(sprintf($strReq,'default','blowup'));
			}
			
			if (version_compare($version,'2.1-alpha2-r2383','<'))
			{
				$schema = dbSchema::init($core->con);
				$schema->dropUnique($core->prefix.'category',$core->prefix.'uk_cat_title');
				
				# Reindex categories
				$rs = $core->con->select(
					'SELECT cat_id, cat_title, blog_id '.
					'FROM '.$core->prefix.'category '.
					'ORDER BY blog_id ASC , cat_position ASC '
				);
				$cat_blog = $rs->blog_id;
				$i = 2;
				while ($rs->fetch()) {
					if ($cat_blog != $rs->blog_id) {
						$i = 2;
					}
					$core->con->execute(
						'UPDATE '.$core->prefix.'category SET '
						.'cat_lft = '.($i++).', cat_rgt = '.($i++).' '.
						'WHERE cat_id = '.(integer) $rs->cat_id
					);
					$cat_blog = $rs->blog_id;
				}
			}
			
			if (version_compare($version,'2.1.6','<='))
			{
				# ie7js has been upgraded
				$ie7files = array (
					'ie7-base64.php ',
					'ie7-content.htc',
					'ie7-core.js',
					'ie7-css2-selectors.js',
					'ie7-css3-selectors.js',
					'ie7-css-strict.js',
					'ie7-dhtml.js',
					'ie7-dynamic-attributes.js',
					'ie7-fixed.js',
					'ie7-graphics.js',
					'ie7-html4.js',
					'ie7-ie5.js',
					'ie7-layout.js',
					'ie7-load.htc',
					'ie7-object.htc',
					'ie7-overflow.js',
					'ie7-quirks.js',
					'ie7-server.css',
					'ie7-standard-p.js',
					'ie7-xml-extras.js'
					);
				foreach ($ie7files as $f) {
					@unlink(DC_ROOT.'/admin/js/ie7/'.$f);
				}
			}
			
			if (version_compare($version,'2.2-alpha1-r3043','<'))
			{
				# metadata has been integrated to the core.
				$core->plugins->loadModules(DC_PLUGINS_ROOT);
				if ($core->plugins->moduleExists('metadata')) {
					$core->plugins->deleteModule('metadata');
				}
				
				# Tags template class has been renamed
				$sqlstr =
					'SELECT blog_id, setting_id, setting_value '.
					'FROM '.$core->prefix.'setting '.
					'WHERE (setting_id = \'widgets_nav\' OR setting_id = \'widgets_extra\') '.
					'AND setting_ns = \'widgets\';';
				$rs = $core->con->select($sqlstr);
				while ($rs->fetch()) {
					$widgetsettings = base64_decode($rs->setting_value);
					$widgetsettings = str_replace ('s:11:"tplMetadata"','s:7:"tplTags"',$widgetsettings);
					$cur = $core->con->openCursor($core->prefix.'setting');
					$cur->setting_value = base64_encode($widgetsettings);
					$sqlstr = 'WHERE setting_id = \''.$rs->setting_id.'\' AND setting_ns = \'widgets\' '.
					'AND blog_id ' .
					($rs->blog_id == NULL ? 'is NULL' : '= \''.$core->con->escape($rs->blog_id).'\'');
					$cur->update($sqlstr);
				}
			}

			if (version_compare($version,'2.3','<'))
			{
				# Add global favorites
				$sqlstr = 'INSERT INTO `dc_pref` (`pref_id`, `user_id`, `pref_ws`, `pref_value`, `pref_type`, `pref_label`) VALUES';
				$sqlstr .= '(\'g000\', NULL, \'favorites\', \'a:8:{s:4:"name";s:8:"new_post";s:5:"title";'.
					's:'.strlen(__('New entry')).':"'.__('New entry').'";s:3:"url";s:8:"post.php";'.
					's:10:"small-icon";s:20:"images/menu/edit.png";s:10:"large-icon";s:22:"images/menu/edit-b.png";'.
					's:11:"permissions";s:18:"usage,contentadmin";s:2:"id";N;s:5:"class";s:13:"menu-new-post";}\', \'string\', NULL)';
				$sqlstr .= '(\'g001\', NULL, \'favorites\', \'a:8:{s:4:"name";s:5:"posts";s:5:"title";'.
					's:'.strlen(__('Entries')).':"'.__('Entries').'";s:3:"url";s:9:"posts.php";'.
					's:10:"small-icon";s:23:"images/menu/entries.png";s:10:"large-icon";s:25:"images/menu/entries-b.png";'.
					's:11:"permissions";s:18:"usage,contentadmin";s:2:"id";N;s:5:"class";N;}\', \'string\', NULL)';
				$sqlstr .= '(\'g002\', NULL, \'favorites\', \'a:8:{s:4:"name";s:8:"comments";s:5:"title";'.
					's:'.strlen(__('Comments')).':"'.__('Comments').'";s:3:"url";s:12:"comments.php";'.
					's:10:"small-icon";s:24:"images/menu/comments.png";s:10:"large-icon";s:26:"images/menu/comments-b.png";'.
					's:11:"permissions";s:18:"usage,contentadmin";s:2:"id";N;s:5:"class";N;}\', \'string\', NULL)';
				$sqlstr .= '(\'g003\', NULL, \'favorites\', \'a:8:{s:4:"name";s:5:"prefs";s:5:"title";'.
					's:'.strlen(__('My preferences')).':"'.__('My preferences').'";s:3:"url";s:15:"preferences.php";'.
					's:10:"small-icon";s:25:"images/menu/user-pref.png";s:10:"large-icon";s:27:"images/menu/user-pref-b.png";'.
					's:11:"permissions";s:1:"*";s:2:"id";N;s:5:"class";N;}\', \'string\', NULL)';
				$sqlstr .= '(\'g004\', NULL, \'favorites\', \'a:8:{s:4:"name";s:9:"blog_pref";s:5:"title";'.
					's:'.strlen(__('Blog settings')).':"'.__('Blog settings').'";s:3:"url";s:13:"blog_pref.php";'.
					's:10:"small-icon";s:25:"images/menu/blog-pref.png";s:10:"large-icon";s:27:"images/menu/blog-pref-b.png";'.
					's:11:"permissions";s:5:"admin";s:2:"id";N;s:5:"class";N;}\', \'string\', NULL)';
				$sqlstr .= '(\'g005\', NULL, \'favorites\', \'a:8:{s:4:"name";s:10:"blog_theme";s:5:"title";'.
					's:'.strlen(__('Blog appearance')).':"'.__('Blog appearance').'";s:3:"url";s:14:"blog_theme.php";'.
					's:10:"small-icon";s:22:"images/menu/themes.png";s:10:"large-icon";s:28:"images/menu/blog-theme-b.png";'.
					's:11:"permissions";s:5:"admin";s:2:"id";N;s:5:"class";N;}\', \'string\', NULL)';
				$sqlstr .= '(\'g006\', NULL, \'favorites\', \'a:8:{s:4:"name";s:5:"pages";s:5:"title";'.
					's:'.strlen(__('Pages')).':"'.__('Pages').'";s:3:"url";s:18:"plugin.php?p=pages";'.
					's:10:"small-icon";s:27:"index.php?pf=pages/icon.png";s:10:"large-icon";s:31:"index.php?pf=pages/icon-big.png";'.
					's:11:"permissions";s:18:"contentadmin,pages";s:2:"id";N;s:5:"class";N;}\', \'string\', NULL)';
				$sqlstr .= '(\'g007\', NULL, \'favorites\', \'a:8:{s:4:"name";s:8:"blogroll";s:5:"title";'.
					's:'.strlen(__('Blogroll')).':"'.__('Blogroll').'";s:3:"url";s:21:"plugin.php?p=blogroll";'.
					's:10:"small-icon";s:36:"index.php?pf=blogroll/icon-small.png";s:10:"large-icon";s:30:"index.php?pf=blogroll/icon.png";'.
					's:11:"permissions";s:18:"usage,contentadmin";s:2:"id";N;s:5:"class";N;}\', \'string\', NULL);';
				$core->con->execute($sqlstr);
			}
			
			$core->setVersion('core',DC_VERSION);
			$core->blogDefaults();
			
			# Drop content from session table
			$core->con->execute('DELETE FROM '.$core->prefix.'session ');
			
			# Empty templates cache directory
			try {
				$core->emptyTemplatesCache();
			} catch (Exception $e) {}
			
			return $changes;
		}
		catch (Exception $e)
		{
			throw new Exception(__('Something went wrong with auto upgrade:').
			' '.$e->getMessage());
		}
	}
	
	# No upgrade?
	return false;
}
?>