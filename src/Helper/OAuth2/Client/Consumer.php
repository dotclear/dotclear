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
 * @brief   oAuth2 client consumer class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Consumer extends Descriptor
{
    /**
     * @var array<string, mixed> CONFIGURATION
     */
    public const CONFIGURATION = [
        'provider' => '',
        'key'      => '',
        'secret'   => '',
        'domain'   => '',
    ];

    /**
     * @var string[] REQUIREMENTS
     */
    public const REQUIREMENTS = [
        'provider',
    ];

    /**
     * @var string[] MUSFILLED
     */
    public const MUSTFILLED = [
        'key',
        'secret',
    ];
}
