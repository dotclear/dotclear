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
 * @brief   oAuth2 client user class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class User extends Descriptor
{
    public const CONFIGURATION = [
        'user_id'     => '',
        'uid'         => '',
        'displayname' => '',
        'email'       => '',
        'avatar'      => '',
    ];
    public const MUSTFILLED = [
        'user_id',
        'uid',
    ];

    /**
     * Parse provider user response.
     *
     * @param   string|mixed[]          $response   The provider user response
     * @param   array<string, string>   $pairs      The keys
     *
     * @return  User    The user info
     */
    public static function parseUser(string|array $response, array $pairs): self
    {
        $config = self::CONFIGURATION;
        if (is_array($response)) {
            foreach ($config as $key => $_) {
                if ($key == 'user_id') {
                    continue;
                }
                if (isset($pairs[$key]) && isset($response[$pairs[$key]])) {
                    $config[$key] = (string) $response[$pairs[$key]];
                }
            }
        }

        return new self($config);
    }
}
