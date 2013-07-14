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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$version = $core->plugins->moduleInfo('blogroll','version');

if (version_compare($core->getVersion('blogroll'),$version,'>=')) {
	return;
}

/* Database schema
-------------------------------------------------------- */
$s = new dbStruct($core->con,$core->prefix);

$s->link
	->link_id			('bigint',	0,	false)
	->blog_id			('varchar',	32,	false)
	->link_href		('varchar',	255,	false)
	->link_title		('varchar',	255,	false)
	->link_desc		('varchar',	255,	true)
	->link_lang		('varchar',	5,	true)
	->link_xfn		('varchar',	255,	true)
	->link_position	('integer',	0,	false,	0)
	
	->primary('pk_link','link_id')
	;

$s->link->index('idx_link_blog_id','btree','blog_id');
$s->link->reference('fk_link_blog','blog_id','blog','blog_id','cascade','cascade');

# Schema installation
$si = new dbStruct($core->con,$core->prefix);
$changes = $si->synchronize($s);

$core->setVersion('blogroll',$version);
return true;
?>