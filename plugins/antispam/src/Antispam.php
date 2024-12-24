<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use ArrayObject;
use dcCore;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Interface\Core\BlogInterface;

/**
 * @brief   The module antispam handler.
 * @ingroup antispam
 */
class Antispam
{
    /**
     * Spam rules table name.
     *
     * @var     string  SPAMRULE_TABLE_NAME
     */
    public const SPAMRULE_TABLE_NAME = 'spamrule';

    /**
     * The spam filters stacks.
     *
     * @var     array<int, class-string<SpamFilter>|SpamFilter>  $spamfilters
     */
    private static array $spamfilters = [];

    /**
     * Antispam Filters.
     *
     * @var     SpamFilters     $filters
     */
    public static $filters;

    /**
     * Initializes the filters.
     */
    public static function initFilters(): void
    {
        if (!empty(self::$spamfilters)) {
            return;
        }

        // deprecated since 2.28, use App::behavior->addBehavior('AntispamInitFilters', ...) instaed
        if (!empty(dcCore::app()->spamfilters)) {
            foreach (dcCore::app()->spamfilters as $spamfilter) {
                if (is_subclass_of($spamfilter, SpamFilter::class)) {
                    self::$spamfilters[] = $spamfilter;
                }
            }
        }

        $spamfilters = new ArrayObject();
        # --BEHAVIOR-- AntispamInitFilters -- ArrayObject
        App::behavior()->callBehavior('AntispamInitFilters', $spamfilters);

        foreach ($spamfilters as $spamfilter) {
            if (is_subclass_of($spamfilter, SpamFilter::class)) {   // @phpstan-ignore-line
                self::$spamfilters[] = $spamfilter;
            }
        }

        self::$filters = new SpamFilters();
        self::$filters->init(self::$spamfilters);
    }

    /**
     * Determines whether the specified Cursor content is spam.
     *
     * The Cursor may be modified (or deleted) according to the result
     *
     * @param   Cursor  $cur    The current
     */
    public static function isSpam(Cursor $cur): void
    {
        self::initFilters();
        self::$filters->isSpam($cur);
    }

    /**
     * Train the filters with current record.
     *
     * @param   BlogInterface   $blog   The blog
     * @param   Cursor          $cur    The Cursor
     * @param   MetaRecord      $rs     The comment record
     */
    public static function trainFilters(BlogInterface $blog, Cursor $cur, MetaRecord $rs): void
    {
        $status = null;
        // From ham to spam
        if ($rs->comment_status != $blog::COMMENT_JUNK && $cur->comment_status == $blog::COMMENT_JUNK) {
            $status = 'spam';
        }

        // From spam to ham
        if ($rs->comment_status == $blog::COMMENT_JUNK && $cur->comment_status == $blog::COMMENT_PUBLISHED) {
            $status = 'ham';
        }

        // the status of this comment has changed
        if ($status) {
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : '';

            self::initFilters();
            self::$filters->trainFilters($rs, $status, $filter_name);
        }
    }

    /**
     * Get filter status message.
     *
     * @param   MetaRecord  $rs     The comment record
     *
     * @return  string
     */
    public static function statusMessage(MetaRecord $rs): string
    {
        if ($rs->exists('comment_status') && $rs->comment_status == App::blog()::COMMENT_JUNK) {
            $filter_name = $rs->exists('comment_spam_filter') ? $rs->comment_spam_filter : '';

            self::initFilters();

            return
            (new Para())->items([
                (new Text(
                    null,
                    (new Text('strong', __('This comment is a spam:')))->render() . self::$filters->statusMessage($rs, $filter_name)
                )),
            ])
            ->render();
        }

        return '';
    }

    /**
     * Return additional information about existing spams.
     *
     * @return  string
     */
    public static function dashboardIconTitle(): string
    {
        if (($count = self::countSpam()) > 0) {
            $str = ($count > 1) ? __('(including %d spam comments)') : __('(including %d spam comment)');

            return '</span></a> <a href="' . App::backend()->url()->get('admin.comments', ['status' => '-2']) . '"><span class="db-icon-title-spam">' .
            sprintf($str, $count);
        }

        return '';
    }

