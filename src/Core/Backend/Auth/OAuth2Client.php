<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Auth;

use Dotclear\App;
use Dotclear\Core\Backend\Helper;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\OAuth2\Client\{ Client, Provider };
use Dotclear\Schema\OAuth2\{ Auth0Connect, GithubConnect, GoogleConnect, Lwa, SlackConnect };
use Exception;

/**
 * @brief   oAuth2 client helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class OAuth2Client extends Client
{
    protected function getDefaultServices(): array
    {
        return [    // @phpstan-ignore-line
            GoogleConnect::PROVIDER_ID => GoogleConnect::class,
            GithubConnect::PROVIDER_ID => GithubConnect::class,
            SlackConnect::PROVIDER_ID  => SlackConnect::class,
            Auth0Connect::PROVIDER_ID  => Auth0Connect::class,
            Lwa::PROVIDER_ID           => Lwa::class,
        ];
    }

    protected function checkSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            App::session()->start();
        }
    }

    protected function requestActionError(Exception $e): bool
    {
        if ($_REQUEST['process'] == 'Auth') {
            App::backend()->err = $e->getMessage();
        } else {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    protected function checkUser(string $user_id): bool
    {
        if (App::auth()->checkUser($user_id, null, null, false)) {
            $_SESSION['sess_user_id']     = $user_id;
            $_SESSION['sess_browser_uid'] = Http::browserUID(App::config()->masterKey());

            return true;
        }

        return false;
    }

    public function getDisabledProviders(): array
    {
        $disabled = json_decode((string) App::blog()->settings()->get(self::CONTAINER_ID)->get('disabled_providers'));

        return is_array($disabled) ? array_values($disabled) : [];
    }

    public function setDisabledProviders(array $providers): void
    {
        App::blog()->settings()->get(self::CONTAINER_ID)->put('disabled_providers', json_encode(array_values($providers)), 'string');
    }

    public function getProviderLogo(Provider $provider): string
    {
        return Helper::adminIcon($provider::getIcon() ?: '', true, '', '', 'icon-mini');
    }
}
