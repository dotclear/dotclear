<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Helper\WebAuthn\Data;

/**
 * @brief   WebAuthn attestation certificates interface.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
interface CertificatesInterface
{
    /**
     * List of default allowed certificates extensions.
     *
     * @var     string[]    DEFAULT_CERTIFICATES_EXTENTIONS
     */
    public const DEFAULT_CERTIFICATES_EXTENTIONS = ['pem', 'crt', 'cer', 'der'];

    /**
     * FIDO MDS URL.
     *
     * @var     string  FIDO_MDS_URL
     */
    public const FIDO_MDS_URL = 'https://mds.fidoalliance.org/';

    /**
     * Certificates check is resquested.
     *
     * @return  bool    True if it is requested
     */
    public function checkRequested(): bool;

    /**
     * Get added certificates files.
     *
     * @return  string[]    The certificates files path
     */
    public function getCertificates(): array;

    /**
     * Add a root certificate to verify new registrations.
     *
     * @param   string      $path   File path of / directory with root certificates
     */
    public function addCertificates(string $path): void;

    /**
     * Downloads root certificates from FIDO Alliance Metadata Service (MDS) to a specific folder.
     *
     * @see     https://fidoalliance.org/metadata/
     *
     * @param   string  $path       Folder path to save the certificates in PEM format.
     * @param   bool    $delete     delete certificates in the target folder before adding the new ones.
     *
     * @return  int     number of cetificates
     */
    public function queryFidoMetaDataService(string $path, bool $delete = true): int;
}