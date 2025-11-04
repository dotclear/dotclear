<?php
/**
 * @file
 * @brief 		The module backend helper resource
 * @ingroup 	userPref
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
\Dotclear\App::backend()->resources()->set('help', 'userPref', __DIR__ . '/help/help.html');
