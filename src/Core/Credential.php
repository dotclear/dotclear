<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use DateTimeImmutable;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Exception\BadRequestException;
use Dotclear\Interface\Core\CredentialInterface;
use Dotclear\Schema\Extension\Credential as CredentialExtension;

/**
 * @brief   User credentials handler.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Credential implements CredentialInterface
{
    public const SSL_ENCRYPTION = 'aes-256-cbc';

    private readonly string $credential_table;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
        $this->credential_table = $this->core->db()->con()->prefix() . self::CREDENTIAL_TABLE_NAME;
    }

    public function openCredentialCursor(): Cursor
    {
        return $this->core->db()->con()->openCursor($this->credential_table);
    }

    public function getCredentials(array|ArrayObject $params = [], bool $count_only = false): MetaRecord
    {
        $sql = new SelectStatement();

        if ($count_only) {
            $sql
                ->column($sql->count('K.user_id'))
                ->from($sql->as($this->credential_table, 'K'));
        } else {
            $sql
                ->columns([
                    'K.user_id',
                    'K.blog_id',
                    'credential_dt',
                    'credential_type',
                    'credential_value',
                    'credential_data',
                    'U.user_name',
                    'U.user_firstname',
                    'U.user_displayname',
                    'U.user_url',
                ])
                ->from($sql->as($this->credential_table, 'K'));

            if (!empty($params['columns'])) {
                $sql->columns($params['columns']);
            }
            $sql
                ->join(
                    (new JoinStatement())
                        ->left()
                        ->from($sql->as($this->core->db()->con()->prefix() . $this->core->auth()::USER_TABLE_NAME, 'U'))
                        ->on('K.user_id = U.user_id')
                        ->statement()
                );
        }

        $sql->where('NULL IS NULL');

        if (!isset($params['credential_type'])) {
            $params['credential_type'] = 'webauthn';
        }
        if (!empty($params['credential_type'])) {
            $sql->and('credential_type =' . $sql->quote($params['credential_type']));
        }

        if (!isset($params['blog_id'])) {
            $sql->and($sql->isNull('K.blog_id'));
        } elseif (!empty($params['blog_id'])) {
            $sql->and('K.blog_id =' . $sql->quote($params['blog_id']));
        }
        // nothing to do

        if (!empty($params['user_id'])) {
            $sql->and('K.user_id =' . $sql->quote($params['user_id']));
        }

        if (!empty($params['credential_value'])) {
            $sql->and('credential_value =' . $sql->quote($params['credential_value']));
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('credential_dt DESC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        $rs = $sql->select();
        if ($rs instanceof MetaRecord) {
            $rs->extend(CredentialExtension::class);
        }

        return $rs ?? MetaRecord::newFromArray([]);
    }

    public function setCredential(string $user_id, Cursor $cur): void
    {
        if ('' === $cur->getField('blog_id')) {
            throw new BadRequestException('Invalid blog id');
        }

        if (null === $cur->getField('user_id')) {
            $cur->setField('user_id', $this->core->auth()->userID());
        }
        if ('' == $cur->getField('user_id')) {
            throw new BadRequestException('Invalid user id');
        }

        if ('' == $cur->getField('credential_dt')) {
            $cur->setField('credential_dt', (new DateTimeImmutable('now'))->format('Y-m-d H:i:00'));
        }

        if ('' == $cur->getField('credential_type')) {
            throw new BadRequestException('Invalid credential type');
        }

        if (null === $cur->getField('credential_value')) {
            $cur->setField('credential_value', '');
        }

        if (null !== $cur->getField('credential_data')) {
            if (is_string($cur->getField('credential_data'))) {
                $cur->setField('credential_data', ['data' => $cur->getField('credential_data')]);
            }
            $cur->setField('credential_data', $this->encryptData($cur->getField('credential_data')));
        }

        $cur->insert();
    }

    public function delCredentials(string $credential_type, string $credential_value = '', ?string $user_id = null, bool $global = true): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->credential_table)
            ->where('credential_type = ' . $sql->quote($credential_type))
            ->and('credential_value = ' . $sql->quote($credential_value))
        ;

        if (null !== $user_id) {
            $sql->and('user_id =' . $sql->quote($user_id));
        }

        if ($global) {
            $sql->and($sql->isNull('blog_id'));
        } else {
            $sql->and('blog_id =' . $sql->quote($this->core->blog()->id()));
        }

        $sql->delete();
    }

    public function encryptData(array|ArrayObject $data): string
    {
        $data = (string) json_encode($data, JSON_UNESCAPED_SLASHES);

        if ($this->hasOpenssl()) {
            $key  = hash($this->core->config()->cryptAlgo(), $this->core->config()->masterKey());
            $iv   = substr(hash($this->core->config()->cryptAlgo(), $this->core->config()->vendorName()), 0, 16); // find a better key
            $data = (string) openssl_encrypt($data, static::SSL_ENCRYPTION, $key, 0, $iv);
        }

        return base64_encode($data);
    }

    public function decryptData(string $data): array
    {
        $data = base64_decode($data, false);

        if ($this->hasOpenssl()) {
            $key  = hash($this->core->config()->cryptAlgo(), $this->core->config()->masterKey());
            $iv   = substr(hash($this->core->config()->cryptAlgo(), $this->core->config()->vendorName()), 0, 16); // find a better key
            $data = (string) openssl_decrypt($data, static::SSL_ENCRYPTION, $key, 0, $iv);
        }

        return json_decode($data, true) ?: [];
    }

    private function hasOpenssl(): bool
    {
        return function_exists('openssl_encrypt') && in_array(static::SSL_ENCRYPTION, openssl_get_cipher_methods());
    }
}
