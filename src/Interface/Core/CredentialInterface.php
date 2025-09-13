<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use ArrayObject;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;

/**
 * @brief   User credentials handler interface.
 *
 * Use this class to store user credential
 * for third party applications.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface CredentialInterface
{
    /**
     * Credential table name.
     *
     * @var    string  CREDENTIAL_TABLE_NAME
     */
    public const CREDENTIAL_TABLE_NAME = 'credential';

    /**
     * Open a credential database table cursor.
     *
     * @return  Cursor  The credential database table cursor
     */
    public function openCredentialCursor(): Cursor;

    /**
     * Get credentials.
     *
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $params         The parameters
     * @param      bool                                             $count_only     Count only
     *
     * @return     MetaRecord  The users.
     */
    public function getCredentials(array|ArrayObject $params = [], bool $count_only = false): MetaRecord;

    /**
     * Set user credential.
     *
     * @throws  \Dotclear\Exception\BadRequestException
     *
     * @param 	string 	$user_id 	The user ID
     * @param 	Cursor 	$cur 		The credential Cursor
     */
    public function setCredential(string $user_id, Cursor $cur): void;

    /**
     * Delete credentials.
     *
     * If <var>$global</var> is set to False, current blog is selected.
     *
     * @param   string          $credential_type    The credential type
     * @param   string          $credential_value   The credential value
     * @param   null|string     $user_id            The user_id (or null for all users)
     * @param   bool            $global             True for global or false for current blog
     */
    public function delCredentials(string $credential_type, string $credential_value = '', ?string $user_id = null, bool $global = true): void;

    /**
     * Encrypt data.
     *
     * This is used to protect sensible data in database.
     * Data encryption is linked to Dotclear's master key.
     *
     * @param   ArrayObject<string, mixed>|array<string, mixed>     $data   The credential data to encode
     *
     * @return  string  The encoded credential data
     */
    public function encryptData(array|ArrayObject $data): string;

    /**
     * Decrypt data.
     *
     * Decode data encoded with self::encreyptData()
     *
     * @param   string  $data   The encoded credential data
     *
     * @return  array<string, mixed> The decoded credential data
     */
    public function decryptData(string $data): array;
}
