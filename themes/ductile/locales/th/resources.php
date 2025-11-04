<?php
/**
 * @file
 * @brief 		The module backend helper resource
 * @ingroup 	ductile
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
\Dotclear\App::backend()->resources()->set('help', 'ductile', __DIR__ . '/help/help.html');
