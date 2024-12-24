<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\akismet;

use Dotclear\Core\Backend\Notices;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @brief   The module antispam filter.
 * @ingroup akismet
 */
class AntispamFilterAkismet extends SpamFilter
{
    /**
     * Filter id.
     *
     * @var     string  $id
     */
    public string $id = 'dcFilterAkismet';

    /**
     * Filter name.
     *
     * @var     string  $name
     */
    public string $name = 'Akismet';

    /**
     * Has GUI settings.
     *
     * @var     bool    $has_gui
     */
    public bool $has_gui = true;

    /**
     * Is filter active.
     *
     * @var     bool    $active
     */
    public bool $active = false;

    /**
     * Filter help resource ID.
     *
     * @var     string  $help
     */
    public ?string $help = 'akismet-filter';

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();

        if (defined('DC_AKISMET_SUPER') && constant('DC_AKISMET_SUPER') && !App::auth()->isSuperAdmin()) {
            $this->has_gui = false;
        }
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo(): void
    {
        $this->description = __('Akismet spam filter');
    }

    /**
     * Gets the status message.
     *
     * @param   string  $status         The status
     * @param   int     $comment_id     The comment identifier
     *
     * @return  string  The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return sprintf(__('Filtered by %s.'), $this->guiLink());
    }

    /**
     * Return a new akismet instance of false if API key not defined.
     *
     * @return  Akismet|false
     */
    private function akInit()
    {
        if (!My::settings()->ak_key) {
            return false;
        }

        return new Akismet(App::blog()->url(), My::settings()->ak_key);
    }

    /**
     * This method should return if a comment is a spam or not.
     *
     * If it returns true or false,
     * execution of next filters will be stoped.
     * If should return nothing to let next filters apply.
     *
     * @param   string  $type       The comment type (comment / trackback)
     * @param   string  $author     The comment author
     * @param   string  $email      The comment author email
     * @param   string  $site       The comment author site
     * @param   string  $ip         The comment author IP
     * @param   string  $content    The comment content
     * @param   int     $post_id    The comment post_id
     * @param   string  $status     The comment status
     *
     * @return  mixed
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if (($ak = $this->akInit()) === false) {
            return;
        }

        try {
            if ($ak->verify()) {
                $post = App::blog()->getPosts(['post_id' => $post_id]);

                $c = $ak->comment_check(
                    $post->getURL(),
                    $type,
                    $author,
                    $email,
                    $site,
                    $content
                );

                if ($c) {
                    $status = 'Filtered by Akismet';

                    return true;
                }
            }
        } catch (Exception) {
            // If http or akismet is dead, we don't need to know it
        }
    }

    /**
     * Train the antispam filter.
     *
     * @param   string      $status     The comment status
     * @param   string      $filter     The filter
     * @param   string      $type       The comment type
     * @param   string      $author     The comment author
     * @param   string      $email      The comment author email
     * @param   string      $site       The comment author site
     * @param   string      $ip         The comment author IP
     * @param   string      $content    The comment content
     * @param   MetaRecord  $rs         The comment record
     */
    public function trainFilter(string $status, string $filter, string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, MetaRecord $rs): void
    {
        # We handle only false positive from akismet
        if ($status === 'spam' && $filter !== 'dcFilterAkismet') {
            return;
        }

        $f = $status === 'spam' ? 'submit_spam' : 'submit_ham';

        if (($ak = $this->akInit()) === false) {
            return;
        }

        try {
            if ($ak->verify()) {
                $ak->{$f}($rs->getPostURL(), $type, $author, $email, $site, $content);
            }
        } catch (Exception) {
            // If http or akismet is dead, we don't need to know it
        }
    }

    /**
     * Filter settings.
     *
     * @param   string  $url    The GUI URL
     *
     * @return  string
     */
    public function gui($url): string
    {
        $ak_key      = My::settings()->ak_key;
        $ak_verified = null;

        if (isset($_POST['ak_key'])) {
            try {
                $ak_key = $_POST['ak_key'];

                My::settings()->put('ak_key', $ak_key, 'string');

                Notices::addSuccessNotice(__('Filter configuration have been successfully saved.'));
                Http::redirect($url);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (My::settings()->ak_key) {
            try {
                $ak          = new Akismet(App::blog()->url(), My::settings()->ak_key);
                $ak_verified = $ak->verify();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        $res = Notices::getNotices();

        $verified = [];
        if ($ak_verified !== null) {
            if ($ak_verified) {
                $verified[] = (new Img('images/check-on.svg'))->class(['mark','mark-check-on']);
                $verified[] = (new Text(null, __('API key verified')));
            } else {
                $verified[] = (new Img('images/check-off.svg'))->class(['mark','mark-check-off']);
                $verified[] = (new Text(null, __('API key not verified')));
            }
        }

        $res .= (new Form('akismet_form'))
            ->action(Html::escapeURL($url))
            ->method('post')
            ->fields([
                (new Fieldset())->items([
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                    (new Para())->items([
                        (new Input('ak_key'))
                            ->size(20)
                            ->maxlength(128)
                            ->value(Html::escapeHTML($ak_key))
                            ->required(true)
                            ->placeholder(__('Akismet API key'))
                            ->label(
                                (new Label(
                                    (new Text('span', '*'))->render() . __('Akismet API key:'),
                                    Label::INSIDE_TEXT_BEFORE
                                ))
                            )
                            ->title(__('Required field')),
                    ]),
                    (new Para())->items($verified),
                    (new Para())->items([
                        (new Link())
                            ->href('https://akismet.com/"')
                            ->text(__('Get your own API key')),
                    ]),
                    (new Para())->items([
                        (new Submit('akismet_save', __('Save'))),
                        App::nonce()->formNonce(),
                    ]),
                ]),
            ])
        ->render();

        return $res;
    }
}
