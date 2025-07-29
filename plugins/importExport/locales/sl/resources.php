<?php
/**
 * @file
 * @brief 		The module backend helper resource
 * @ingroup 	importExport
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
\Dotclear\App::backend()->resources()->set('help', 'import', __DIR__ . '/help/import.html');
