<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Auth;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Container\{ Factories, Factory };
use Dotclear\Helper\OAuth2\Client\{ Consumer, Store, Token, User };
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Network\HttpClient;
use Throwable;

/**
 * @brief   oAuth2 client store class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class OAuth2Store extends Store
{
    /**
     * Avatar var subdirectory.
     */
    public const VAR_DIR = 'avatar';

    /**
     * Consumers factory.
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

    public function getToken(string $provider, string $user): Token
    {
        $config = [];
        if ($user !== '') {
            $rs = App::credential()->getCredentials([
                'user_id'          => $user,
                'credential_type'  => $this->getType($provider),
                'credential_value' => 'token',
            ]);

            if ($rs->isEmpty()) {
                // Set an empty token
                if (App::auth()->userID() != '') {
                    $this->setToken($provider, $user);
                }
            } else {
                $config = $rs->getAllData();
            }
        }

        return new Token($config);
    }

    public function setToken(string $provider, string $user_id, ?Token $token = null): void
    {
        $this->delToken($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_type', $this->getType($provider));
        $cur->setField('credential_value', 'token');
        $cur->setField('credential_data', new ArrayObject($token?->getConfiguration() ?? []));

        App::credential()->setCredential($user_id, $cur);
    }

    public function delToken(string $provider, string $user_id): void
    {
        App::credential()->delCredentials(
            $this->getType($provider),
            'token',
            $user_id,
            true
        );
    }

    public function getUser(string $provider, string $uid): User
    {
        $rs = App::credential()->getCredentials([
            'credential_type'  => $this->getType($provider),
            'credential_value' => $uid,
        ]);

        $config = [];
        if (!$rs->isEmpty()) {
            $config = $rs->getAllData();
        }
        if ($config === []) {
            $config = [
                'user_id' => (string) App::auth()->userID(),
                'uid'     => $uid,
            ];
        }

        return new User($config);
    }

    public function setUser(string $provider, User $user, string $user_id): void
    {
        $config            = $user->getConfiguration();
        $config['user_id'] = $user_id;

        if (!empty($config['avatar'] ?? '')) {
            // Put user avatar in var
            try {
                $content = HttpClient::quickGet($config['avatar']);
                if ($content) {
                    Files::putContent($this->getUserAvatarLocalPath($provider, $config), $content);
                    $config['avatar'] = $this->getUserAvatarLocalUrl($provider, $config);
                }
            } catch (Throwable) {
            }
        }

        // Delete existing user in credentail table
        $this->delUser($provider, $user_id);

        // Add user to credential table
        $cur = App::credential()->openCredentialCursor();
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_type', $this->getType($provider));
        $cur->setField('credential_value', $user->get('uid')); // user distant uid
        $cur->setField('credential_data', new ArrayObject($config));

        App::credential()->setCredential($user_id, $cur);
    }

    public function delUser(string $provider, string $user_id): void
    {
        $rs = App::credential()->getCredentials([
            'user_id'         => $user_id,
            'credential_type' => $this->getType($provider),
        ]);

        while ($rs->fetch()) {
            // Delete user from credential table
            App::credential()->delCredentials(
                $this->getType($provider),
                (string) $rs->f('credential_value'), // user distant uid
                null,
                true
            );

            $config = $rs->getAllData();
            if (!empty($config['avatar'] ?? '')) {
                // Delete user avatar from var
                try {
                    $path = $this->getUserAvatarLocalPath($provider, $config);
                    if (Files::isDeletable($path)) {
                        unlink($path);
                    }
                } catch (Throwable) {
                }
            }
        }
    }

    public function getLocalUser(string $provider): User
    {
        $config = [];
        if (App::auth()->userID() != '') {
            $rs = App::credential()->getCredentials([
                'user_id'         => App::auth()->userID(),
                'credential_type' => $this->getType($provider),
            ]);

            if (!$rs->isEmpty()) {
                while ($rs->fetch()) {
                    if ($rs->f('credential_value') != 'token') { // type is for token and user
                        $config = $rs->getAllData();

                        break;
                    }
                }
            }
        }

        return new User($config);
    }

    /**
     * Get user avatar local path.
     *
     * @param   array<string, mixed>    $config     The object configuration
     */
    protected function getUserAvatarLocalPath(string $provider, array $config): string
    {
        $path = implode(DIRECTORY_SEPARATOR, [App::config()->varRoot(), static::VAR_DIR]);
        $file = implode(DIRECTORY_SEPARATOR, [static::CONTAINER_ID, $provider, $config['user_id']]);
        Files::makeDir($path);

        return $path . DIRECTORY_SEPARATOR . md5($file) . '.' . (Files::getExtension($config['avatar']) ?: 'jpg');
    }

    /**
     * Get user avatar local URL.
     *
     * @param   array<string, mixed>    $config     The object configuration
     */
    protected function getUserAvatarLocalUrl(string $provider, array $config): string
    {
        $file = implode(DIRECTORY_SEPARATOR, [static::CONTAINER_ID, $provider, $config['user_id']]);

        return 'index.php?vf=' . static::VAR_DIR . '/' . md5($file) . '.' . (Files::getExtension($config['avatar']) ?: 'jpg');
    }

    public function getState(string $state): string
    {
        $session = $this->getSession();

        if (isset($session['state'])
            && is_array($session['state'])
        ) {
            return $session['state'][$state] ?? '';
        }

        return '';
    }

    public function setState(string $provider, string $state): void
    {
        $session                  = $this->getSession();
        $session['state'][$state] = $provider;

        App::session()->set(static::CONTAINER_ID, $session);
    }

    public function delState(string $provider): void
    {
        $session = $this->getSession();
        if (isset($session['state'])
            && is_array($session['state'])
            && false !== ($state = array_search($provider, $session['state']))
        ) {
            $session['state'][$state] = null;

            App::session()->set(static::CONTAINER_ID, $session);
        }
    }

    public function delStates(): void
    {
        $session          = $this->getSession();
        $session['state'] = null;

        App::session()->set(static::CONTAINER_ID, $session);
    }

    public function getRedir(): string
    {
        $session = $this->getSession();

        return $session['redir'] ?: $this->redirect_url;
    }

    public function setRedir(string $redir): void
    {
        $session          = $this->getSession();
        $session['redir'] = $redir;

        App::session()->set(static::CONTAINER_ID, $session);
    }

    public function delRedir(): void
    {
        $session          = $this->getSession();
        $session['redir'] = null;

        App::session()->set(static::CONTAINER_ID, $session);
    }

    /**
     * Get session values.
     *
     * @return  array<string, mixed>
     */
    protected function getSession(): array
    {
        $session = App::session()->get(static::CONTAINER_ID);

        return is_array($session) ? $session : [];
    }
}
