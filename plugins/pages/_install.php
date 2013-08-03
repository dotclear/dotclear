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

if (!defined('DC_CONTEXT_ADMIN')){return;}

# Create first page
$core->setBlog('default');

$core->blog->settings->addNamespace('pages');
$core->blog->settings->pages->put('firstpage',false, 'boolean');

$params = array(
	'post_type' => 'page',
	'no_content' => true
);
$counter = $core->blog->getPosts($params,true);

If( $counter->f(0) == 0 && !$core->blog->settings->pages->get('firstpage') ) {
	
	$core->blog->settings->pages->put('firstpage',true);

	$cur = $core->con->openCursor($core->prefix.'post');
	$cur->user_id = $core->auth->userID();
	$cur->post_type = 'page';
	$cur->post_format = 'xhtml';
	$cur->post_lang = $core->blog->settings->system->lang;
	$cur->post_title = __('Page de démonstration');
	$cur->post_content = __('<h3>Créer une nouvelle page</h3><p>Cliquez sur le lien <strong>Pages</strong> situé sur le <strong>Tableau de bord</strong> ou dans la section <strong>Blog</strong> du menu latéral. La liste des pages s\'affiche. Cliquez sur le bouton <strong>Nouvelle page</strong> en haut de la page pour créer une page. vous retrouvez alors la même interface que pour éditer les <a href="http://fr.dotclear.org/documentation/2.0/usage/entries" class="wikilink1" title="2.0:usage:entries">billets</a> mais sans catégorie ni tags.</p>');
	$cur->post_content_xhtml = $cur->post_content;
	$cur->post_excerpt = __('<p>Les pages sont des contenus indépendants des billets. Elles servent par exemple à créer des pages <em>À propos</em> ou <em>Mentions légales</em> qui ont vocation à être toujours accessibles, contrairement aux billets progressivement remplacés par les nouveaux billets.</p>');
	$cur->post_excerpt_xhtml = $cur->post_excerpt;
	$cur->post_status = 1;
	$cur->post_open_comment = 0;
	$cur->post_open_tb = 0;
	$post_id = $core->blog->addPost($cur);
	
}
