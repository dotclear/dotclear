<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\AbstractHandler;

/**
 * @brief   Database connection handler.
 *
 * Transitionnal class to set Dotclear default db connection handler.
 *
 * We keep Connection::init() as third party class use it:
 * * Plugins\importExport\ModuleImportDc1
 * * Plugins\importExport\src\ModuleImportWp
 * * Process\Install\Wizard
 * These class use App::newConnectionFromValues() to instanciate database connection.
 */
abstract class Connection extends AbstractHandler
{
}
