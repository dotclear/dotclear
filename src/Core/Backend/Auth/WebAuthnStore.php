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
use Dotclear\Helper\Network\Http;

use Dotclear\Helper\WebAuthn\Data\Store;
use Dotclear\Helper\WebAuthn\Exception\StoreException;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\RpOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Credentials\Option\UserOptionInterface;
use Dotclear\Interface\Helper\WebAuthn\Data\CredentialInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   Dotclear backend WebAuthn store class.
 *
 * WebAuthn credential id is base64 encoded to be safety added to database.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class WebAuthnStore extends Store
{
    /**
     * Default database credential type.
     *
     * @var     string  DEFAULT_TYPE
     */
    public const DEFAULT_TYPE = 'webauthn';

    /**
     * The passkey providers file name.
     *
     * @var     string  PASSKEY_PROVIDERS_FILE
     */
    public const PASSKEY_PROVIDERS_FILE = 'webauthnpasskeyproviders.json';

    /**
     * Load services from container.
     *
     * @param   ByteBufferInterface     $buffer         The byte buffer interface
     * @param   RpOptionInterface       $rp             The relying party instance
     * @param   UserOptionInterface     $user           The user option instance
     * @param   CredentialInterface     $credential     The credential data interface
     */
    public function __construct(
        protected ByteBufferInterface $buffer,
        protected RpOptionInterface $rp,
        protected UserOptionInterface $user,
        protected CredentialInterface $credential
    ) {
    }

    public function getRelyingParty(): RpOptionInterface
    {
        $this->rp->configure([
            'id'   => (string) parse_url(Http::getHost(), PHP_URL_HOST),
            'name' => App::config()->vendorName(),
        ]);

        return $this->rp;
    }

    public function getUser(): UserOptionInterface
    {
        $this->user->configure([
            'id'          => (string) App::auth()->userID(),
            'name'        => (string) App::auth()->userID(),
            'displayname' => (string) App::auth()->getInfo('user_cn'),
        ]);

        return $this->user;
    }

    public function setCredential(CredentialInterface $credential): void
    {
        $this->delCredential($credential->credentialId());

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('credential_id', $this->encodeValue($credential->credentialId()));
        $cur->setField('credential_type', static::DEFAULT_TYPE);
        $cur->setField('credential_data', $credential->encodeData());

        App::credential()->setCredential((string) App::auth()->userID(), $cur);
    }

    public function getCredentials(?string $credential_id = null, ?string $user_id = null): array
    {
        $data   = [];
        $params = [
            'credntial_type' => static::DEFAULT_TYPE,
        ];
        if (!is_null($credential_id)) {
            $params['credential_id'] = $this->encodeValue($credential_id);
        }
        if (!is_null($user_id)) {
            $params['user_id'] = $user_id;
        }

        $rs = App::credential()->getCredentials($params);
        if (!$rs->isEmpty()) {
            while ($rs->fetch()) {
                $data[] = $this->credential->decodeData((string) $rs->f('credential_data'));
            }
        }

        return $data;
    }

    public function delCredential(string $credential_id): void
    {
        App::credential()->delCredential(static::DEFAULT_TYPE, $this->encodeValue($credential_id));
    }

    public function setProviders(array $data): void
    {
        $path = App::config()->varRoot() . DIRECTORY_SEPARATOR . static::PASSKEY_PROVIDERS_FILE;

        if (!is_writable(dirname($path))) {
            throw new StoreException('Unable to write passkey providers file');
        }

        file_put_contents($path, json_encode($data));
    }

    public function getProviders(): array
    {
        $data = [];
        $path = App::config()->varRoot() . DIRECTORY_SEPARATOR . static::PASSKEY_PROVIDERS_FILE;
        if (!file_exists($path)) {
            // no file, update list
            return [];
        }

        if (!is_readable($path)) {
            throw new StoreException('Unable to read passkey providers file');
        }

        if ((filemtime($path) + strtotime('1 month')) < time()) {
            // cache file is too old, update list.
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }
}