    /**
     * Load antispam dashboard script.
     *
     * @return  string
     */
    public static function dashboardHeaders(): string
    {
        return My::jsLoad('dashboard');
    }

    /**
     * Counts the number of spam.
     *
     * @return  int     Number of spam.
     */
    public static function countSpam(): int
    {
        return (int) App::blog()->getComments(['comment_status' => App::blog()::COMMENT_JUNK], true)->f(0);
    }

    /**
     * Counts the number of published comments.
     *
     * @return  int     Number of published comments.
     */
    public static function countPublishedComments(): int
    {
        return (int) App::blog()->getComments(['comment_status' => App::blog()::COMMENT_PUBLISHED], true)->f(0);
    }

    /**
     * Delete all spam older than a given date, else every.
     *
     * @param   null|string     $beforeDate     The before date
     */
    public static function delAllSpam(?string $beforeDate = null): void
    {
        $sql = new SelectStatement();
        $sql
            ->column('comment_id')
            ->from($sql->as(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->join(
                (new JoinStatement())
                    ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('P.post_id = C.post_id')
                    ->statement()
            )
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('comment_status = ' . (string) App::blog()::COMMENT_JUNK);

        if ($beforeDate) {
            $sql->and('comment_dt < \'' . $beforeDate . '\' ');
        }

        $r = [];
        if ($rs = $sql->select()) {
            while ($rs->fetch()) {
                $r[] = (int) $rs->comment_id;
            }
        }

        if (empty($r)) {
            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME)
            ->where('comment_id ' . $sql->in($r))
            ->delete();
    }

    /**
     * Gets the user code (used for antispam feeds URL).
     *
     * @return  string  The user code.
     */
    public static function getUserCode(): string
    {
        $code = pack('a32', App::auth()->userID()) .
        hash(App::config()->cryptAlgo(), App::auth()->cryptLegacy(App::auth()->getInfo('user_pwd')));

        return bin2hex($code);
    }

    /**
     * Check if a user code is valid and if so return the user ID.
     *
     * @param   string  $code   The code
     *
     * @return  bool|string
     */
    public static function checkUserCode(string $code)
    {
        $code = pack('H*', $code);

        $user_id = trim((string) @pack('a32', substr($code, 0, 32)));
        $pwd     = substr($code, 32);

        if ($user_id === '' || $pwd === '') {
            return false;
        }

        $sql = new SelectStatement();
        $rs  = $sql
            ->columns([
                'user_id',
                'user_pwd',
            ])
            ->from(App::con()->prefix() . App::auth()::USER_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($user_id))
            ->select();

        if (!$rs || $rs->isEmpty()) {
            return false;
        }

        if (hash(App::config()->cryptAlgo(), App::auth()->cryptLegacy($rs->user_pwd)) != $pwd) {
            return false;
        }

        $permissions = App::blogs()->getBlogPermissions(App::blog()->id());

        if (empty($permissions[$rs->user_id])) {
            return false;
        }

        return $rs->user_id;
    }

    /**
     * Purge old spam.
     */
    public static function purgeOldSpam(): void
    {
        $defaultDateLastPurge = time();
        $defaultModerationTTL = '7';
        $init                 = false;

        // settings
        $dateLastPurge = My::settings()->antispam_date_last_purge;
        if ($dateLastPurge === null) {
            $init = true;
            My::settings()->put('antispam_date_last_purge', $defaultDateLastPurge, 'integer', 'Antispam Date Last Purge (unix timestamp)', true, false);
            $dateLastPurge = $defaultDateLastPurge;
        }
        $moderationTTL = My::settings()->antispam_moderation_ttl;
        if ($moderationTTL === null) {
            My::settings()->put('antispam_moderation_ttl', $defaultModerationTTL, 'integer', 'Antispam Moderation TTL (days)', true, false);
            $moderationTTL = $defaultModerationTTL;
        }

        if ($moderationTTL < 0) {
            // disabled
            return;
        }

        // we call the purge every day
        if ((time() - $dateLastPurge) > (86400)) {
            // update dateLastPurge
            if (!$init) {
                My::settings()->put('antispam_date_last_purge', time(), null, null, true, false);
            }
            $date = date('Y-m-d H:i:s', (int) (time() - $moderationTTL * 86400));
            Antispam::delAllSpam($date);
        }
    }
}
