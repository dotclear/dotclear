<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Helper\Otp as OtpHelper;

/**
 * @brief   Dotclear upgrade otp class.
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
            ->setDomain((string) parse_url(App::config()->adminUrl(), PHP_URL_HOST));
    }

    public function getCredential(): void
    {
        // @phpstan-ignore-next-line Prevent error from exotic upgrades as we play on Auth page
        if (!method_exists(App::class, 'credential') || !in_array(App::con()->prefix() . App::credential()::CREDENTIAL_TABLE_NAME, App::con()->schema()->getTables())) {
            $this->setData([]);
            $this->setCredential();

            return;
        }

        $params = [
            'user_id'         => $this->getUser(),
            'credential_type' => $this->getType(),
            'limit'           => 1, // Should have only one record
        ];

        $rs = App::credential()->getCredentials($params);
        if ($rs->isEmpty()) {
            $this->setData([]);
            $this->setCredential();
        } else {
            $this->decodeData((string) $rs->f('credential_data'));
        }
    }

    public function setCredential(): void
    {
        // do nothing on upgrade utility;
    }

    public function delCredential(): void
    {
        // do nothing on upgrade utility;
    }
}
