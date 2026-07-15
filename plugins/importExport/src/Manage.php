<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Dd;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Dl;
use Dotclear\Helper\Html\Form\Dt;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module manage process.
 * @ingroup importExport
 */
class Manage
{
    use TraitProcess;

    protected static string $type;

    /**
     * @var array<string, array<array-key, class-string>> $modules
     */
    protected static array $modules;

    protected static Module $module;

    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE));

        /**
         * @var ArrayObject<string, array<array-key, class-string>>
         */
        $modules = new ArrayObject(['import' => [], 'export' => []]);

        # --BEHAVIOR-- importExportModules -- ArrayObject
        App::behavior()->callBehavior('importExportModulesV2', $modules);

        self::$modules = $modules->getArrayCopy();

        self::$type = '';
        if (!empty($_REQUEST['type'])
            && is_string($_REQUEST['type'])
            && in_array($_REQUEST['type'], ['export', 'import'])
        ) {
            self::$type = $_REQUEST['type'];
        }

        /**
         * @var null|Module|false
         */
        $module = $_REQUEST['module'] ?? false;
        if (self::$type !== ''
            && $module
            && isset(self::$modules[self::$type])
            && in_array($module, self::$modules[self::$type])
        ) {
            self::$module = new $module();
            self::$module->init();
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (self::$type !== '' && isset(self::$module) && !empty($_REQUEST['do'])) {
            try {
                $do = is_string($do = $_REQUEST['do']) ? $do : '';

                self::$module->process($do);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        App::backend()->page()->openModule(
            My::name(),
            My::cssLoad('style') .
            App::backend()->page()->jsJson('ie_msg', ['please_wait' => __('Please wait...')]) .
            My::jsLoad('script')
        );

        if (self::$type !== '' && isset(self::$module)) {
            echo
            App::backend()->page()->breadcrumb(
                [
                    __('Plugins')                         => '',
                    My::name()                            => App::backend()->getPageURL(),
                    Html::escapeHTML(self::$module->name) => '',
                ]
            ) .
            App::backend()->notices()->getNotices();

            echo (new Div('ie-gui'))->items([
                (new Capture(self::$module->gui(...))),
            ])
            ->render();
        } else {
            echo
            App::backend()->page()->breadcrumb(
                [
                    __('Plugins') => '',
                    My::name()    => '',
                ]
            ) .
            App::backend()->notices()->getNotices();

            $list = [];

            foreach (self::$modules['import'] as $id) {
                if (is_subclass_of($id, Module::class)) {
                    $module = new $id();

                    $list[] = (new Dt())->items([(new Link())->href($module->getURL(true))->text($module->name)]);
                    $list[] = (new Dd())->text(Html::escapeHTML($module->description));
                }
            }

            echo (new Set())->items([
                (new Text('h3', __('Import'))),
                ((new Dl())->class('modules')->items($list)),
            ])
            ->render();

            echo (new Set())->items([
                (new Text('h3', __('Export'))),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(null, sprintf(
                            __('Export functions are in the page %s.'),
                            (new Link())
                                ->href(App::backend()->url()->get('admin.plugin.maintenance', ['tab' => 'backup']) . '#backup')
                                ->text(__('Maintenance'))
                            ->render(),
                        ))),
                    ]),
            ])
            ->render();
        }

        App::backend()->page()->helpBlock('import');

        App::backend()->page()->closeModule();
    }
}
