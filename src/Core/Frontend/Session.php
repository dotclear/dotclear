<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use Dotclear\App;
use Dotclear\Database\Session as DatabaseSession;
use Dotclear\Exception\BlogException;
use Throwable;

/**
 * @brief   Frontend session handler.
 *
 * Transitionnal class to set Dotclear default session handler table.
 *
 * @since   2.35
 */
class Session extends DatabaseSession
{
    /**
     * Constructor.
     */
    public function __construct() {
        // Take care of blog URL for frontend session
        $url = parse_url(App::blog()->url());
        if (!is_array($url)) {
            throw new BlogException(__('Something went wrong while trying to read blog URL.')) ;
        }

        parent::__construct(
            con: App::con(),
            table : App::con()->prefix() . Session::SESSION_TABLE_NAME,
            cookie_name: App::config()->sessionName() . '_' . App::blog()->id(),
            cookie_secure: empty($url['scheme']) || !preg_match('%^http[s]?$%', $url['scheme']) ? false : $url['scheme'] === 'https',
            cookie_path: dirname($url['path']),
            ttl: App::config()->sessionTtl()
        );

        register_shutdown_function(function (): void {
            try {
                if (session_id()) {
                    // Explicitly close session before DB connection
                    session_write_close();
                }
                App::con()->close();
            } catch (Throwable) {
                // Ignore exceptions
            }
        });
    }
}
