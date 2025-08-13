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
     * @param      array<string, mixed>|ArrayObject<string, mixed>  $params      The parameters
     *
     * @return     MetaRecord  The users.
     */
    public function getCredentials(array|ArrayObject $params = []): MetaRecord;

    /**
     * Set user credential.
     *
     * @param 	string 	$user_id 	The user ID
     * @param 	Cursor 	$cur 		The credential Cursor
     */
    public function setCredential(string $user_id, Cursor $cur): void;

    /**
     * Delete a credential.
     *
     * @param   string  $credential_type    The credential type
     * @param   string  $credential_id      The credential ID
     */
    public function delCredential(string $credential_type, string $credential_id): void;
}
