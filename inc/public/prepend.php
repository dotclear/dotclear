<?php
/**
 * @deprecated since 2.27 Use Dotclear\App::boostrap('Frontend');
 *
 * Keep this file for backward compatibility with existing blogs index.php
 *
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src', 'App.php']);

Dotclear\App::bootstrap('Frontend');
