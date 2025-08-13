<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client;

/**
 * @brief   oAuth2 client token class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Token extends Descriptor
{
    public const CONFIGURATION = [
        'access_token'  => '',
        'refresh_token' => '',
        'expiry'        => 0,
        'scope'         => [],
    ];
    public const MUSTFILLED = [
        'access_token',
        'scope',
    ];

    /**
     * Check if a access token is expired.
     *
     * @return  bool    True if token is expired
     */
    public function isExpired(): bool
    {
        return $this->get('expiry') != 0 && $this->get('expiry') < time();
    }

    /**
     * Convert a delai to a timestamp.
     *
     * @param   int     $expires_in     The delay
     *
     * @return  int     The timestamp
     */
    public static function convertToTime(int $expires_in = 0): int
    {
        return $expires_in ? $expires_in + time() : 0;
    }
}
