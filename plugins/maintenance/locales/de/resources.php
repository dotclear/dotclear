<?php
/**
 * @file
 * @brief 		The module backend helper resource
 * @ingroup 	maintenance
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
\Dotclear\App::backend()->resources()->set('help', 'maintenance', __DIR__ . '/help/maintenance.html');
