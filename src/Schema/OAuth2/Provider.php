<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\OAuth2;

use Dotclear\App;
use Dotclear\Helper\OAuth2\Client\Provider as BaseProvider;

/**
 * @brief   Dotclear oAuth2 client provider class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class Provider extends BaseProvider
{
    protected function getCodeVerifier(): string
    {
        return (string) App::session()->get('code_verifier');
    }

    protected function setCodeVerifier(?string $code): void
    {
        App::session()->set('code_verifier', $code);
    }
}
