<?php

/**
 * @package     Dotclear
 *    
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\WebAuthn\Type;

/**
 * @brief   WebAuthn transports enumeration.
 *
 * https://developer.mozilla.org/en-US/docs/Web/API/PublicKeyCredentialCreationOptions#transports
 * https://developer.mozilla.org/en-US/docs/Web/API/AuthenticatorAttestationResponse/getTransports
 * 
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
enum TransportsEnum: string
{
    use EnumTrait;

    case USB      = 'usb';
    case NFC      = 'nfc';
    case BLE      = 'ble';
    case HYBRID   = 'hybrid';
    case INTERNAL = 'internal';
}
