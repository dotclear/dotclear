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

    public function getToken(string $provider, string $user_id): Token
    {
        $config = [];
        if ($user_id !== '') {
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
                $config = json_decode((string) $rs->f('credential_data'), true);
                if (!is_array($config)) {
                    $config = [];
                }
            }
        }

        return new Token($config);
    }

    public function setToken(string $provider, string $user_id, ?Token $token = null): void
    {
        $this->delToken($provider, $user_id);

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('credential_type', $this->getType($provider, true));
        $cur->setField('credential_id', $user_id);
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_data', json_encode($token?->getConfiguration() ?? []));

        App::credential()->setCredential($user_id, $cur);
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

        $config = [];
        if (!$rs->isEmpty()) {
            $config = json_decode((string) $rs->f('credential_data'), true);
        }
        if (!is_array($config) || $config === []) {
            $config = [
                'user_id' => (string) App::auth()->userID(),
                'uid'     => $uid,
            ];
        }

        return new User($config);
    }

    public function setUser(string $provider, User $user, string $user_id): void
    {
        $config = $user->getConfiguration();
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
        $cur->setField('credential_type', $this->getType($provider, false));
        $cur->setField('credential_id', $user->get('uid'));
        $cur->setField('user_id', $user_id);
        $cur->setField('credential_data', json_encode($config));

        App::credential()->setCredential($user_id, $cur);

    }

    public function delUser(string $provider, string $user_id): void
    {
        $rs = App::credential()->getCredentials([
            'credential_type' => $this->getType($provider, false),
            'user_id'         => $user_id,
        ]);

        while ($rs->fetch()) {
            $config = json_decode((string) $rs->f('credential_data'), true);

            // Delete user from credential table
            App::credential()->delCredential(
                $this->getType($provider, false),
                $config['uid'] ?? ''
            );

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
                'credential_type' => $this->getType($provider, false),
                'user_id'         => App::auth()->userID(),
            ]);

            if (!$rs->isEmpty()) {
                $config = json_decode((string) $rs->f('credential_data'), true);
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
}
