<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Url as BackendUrl;
use Exception;

/**
 * @brief   URL Handler for upgrade urls.
 *
 * @since   2.29
 */
class Url extends BackendUrl
{
    /**
     * @var    string  Default upgrade index page
     */
    public const INDEX = 'upgrade.php';

    /**
     * @var    string  Default backend index page
     */
    public const BACKEND = 'index.php';

    /**
     * Constructs a new instance.
     *
     * @throws  Exception   If not in upgrade context
     */
    public function __construct()
    {
        if (!App::task()->checkContext('UPGRADE')) {
            throw new Exception('Application is not in upgrade context.', 500);
        }

        $this->urls = new ArrayObject();

        // set required URLs
        $this->register('upgrade.auth', 'Auth');
        $this->register('upgrade.logout', 'Logout');

        $this->register('upgrade.home', 'Home');
        $this->register('upgrade.upgrade', 'Upgrade');
        $this->register('upgrade.backup', 'Backup');
        $this->register('upgrade.langs', 'Langs');
        $this->register('upgrade.plugins', 'Plugins');
        $this->register('upgrade.cache', 'Cache');
        $this->register('upgrade.files', 'Files');

        // we don't care of admin process for FileServer
        $this->register('load.plugin.file', self::INDEX, ['pf' => 'dummy.css']);
        $this->register('load.var.file', self::INDEX, ['vf' => 'dummy.json']);

        // from backend
        $this->register('admin.home', self::BACKEND);
        $this->register('admin.rest', self::BACKEND, ['process' => 'Rest']);
    }

    /**
     * Set default upgrade URLs handlers.
     */
    public function setDefaultUrls(): void
    {
    }
}
