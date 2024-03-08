<?php
/**
 * @file
 * @brief 		The module backend helper resource
 * @ingroup 	blowup
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
\Dotclear\App::backend()->resources()->set('help', 'blowupConfig', __DIR__ . '/help/help.html');
