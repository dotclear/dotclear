<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Data;

use Dotclear\Helper\WebAuthn\Exception\CertificatesException;
use Dotclear\Interface\Helper\WebAuthn\Data\CertificatesInterface;
use Dotclear\Interface\Helper\WebAuthn\Util\ByteBufferInterface;

/**
 * @brief   WebAuthn attestation certificats helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Certificates implements CertificatesInterface
{
    /**
     * Certificats path stack.
     *
     * @var     string[]   $certificates
     */
    protected array $certificates = [];

    /**
     * Check if some certificates have been added.
     *
     * Even if no certificates found !
     */
    protected bool  $check_requested = false;

    /**
     * Load services from container.
     *
     * @param   ByteBufferInterface     $buffer     The byte buffer interface
     */
    public function __construct(
        protected ByteBufferInterface $buffer
    ) {
    }

    public function checkRequested(): bool
    {
        return $this->check_requested;
    }

    public function getCertificates(): array
    {
        return $this->certificates;
    }

    public function addCertificates(string $path): void
    {
        $this->check_requested = true;

        $path = rtrim(trim($path), '\\/');
        if (is_dir($path) && ($dirs = scandir($path)) !== false) {
            foreach ($dirs as $ca) {
                if (is_file($path . DIRECTORY_SEPARATOR . $ca) && in_array(strtolower(pathinfo($ca, PATHINFO_EXTENSION)), static::DEFAULT_CERTIFICATES_EXTENTIONS)) {
                    $this->addCertificates($path . DIRECTORY_SEPARATOR . $ca);
                }
            }
        } elseif (is_file($path) && !in_array(realpath($path), $this->certificates) && ($path = realpath($path)) !== false) {
            $this->certificates[] = $path;
        }
    }

    public function queryFidoMetaDataService(string $path, bool $delete = true): int
    {
        $url = static::FIDO_MDS_URL;
        $raw = null;
        if (function_exists('curl_init') && ($ch = curl_init($url)) !== false) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'dotclear.org - Dotclear webauthn');
            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $raw = file_get_contents($url);
        }

        $path = rtrim((string) realpath($path), '\\/');
        if ($path !== '' && !is_dir($path)) {
            throw new CertificatesException('Invalid folder path for query FIDO Alliance Metadata Service');
        }

        if (!is_string($raw)) {
            throw new CertificatesException('Unable to query FIDO Alliance Metadata Service');
        }

        $jwt = explode('.', $raw);
        if (count($jwt) !== 3) {
            throw new CertificatesException('Invalid JWT from FIDO Alliance Metadata Service');
        }

        if ($delete && ($dirs = scandir($path)) !== false) {
            foreach ($dirs as $ca) {
                if (str_ends_with($ca, '.pem') && unlink($path . DIRECTORY_SEPARATOR . $ca) === false) {
                    throw new CertificatesException('Cannot delete certs in folder for FIDO Alliance Metadata Service');
                }
            }
        }

        [$header, $payload, $hash] = $jwt;
        $payload                   = $this->buffer->fromBase64Url($payload)->getJson();

        $count = 0;
        if (is_object($payload) && property_exists($payload, 'entries') && is_array($payload->entries)) {
            foreach ($payload->entries as $entry) {
                if (is_object($entry) && property_exists($entry, 'metadataStatement') && is_object($entry->metadataStatement)) {
                    $description                 = $entry->metadataStatement->description                 ?? null;  // @phpstan-ignore-line
                    $attestationRootCertificates = $entry->metadataStatement->attestationRootCertificates ?? null;  // @phpstan-ignore-line

                    if ($description && $attestationRootCertificates) {
                        // create filename
                        $certFilename = preg_replace('/[^a-z0-9]/i', '_', (string) $description);
                        $certFilename = trim((string) preg_replace('/\_{2,}/i', '_', (string) $certFilename), '_') . '.pem';
                        $certFilename = strtolower($certFilename);

                        // add certificate
                        $certContent = $description . "\n";
                        $certContent .= str_repeat('-', mb_strlen((string) $description)) . "\n";

                        foreach ($attestationRootCertificates as $attestationRootCertificate) {
                            $attestationRootCertificate = str_replace(["\n", "\r", ' '], '', trim((string) $attestationRootCertificate));
                            $count++;
                            $certContent .= "\n-----BEGIN CERTIFICATE-----\n";
                            $certContent .= chunk_split($attestationRootCertificate, 64, "\n");
                            $certContent .= "-----END CERTIFICATE-----\n";
                        }

                        if (file_put_contents($path . DIRECTORY_SEPARATOR . $certFilename, $certContent) === false) {
                            throw new CertificatesException('unable to save certificate from FIDO Alliance Metadata Service');
                        }
                    }
                }
            }
        }

        return $count;
    }
}
