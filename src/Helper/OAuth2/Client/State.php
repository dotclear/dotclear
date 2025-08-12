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
 * @brief   oAuth2 client provider state class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class State
{
    /**
     * The state.
     *
     * @var     string  $state
     */
    public readonly string $state;

    /**
     * State constructor.
     *
     * State is created on the fly if none is given.
     *
     * @param   string  $state  The state
     */
    public function __construct(string $state = '')
    {
        $this->state = empty($state) ? bin2hex(random_bytes(16)) : $state;
    }

    /**
     * Compare states.
     *
     * @param   null|string     $state  The state
     *
     * @return  bool    True if states match
     */
    public function check(?string $state): bool
    {
        return $this->state === $state;
    }
}
