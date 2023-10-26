<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam\Filters;

use Dotclear\Core\Backend\Notices;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\Antispam;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;
use form;

/**
 * @brief   The module words spam filter.
 * @ingroup antispam
 */
class Words extends SpamFilter
{
    /**
     * Filter id.
     *
     * @var     string  $id
     */
    public string $id = 'dcFilterWords';

    /**
     * Filter name.
     *
     * @var     string  $name
     */
    public string $name = 'Bad Words';

    /**
     * Filter has settings GUI?
     *
     * @var     bool    $has_gui
     */
    public bool $has_gui = true;

    /**
     * Filter help ID.
     *
     * @var     null|string     $help
     */
    public ?string $help = 'words-filter';

    /**
     * Table name.
     *
     * @var     string  $table
     */
    private string $table;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->table = App::con()->prefix() . Antispam::SPAMRULE_TABLE_NAME;
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo(): void
    {
        $this->description = __('Words Blocklist');
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
        return sprintf(__('Filtered by %1$s with word %2$s.'), $this->guiLink(), '<em>' . $status . '</em>');
    }

    /**
     * This method should return if a comment is a spam or not.
     *
     * If it returns true or false, execution of next filters will be stoped.
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
        $str = $author . ' ' . $email . ' ' . $site . ' ' . $content;

        $rs = $this->getRules();

        while ($rs->fetch()) {
            $word = $rs->rule_content;

            if (str_starts_with($word, '/') && str_ends_with($word, '/')) {
                $reg = substr(substr($word, 1), 0, -1);
            } else {
                $reg = preg_quote($word, '/');
                $reg = '(^|\s+|>|<)' . $reg . '(>|<|\s+|\.|$)';
            }

            if (preg_match('/' . $reg . '/msiu', $str)) {
                $status = $word;

                return true;
            }
        }
    }

    /**
     * Filter settings.
     *
     * @param   string  $url    The GUI URL
     *
     * @return  string
     */
    public function gui(string $url): string
    {
        # Create list
        if (!empty($_POST['createlist'])) {
            try {
                $this->defaultWordsList();
                Notices::addSuccessNotice(__('Words have been successfully added.'));
                Http::redirect($url);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Adding a word
        if (!empty($_POST['swa'])) {
            $globalsw = !empty($_POST['globalsw']) && App::auth()->isSuperAdmin();

            try {
                $this->addRule($_POST['swa'], $globalsw);
                Notices::addSuccessNotice(__('Word has been successfully added.'));
                Http::redirect($url);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Removing spamwords
        if (!empty($_POST['swd']) && is_array($_POST['swd'])) {
            try {
                $this->removeRule($_POST['swd']);
                Notices::addSuccessNotice(__('Words have been successfully removed.'));
                Http::redirect($url);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        /* DISPLAY
        ---------------------------------------------- */
        $res = '<form action="' . Html::escapeURL($url) . '" method="post" class="fieldset">' .
        '<p><label class="classic" for="swa">' . __('Add a word ') . '</label> ' . form::field('swa', 20, 128);

        if (App::auth()->isSuperAdmin()) {
            $res .= '<label class="classic" for="globalsw">' . form::checkbox('globalsw', 1) .
            __('Global word (used for all blogs)') . '</label> ';
        }

        $res .= App::nonce()->getFormNonce() .
        '</p>' .
        '<p><input type="submit" value="' . __('Add') . '"/></p>' .
            '</form>';

        $rs = $this->getRules();
        if ($rs->isEmpty()) {
            $res .= '<p><strong>' . __('No word in list.') . '</strong></p>';
        } else {
            $res .= '<form action="' . Html::escapeURL($url) . '" method="post" class="fieldset">' .
            '<h3>' . __('List of bad words') . '</h3>' .
                '<div class="antispam">';

            $res_global = '';
            $res_local  = '';
            while ($rs->fetch()) {
                $disabled_word = false;

                $p_style = '';

                if (!$rs->blog_id) {
                    $disabled_word = !App::auth()->isSuperAdmin();
                    $p_style .= ' global';
                }

                $item = '<p class="' . $p_style . '"><label class="classic" for="word-' . $rs->rule_id . '">' .
                form::checkbox(
                    ['swd[]', 'word-' . $rs->rule_id],
                    $rs->rule_id,
                    [
                        'disabled' => $disabled_word,
                    ]
                ) . ' ' .
                Html::escapeHTML($rs->rule_content) .
                    '</label></p>';

                if ($rs->blog_id) {
                    // local list
                    if ($res_local == '') {
                        $res_local = '<h4>' . __('Local words (used only for this blog)') . '</h4>';
                    }
                    $res_local .= $item;
                } else {
                    // global list
                    if ($res_global == '') {
                        $res_global = '<h4>' . __('Global words (used for all blogs)') . '</h4>';
                    }
                    $res_global .= $item;
                }
            }
            $res .= '<div class="local">' . $res_local . '</div>';
            $res .= '<div class="global">' . $res_global . '</div>';

            $res .= '</div>' .
            '<p>' . form::hidden(['spamwords'], 1) .
            App::nonce()->getFormNonce() .
            '<input class="submit delete" type="submit" value="' . __('Delete selected words') . '"/></p>' .
                '</form>';
        }

        if (App::auth()->isSuperAdmin()) {
            $res .= '<form action="' . Html::escapeURL($url) . '" method="post">' .
            '<p><input type="submit" value="' . __('Create default wordlist') . '" />' .
            form::hidden(['spamwords'], 1) .
            form::hidden(['createlist'], 1) .
            App::nonce()->getFormNonce() . '</p>' .
                '</form>';
        }

        return $res;
    }

    /**
     * Gets the rules.
     *
     * @return  MetaRecord  The rules.
     */
    private function getRules(): MetaRecord
    {
        $sql = new SelectStatement();

        return $sql
            ->columns([
                'rule_id',
                'blog_id',
                'rule_content',
            ])
            ->from($this->table)
            ->where('rule_type = ' . $sql->quote('word'))
            ->and($sql->orGroup([
                'blog_id = ' . $sql->quote(App::blog()->id()),
                'blog_id IS NULL',
            ]))
            ->order([
                'blog_id ASC',
                'rule_content ASC',
            ])
            ->select() ?? MetaRecord::newFromArray([]);
    }

    /**
     * Adds a rule.
     *
     * @param   string  $content    The content
     * @param   bool    $general    The general flag
     *
     * @throws  Exception
     */
    private function addRule(string $content, bool $general = false): void
    {
        $sql = new SelectStatement();

        if (!$general) {
            $sql->and('blog_id = ' . $sql->quote(App::blog()->id()));
        }

        $rs = $sql
            ->column('rule_id')
            ->from($this->table)
            ->where('rule_type = ' . $sql->quote('word'))
            ->and('rule_content = ' . $sql->quote($content))
            ->select();

        if ($rs && !$rs->isEmpty() && !$general) {
            throw new Exception(__('This word exists'));
        }

        $cur               = App::con()->openCursor($this->table);
        $cur->rule_type    = 'word';
        $cur->rule_content = (string) $content;

        if ($general && App::auth()->isSuperAdmin()) {
            $cur->blog_id = null;
        } else {
            $cur->blog_id = App::blog()->id();
        }

        if ($rs && !$rs->isEmpty() && $general) {
            $sql = new UpdateStatement();
            $sql
                ->where('rule_id = ' . (string) $rs->rule_id)
                ->update($cur);
        } else {
            $sql = new SelectStatement();
            $run = $sql
                ->column($sql->max('rule_id'))
                ->from($this->table)
                ->select();
            $max = $run ? $run->f(0) : 0;

            $cur->rule_id = $max + 1;
            $cur->insert();
        }
    }

    /**
     * Removes a rule.
     *
     * @param   mixed   $ids    The rules identifiers
     */
    private function removeRule($ids): void
    {
        $sql = new DeleteStatement();

        if (is_array($ids)) {
            foreach ($ids as $i => $v) {
                $ids[$i] = (int) $v;
            }
        } else {
            $ids = [(int) $ids];
        }

        if (!App::auth()->isSuperAdmin()) {
            $sql->and('blog_id = ' . $sql->quote(App::blog()->id()));
        }

        $sql
            ->from($this->table)
            ->where('rule_id = ' . $sql->in($ids))
            ->delete();
    }

    /**
     * Set the default word list.
     */
    public function defaultWordsList(): void
    {
        $words = [
            '/-credit(\s+|$)/',
            '/-digest(\s+|$)/',
            '/-loan(\s+|$)/',
            '/-online(\s+|$)/',
            '4u',
            'adipex',
            'advicer',
            'ambien',
            'baccarat',
            'baccarrat',
            'blackjack',
            'bllogspot',
            'bolobomb',
            'booker',
            'byob',
            'car-rental-e-site',
            'car-rentals-e-site',
            'carisoprodol',
            'cash',
            'casino',
            'casinos',
            'chatroom',
            'cialis',
            'craps',
            'credit-card',
            'credit-report-4u',
            'cwas',
            'cyclen',
            'cyclobenzaprine',
            'dating-e-site',
            'day-trading',
            'debt',
            'digest-',
            'discount',
            'discreetordering',
            'duty-free',
            'dutyfree',
            'estate',
            'favourits',
            'fioricet',
            'flowers-leading-site',
            'freenet',
            'freenet-shopping',
            'gambling',
            'gamias',
            'health-insurancedeals-4u',
            'holdem',
            'holdempoker',
            'holdemsoftware',
            'holdemtexasturbowilson',
            'hotel-dealse-site',
            'hotele-site',
            'hotelse-site',
            'incest',
            'insurance-quotesdeals-4u',
            'insurancedeals-4u',
            'jrcreations',
            'levitra',
            'macinstruct',
            'mortgage',
            'online-gambling',
            'onlinegambling-4u',
            'ottawavalleyag',
            'ownsthis',
            'palm-texas-holdem-game',
            'paxil',
            'pharmacy',
            'phentermine',
            'pills',
            'poker',
            'poker-chip',
            'poze',
            'prescription',
            'rarehomes',
            'refund',
            'rental-car-e-site',
            'roulette',
            'shemale',
            'slot',
            'slot-machine',
            'soma',
            'taboo',
            'tamiflu',
            'texas-holdem',
            'thorcarlson',
            'top-e-site',
            'top-site',
            'tramadol',
            'trim-spa',
            'ultram',
            'v1h',
            'vacuum',
            'valeofglamorganconservatives',
            'viagra',
            'vicodin',
            'vioxx',
            'xanax',
            'zolus',
        ];

        foreach ($words as $w) {
            try {
                $this->addRule($w, true);
            } catch (Exception) {
                // Ignore exceptions
            }
        }
    }
}
