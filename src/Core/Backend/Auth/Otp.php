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
use Dotclear\Helper\Otp as OtpHelper;

/**
 * @brief   Dotclear backend otp class.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Otp extends OtpHelper
{
    /**
     * Create backend Otp instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this
            // Add a leeway of 10 seconds on code validation
            ->setLeeway(10)
            // Set domain has admin hostname
            ->setDomain((string) parse_url(App::config()->adminUrl(), PHP_URL_HOST))
            // Set QR code size to 200px
            ->setQrCodeSize(200);
    }

    public function getCredential(): void
    {
        // @phpstan-ignore-next-line Prevent error from exotic upgrades as we play on Auth page
        if (!method_exists(App::class, 'credential') 
            || !in_array(App::db()->con()->prefix() . App::credential()::CREDENTIAL_TABLE_NAME, App::db()->con()->schema()->getTables())
        ) {
            $this->setData([]);

            return;
        }

        $params = [
            'user_id'         => $this->getUser(),
            'credential_type' => $this->getType(),
        ];

        $rs = App::credential()->getCredentials($params);
        if ($rs->isEmpty()) {
            $this->setData([]);
            $this->setCredential();
        } else {
            $this->setData($rs->getAllData());
        }
    }

    public function setCredential(): void
    {
        $this->delCredential();

        $cur = App::credential()->openCredentialCursor();
        $cur->setField('user_id', $this->getUser());
        $cur->setField('credential_type', $this->getType());
        $cur->setField('credential_data', new ArrayObject($this->data));

        App::credential()->setCredential($this->getUser(), $cur);
    }

    public function delCredential(): void
    {
        App::credential()->delCredentials(
            $this->getType(),
            '',
            $this->getUser(),
            true
        );
    }
}
