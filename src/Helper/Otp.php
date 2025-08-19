<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper;

use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Network\HttpClient;
use DateTimeImmutable;
use Exception;

/**
 * @brief   One time password (otp) instance
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
abstract class Otp
{
    /**
     * Base32 dictionary.
     *
     * @var     string  BASE32_DICTIONARY
     */
    final public const BASE32_DICTIONARY = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=';

    /**
     * Default code validity period.
     *
     * @var     int     DEFAULT_PERIOD
     */
    final public const DEFAULT_PERIOD = 30;

    /**
     * Default code length.
     *
     * @var     int     DEFAULT_DIGITS
     */
    final public const DEFAULT_DIGITS = 6;

    /**
     * Default code hmac cryptographic algorithm.
     *
     * @var     string  DEFAULT_ALGORITHM
     */
    final public const DEFAULT_ALGORITHM = 'sha1';

    /**
     * The OTP URL shema.
     *
     * @var     string  OTP_URL
     */
    final public const OTP_URL = 'otpauth://%s/%s?%s';

    /**
     * Default secret length.
     *
     * @var     int     SECRET_LENGTH
     */
    public const SECRET_LENGTH = 32;

    /**
     * The image generator service url.
     *
     * @var     string  QRCODE_SERVICE_URL
     */
    public const QRCODE_SERVICE_URL = 'https://image-charts.com/chart?';

    /**
     * Base 32 dictionary.
     *
     * @var     array<string>
     */
    protected readonly array $base32_map ;

    /**
     * Reverse base32 dictionary.
     *
     * @var     array<string, int>
     */
    protected readonly array $base32_lookup;

    /**
     * The OTP type.
     *
     * @var     string  $otp_type
     */
    protected readonly string $otp_type;

    /**
     * The issuer domain.
     *
     * @var     string  $domain
     */
    protected string $domain = 'Undefined';

    /**
     * The user (ID).
     *
     * @var     string $user
     */
    protected string $user = '';

    /**
     * The QR code image correction level.
     *
     * @var     string  $qrcode_correction
     */
    protected string $qrcode_correction = 'L';

    /**
     * The QR code image size.
     *
     * @var     int     $qrcode_size
     */
    protected int $qrcode_size = 200;

    /**
     * The QR code image margin.
     *
     * @var int     $qrcode_margin
     */
    protected int $qrcode_margin = 1;

    /**
     * The QR code image title (for HTML render).
     *
     * @var     string  $qrcode_title
     */
    protected string $qrcode_title = '';

    /**
     * User credential data.
     *
     * @var     array<string, mixed>
     */
    protected array $data = [];

    /**
     * The leeway.
     *
     * @var     int     $leeway
     */
    protected int $leeway = 0;

    /**
     * Create Otp instance.
     *
     * @param   bool    $is_totp    Otp type (False for Hotp, default to Totp)
     */
    public function __construct(bool $is_totp = true)
    {
        $this->otp_type      = $is_totp ? 'totp' : 'hotp';
        $this->base32_map    = str_split(static::BASE32_DICTIONARY);
        $this->base32_lookup = array_flip($this->base32_map);
    }

    /**
     * Get Otp type.
     *
     * @return  string  totp or hotp;
     */
    public function getType(): string
    {
        return $this->otp_type;
    }

    /**
     * Set the issuer domain.
     *
     * This should be the host, but can be whatever you want.
     *
     * @param   string  $domain     The issuer domain
     *
     * @return  Otp     Self instance
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get the issuer domain.
     *
     * @return  string  The issuer domain
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Set the user.
     *
     * This should be the user ID
     *
     * @param   string  $user   The user
     *
     * @return  Otp     Self instance
     */
    public function setUser(string $user): self
    {
        $this->user = $user;
        $this->getCredential(); // load user credential

        return $this;
    }

    /**
     * Get the user.
     *
     * @return  string  The user
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * Get current timestamp.
     *
     * @return  int     The Current timestamp
     */
    public function getTimestamp(): int
    {
        return (int) (new DateTimeImmutable('now'))->getTimestamp();
    }

    /**
     * Set QR code correction level.
     *
     * @param   string  $correction     The QR code correction level
     *
     * @return  Otp     Self instance
     */
    public function setQrCodeCorrection(string $correction): self
    {
        $this->qrcode_correction = $correction;

        return $this;
    }

    /**
     * Set QR code size (in px).
     *
     * @param   int     $size   The QR code size
     *
     * @return  Otp     Self instance
     */
    public function setQrCodeSize(int $size): self
    {
        $this->qrcode_size = $size;

        return $this;
    }

    /**
     * Set QR code margin.
     *
     * @param   int     $margin     The QR code margin
     *
     * @return  Otp     Self instance
     */
    public function setQrCodeMargin(int $margin): self
    {
        $this->qrcode_margin = $margin;

        return $this;
    }

    /**
     * Set QR code image title (for HTML render).
     *
     * @param   string  $title  The QR code image title
     *
     * @return  Otp     Self instance
     */
    public function setQrCodeImageTitle(string $title): self
    {
        $this->qrcode_title = $title;

        return $this;
    }

    /**
     * Get QR code mime type.
     *
     * @return  string  The mime type
     */
    public function getQrCodeMimeType(): string
    {
        return 'image/png';
    }

    /**
     * Get QR code image content.
     *
     * @return  string  The QR code image content
     */
    public function getQrCodeImageData(): string
    {
        return 'data:' . $this->getQrCodeMimeType() . ';base64,' . base64_encode($this->queryQrCodeService());
    }

    /**
     * Get QR code image HTML code.
     *
     * @return  Img     The QR code image HTML Component instance
     */
    public function getQrCodeImageHtml(): Img
    {
        return (new Img($this->getQrCodeImageData()))->alt($this->qrcode_title ?: __('Two Factors authentication'));
    }

    /**
     * Query image generator service.
     *
     * @return  string  The image content
     */
    protected function queryQrCodeService(): string
    {
        $params = [
            'cht' => 'qr',
            'chs' => ceil($this->qrcode_size / 2) . 'x' . ceil($this->qrcode_size / 2),
            'chld' => $this->qrcode_correction . '|' . $this->qrcode_margin,
            'chl' => $this->getUrl(),
        ];

        $data = HttpClient::quickGet(static::QRCODE_SERVICE_URL . http_build_query($params));

        return (string) $data;
    }

    /**
     * Load credential from database.
     *
     * If theres no credential yet, 
     * this MUST populate credential instance with defautl values.
     */
    abstract public function getCredential(): void;

    /**
     * Save current credential.
     */
    abstract public function setCredential(): void;

    /**
     * Delete current credential.
     */
    abstract public function delCredential(): void;

    /**
     * Fill credential from array.
     *
     * @param   array<string, mixed>    $data   The credential data
     */
    public function setData(array $data): void
    {
        $this->data = [
            'secret'    => isset($data['secret']) && is_string($data['secret']) ? $data['secret'] : $this->createSecret(),
            'counter'   => isset($data['counter']) && is_numeric($data['counter']) ? (int) $data['counter'] : 0, // hotp
            'period'    => isset($data['period']) && is_numeric($data['period']) ? (int) $data['period'] : static::DEFAULT_PERIOD, // totp
            'digits'    => isset($data['digits']) && is_numeric($data['digits']) ? (int) $data['digits'] : static::DEFAULT_DIGITS,
            'algorithm' => isset($data['algorithm']) && is_string($data['algorithm']) ? $data['algorithm'] : static::DEFAULT_ALGORITHM,
            'verified'  => isset($data['verified']) && !empty($data['verified']),
        ];
    }

    /**
     * Encode credential data for database.
     *
     * @return  string  The encoded data
     */
    public function encodeData(): string
    {
        return (string) json_encode($this->data);
    }

    /**
     * Decode credentail data from database entry.
     *
     * From data encoded with self::encodeData
     * This populate current instance with theses values
     *
     * @param   string  $data   The database encoded credential data
     */
    public function decodeData(string $data): void
    {
        $data  = json_decode($data, true);
        $this->setData(is_array($data) ? $data : []);
    }

    /**
     * Get crendetail secret.
     *
     * @return  string  The credential secret
     */
    public function getSecret(): string
    {
        return $this->data['secret'] ?? $this->createSecret();
    }

    /**
     * Decode base32 encoded secret.
     *
     * @return  string  The decoded secret
     */
    public function decodeSecret(): string
    {
        return $this->base32Decode($this->getSecret());
    }

    /**
     * Create a new base32 secret.
     *
     * @return  string  The base32 encoded secret
     */
    public function createSecret(): string
    {
        return $this->base32Encode(random_bytes(abs(static::SECRET_LENGTH) ?: 16));
    }

    /**
     * Get HOTP credential counter.
     *
     * Not implemented.
     *
     * @return  int     The credential counter
     */
    public function getCounter(): int
    {
        return $this->data['counter'] ?? 0;
    }

    /**
     * Get credential validity period (in seconds).
     *
     * Google Authenticator default period is 30 seconds.
     *
     * @return  int     The credential validity period
     */
    public function getPeriod(): int
    {
        return $this->data['period'] ?? static::DEFAULT_PERIOD;
    }

    /**
     * Get credential code length.
     *
     * Google Authenticator only support 6 digits.
     *
     * @return  int     The credential code length
     */
    public function getDigits(): int
    {
        return $this->data['digits'] ?? static::DEFAULT_DIGITS;
    }

    /**
     * Get hmac encryption algorithm.
     *
     * Google Authenticator only support sha1 encryption.
     *
     * @return  string  The hmac encryption algorithm
     */
    public function getAlgorithm(): string
    {
        return $this->data['algorithm'] ?? static::DEFAULT_ALGORITHM;
    }

    /**
     * Check if a credential was verified.
     *
     * @return  bool    True if it was verified
     */
    public function isVerified(): bool
    {
        return (bool) ($this->data['verified'] ?? false);
    }

    /**
     * Set a credential as verified.
     */
    public function setAsVerified(): void
    {
        $this->data['verified'] = true;
    }

    /**
     * Set a leeway (in seconds) for code time.
     *
     * Leeway must be lower than credentail period.
     *
     * @param   int     $seconds    The leeway
     *
     * @return  Otp     Self instance
     */
    public function setLeeway(int $seconds): self
    {
        if ($seconds >= 0) {
            $this->leeway = $seconds;
        }

        return $this;
    }

    /**
     * Get leeway (in seconds).
     *
     * @return  int     The leeway
     */
    public function getLeeway(): int
    {
        return $this->leeway;
    }

    /**
     * Get a code.
     *
     * @param   int     $input  The timestamp (topt) or counter (hotp)
     *
     * @return  string  The code
     */
    public function getCode(int $input): string
    {
        $timecode = $this->getType() === 'htop' ? $input : (int) floor($input / $this->getPeriod());

        return $this->generateCode($timecode);
    }

    /**
     * Verify a code.
     *
     * @param   string  $code   The code to verify
     *
     * @return  bool    True if code is valid
     */
    public function verifyCode(string $code): bool
    {
        $verified = false;
        if ($this->getType() === 'hotp') {
            $verifiy = hash_equals($this->getCode($this->getCounter()), $code);
        } else {
            $ts    = $this->getTimestamp();
            $lower = $ts - $this->getLeeway();

            if ($this->getLeeway() === 0) {
                $verified = $this->compareTimeCode($ts, $code);
            } else {
                if ($this->getLeeway() >= $this->getPeriod() || $lower < 0) {
                    throw new Exception('Invalid time leeway');
                }

                $verified = $this->compareTimeCode($lower, $code)
                    || $this->compareTimeCode($ts, $code)
                    || $this->compareTimeCode($ts + $this->getLeeway(), $code);
            }
        }

        if (!$verified) {
            // Avoid time attacks by measuring server response time during user existence check
            sleep(random_int(2, 5));
        }

        return $verified;
    }

    /**
     * Compare given Totp code with instance Totp code.
     *
     * @param   int     $timestamp  The time to verifiy code
     * @param   string  $code       The code to compare
     *
     * @return  bool    True if codes match
     */
    protected function compareTimeCode(int $timestamp, string $code): bool
    {
        if (hash_equals($this->getCode($timestamp), $code)) {
            // if verification succeeded, set credential as verified
            $this->setAsVerified();
            // then store update credential values
            $this->setCredential();

            return true;
        }

        return false;
    }

    /**
     * Get otpauth URL.
     *
     * @see https://github.com/google/google-authenticator/wiki/Key-Uri-Format
     *
     * @return  string  The otpauth URL
     */
    public function getUrl(): string
    {
        $params = [
            'secret' => $this->getSecret(),
            'issuer' => $this->getDomain(),
        ];

        // optionnal parameters
        if ($this->getDigits() !== static::DEFAULT_DIGITS) {
            $params['digits'] = $this->getDigits();
        }
        if ($this->getPeriod() !== static::DEFAULT_PERIOD) {
            $params['period'] = $this->getPeriod();
        }
        if ($this->getAlgorithm() !== static::DEFAULT_ALGORITHM) {
            $params['algorithm'] = strtoupper($this->getAlgorithm());
        }
        // hotp parameter
        if ($this->getType() === 'hotp') {
            $params['counter'] = (string) $this->getCounter();
        }

        return sprintf(static::OTP_URL,
            $this->getType(),
            rawurlencode($this->getDomain() . ':' . $this->getUser()),
            http_build_query($params, '', '&')
        );
    }

    /**
     * Generate code.
     *
     * Input could be a timecode for Totp or counter for Hotp.
     *
     * @param   int     $input  The timecode or counter
     *
     * @return  string  The code
     */
    protected function generateCode(int $input): string
    {
        $hash = hash_hmac($this->getAlgorithm(), $this->intToByteString($input), $this->decodeSecret(), true);
        if (($unpacked = unpack('C*', $hash)) === false) {
            throw new Exception('Invalid cryptographic data.');
        }

        $hmac   = array_values($unpacked);
        $offset = ($hmac[count($hmac) - 1] & 0xF);
        $code   = ($hmac[$offset] & 0x7F) << 24 | ($hmac[$offset + 1] & 0xFF) << 16 | ($hmac[$offset + 2] & 0xFF) << 8 | ($hmac[$offset + 3] & 0xFF);
        $otp    = $code % (10 ** $this->getDigits());

        return str_pad((string) $otp, $this->getDigits(), '0', STR_PAD_LEFT);
    }

    /**
     * Convert integer to bytes string.
     *
     * @param   int     $int    The integer to convert
     *
     * @return  string  The converted string
     */
    protected function intToByteString(int $int): string
    {
        $result = [];
        while ($int !== 0) {
            $result[] = chr($int & 0xFF);
            $int >>= 8;
        }

        return str_pad(implode('', array_reverse($result)), 8, "\000", STR_PAD_LEFT);
    }

    /**
     * Encode string to base32.
     *
     * @param   string  $input      The string to encode
     * @param   bool    $padding    Remove padding
     *
     * @return  string  The base32 encoded string
     */
    public function base32Encode(string $input, bool $padding = true): string
    {
        if(empty($input)) {
            return '';
        }

        $input = str_split($input);
        $binaryString = "";
        for($i = 0; $i < count($input); $i++) {
            $binaryString .= str_pad(base_convert((string) ord($input[$i]), 10, 2), 8, '0', STR_PAD_LEFT);
        }
        $fiveBitBinaryArray = str_split($binaryString, 5);
        $base32 = "";
        $i=0;
        while($i < count($fiveBitBinaryArray)) {    
            $base32 .= $this->base32_map[(int) base_convert(str_pad($fiveBitBinaryArray[$i], 5,'0'), 2, 10)];
            $i++;
        }
        if($padding && ($x = strlen($binaryString) % 40) != 0) {
            if($x == 8) $base32 .= str_repeat($this->base32_map[32], 6);
            else if($x == 16) $base32 .= str_repeat($this->base32_map[32], 4);
            else if($x == 24) $base32 .= str_repeat($this->base32_map[32], 3);
            else if($x == 32) $base32 .= $this->base32_map[32];
        }

        return $base32;
    }

    /**
     * Decode base32 to string.
     *
     * @param   string  $input      The base32 string to decode
     *
     * @return  string  The decoded string
     */
    public function base32Decode(string $input): string
    {
        if(empty($input)) {
            return '';
        }

        $paddingCharCount = substr_count($input, $this->base32_map[32]);
        $allowedValues    = array(6,4,3,1,0);
        if(!in_array($paddingCharCount, $allowedValues)) {
            return '';
        }

        for($i=0; $i<4; $i++){ 
            if($paddingCharCount == $allowedValues[$i] && 
                substr($input, -($allowedValues[$i])) != str_repeat($this->base32_map[32], $allowedValues[$i])) {
                return '';
            }
        }

        $input  = str_replace('=','', $input);
        $input  = str_split($input);
        $binary = "";
        for($i=0; $i < count($input); $i = $i+8) {
            $x = "";
            if(!in_array($input[$i], $this->base32_map)) {
                return '';
            }

            for($j=0; $j < 8; $j++) {
                $x .= str_pad(base_convert((string) @$this->base32_lookup[@$input[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightbits = str_split($x, 8);
            for($z = 0; $z < count($eightbits); $z++) {
                $binary .= ( ($y = chr((int) base_convert($eightbits[$z], 2, 10))) || ord($y) == 48 ) ? $y:"";
            }
        }

        return $binary;
    }
} 