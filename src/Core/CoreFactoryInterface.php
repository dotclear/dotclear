<?php
/**
 * Core factory interface.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

// classes that move to \Dotclear\Core
use dcAuth;
use dcError;
use dcLog;
use dcMeta;
use dcNotices;
use dcRestServer;
//
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Session;
use Dotclear\Helper\Behavior;

interface CoreFactoryInterface
{
    public function auth(): dcAuth;
    public function behavior(): Behavior;
    public function blogs(): Blogs;
    public function con(): AbstractHandler;
    public function error(): dcError;
    public function filter(): Filter;
    public function formater(): Formater;
    public function log(): dcLog;
    public function meta(): dcMeta;
    public function nonce(): Nonce;
    public function notice(): dcNotices;
    public function postTypes(): PostTypes;
    public function rest(): dcRestServer;
    public function session(): Session;
    public function users(): Users;
    public function version(): Version;
}
