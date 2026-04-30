<?php

/**
 * @file
 * @brief 		The module backend helper resource
 * @ingroup 	widgets
 *
 * @package 	Dotclear
 *
 * @copyright 	Olivier Meunier & Association Dotclear
 * @copyright 	AGPL-3.0
 */
declare(strict_types=1);

\Dotclear\App::backend()->resources()->set('help', 'widgets', __DIR__ . '/help/help.html');
