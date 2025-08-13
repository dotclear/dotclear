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
use Dotclear\Database\Statement\{ DeleteStatement, SelectStatement };
use Dotclear\Helper\Container\{ Factories, Factory };
use Dotclear\Helper\OAuth2\Client\{ Consumer, Store, Token, User };

/**
 * @brief   oAuth2 client store class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class OAuth2Store extends Store
{
    /**
     * Consumers factory.
     *
     * @var     Factory     $consumers
     */
    protected Factory $consumers;

    public function __construct(protected string $redirect_url)
    {
        // grab all configured consumers, see inc/oauth2.php
        $this->consumers = Factories::getFactory(static::CONTAINER_ID);
    }

    public function getConsumer(string $provider): Consumer
    {
        $consumer = $this->consumers->has($provider) ? $this->consumers->get($provider) : null;

        return is_callable($consumer) ? $consumer() : new Consumer(['provider' => $provider]);
    }

    public function setConsumer(string $provider, string $key = '', string $secret = '', string $domain = ''): void
    {
        // We never save consumer configuration as it is set for the wole plateforme in inc/oauth2.php
    }

    public function getToken(string $provider, string $user_id): Token
    {
        $res = [];
        if ($user_id != '') {
            $rs = App::credential()->getCredentials([
                'credential_type' => $this->getType($provider, true),
                'credential_id'   => $user_id,
                'user_id'         => $user_id,
            ]);

            if ($rs->isEmpty()) {
                // Set an empty token
                if (App::auth()->userID() != '') {
                    $this->setToken($provider, $user_id);
                }
            } else {
                $res = json_decode((string) $rs->f('credential_data'), true);
                if (!is_array($res)) {
                    $res = [];
                }
            }
        }

        return new Token($res);
    }

    public function setToken(string $provider, string $user_id, ?Token $token= null): void
    {
        $this->delToken($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('credential_type', $this->getType($provider, true));
        $cur->setField('credential_id', $user_id);
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_data', json_encode($token?->getConfiguration() ?? []));

        App::credential()->setCredential((string) $user_id, $cur);
    }

    public function delToken(string $provider, string $user_id): void
    {
        App::credential()->delCredential(
            $this->getType($provider, true),
            $user_id
        );
    }

    public function getUser(string $provider, string $uid): User
    {
        $rs = App::credential()->getCredentials([
            'credential_type' => $this->getType($provider, false),
            'credential_id'   => $uid,
        ]);

        $res = [];
        if (!$rs->isEmpty()) {
            $res = json_decode((string) $rs->f('credential_data'), true);
        }
        if (!is_array($res) || $res === []) {
            $res = [
                'user_id' => (string) App::auth()->userID(),
                'uid'     => $uid,
            ];
        }

        return new User($res);
    }

    public function setUser(string $provider, User $user, string $user_id): void
    {
        $this->delUser($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('credential_type', $this->getType($provider, false));
        $cur->setField('credential_id', $user->get('uid'));
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_data', json_encode(array_merge($user->getConfiguration(), ['user_id' => $user_id])));

        App::credential()->setCredential((string) $user_id, $cur);
    }

    public function delUser(string $provider, string $user_id): void
    {
        $rs = App::credential()->getCredentials([
            'credential_type' => $this->getType($provider, false),
            'user_id'         => $user_id,
        ]);

        while($rs->fetch()) {
            $res = json_decode($rs->f('credential_data'), true);
            App::credential()->delCredential(
                $this->getType($provider, false),
                $res['uid'] ?? ''
            );
        }
    }

    public function getLocalUser(string $provider): User
    {
        $res = [];
        if (App::auth()->userID() != '') {
            $rs = App::credential()->getCredentials([
                'credential_type' => $this->getType($provider, false),
                'user_id'         => App::auth()->userID(),
            ]);

            if (!$rs->isEmpty()) {
                $res = json_decode((string) $rs->f('credential_data'), true);
            }
        }

        return new User($res);
    }
}
