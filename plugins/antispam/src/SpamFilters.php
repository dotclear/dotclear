<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use dcCore;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;

/**
 * @brief   The module spam filters handler.
 * @ingroup antispam
 */
class SpamFilters
{
    /**
     * Stack of antispam filters.
     *
     * @var     array<string, SpamFilter>   $filters
     */
    private array $filters = [];

    /**
     * Stack of antispam filters settings.
     *
     * @var     array<string, mixed>        $filters_opt
     */
    private $filters_opt = [];

    /**
     * Initializes the given filters.
     *
     * @todo    Remove old dcCore from SpamFilters::init new filter parameters
     *
     * @param   array<mixed>   $filters    The filters
     */
    public function init(array $filters): void
    {
        foreach ($filters as $filter) {
            if (!class_exists($filter)) {
                continue;
            }

            if (!is_subclass_of($filter, SpamFilter::class)) {
                // An antispam filter must extend SpamFilter class
                continue;
            }

            // todo remove dcCore from method
            $class                     = new $filter(dcCore::app());
            $this->filters[$class->id] = $class;
        }

        $this->setFilterOpts();
        if (!empty($this->filters_opt)) {
            uasort($this->filters, fn ($a, $b): int => $a->order <=> $b->order);
        }
    }

    /**
     * Gets the filters.
     *
     * @return  array<string, SpamFilter>   The filters.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Determines whether the specified Cursor is a spam.
     *
     * @param   Cursor  $cur    The Cursor
     *
     * @return  bool    True if the specified current is spam, False otherwise.
     */
    public function isSpam(Cursor $cur): bool
    {
        foreach ($this->filters as $fid => $f) {
            if (!$f->active) {
                continue;
            }

            $type    = $cur->comment_trackback ? 'trackback' : 'comment';
            $author  = $cur->comment_author;
            $email   = $cur->comment_email;
            $site    = $cur->comment_site;
            $ip      = $cur->comment_ip;
            $content = $cur->comment_content;
            $post_id = (int) $cur->post_id;
            $status  = '';

            $is_spam = $f->isSpam($type, $author, $email, $site, $ip, $content, $post_id, $status);

            if ($is_spam === true) {
                if ($f->auto_delete) {
                    $cur->clean();
                } else {
                    $cur->comment_status      = App::status()->comment()->level('junk');
                    $cur->comment_spam_status = $status;
                    $cur->comment_spam_filter = $fid;
                }

                return true;
            } elseif ($is_spam === false) {
                return false;
            }
        }

        return false;
    }

    /**
     * Train antispam filters with current comment in record.
     *
     * @param   MetaRecord  $rs             The comment record
     * @param   string      $status         The status
     * @param   string      $filter_name    The filter name
     */
    public function trainFilters(MetaRecord $rs, string $status, string $filter_name): void
    {
        foreach ($this->filters as $f) {
            if (!$f->active) {
                continue;
            }

            $type    = $rs->comment_trackback ? 'trackback' : 'comment';
            $author  = $rs->comment_author;
            $email   = $rs->comment_email;
            $site    = $rs->comment_site;
            $ip      = $rs->comment_ip;
            $content = $rs->comment_content;

            $f->trainFilter($status, $filter_name, $type, $author, $email, $site, $ip, $content, $rs);
        }
    }

    /**
     * Get filter status message.
     *
     * @param   MetaRecord  $rs             The comment record
     * @param   string      $filter_name    The filter name
     */
    public function statusMessage(MetaRecord $rs, string $filter_name): string
    {
        $filter = $this->filters[$filter_name] ?? null;

        if ($filter === null) {
            return __('Unknown filter.');
        }
        $status = $rs->exists('comment_spam_status') ? $rs->comment_spam_status : null;

        return $filter->getStatusMessage($status, (int) $rs->comment_id);
    }

    /**
     * Saves filter settings.
     *
     * @param   array<int|string, array{0:bool, 1:int, 2:bool}>     $opts       The settings
     * @param   bool                                                $global     True if global settings
     */
    public function saveFilterOpts(array $opts, bool $global = false): void
    {
        if ($global) {
            My::settings()->drop('antispam_filters');
        }
        My::settings()->put('antispam_filters', $opts, 'array', 'Antispam Filters', true, $global);
    }

    /**
     * Sets the filter settings.
     */
    private function setFilterOpts(): void
    {
        if (My::settings()->antispam_filters !== null) {
            $this->filters_opt = My::settings()->antispam_filters;
        }

        // Create default options if needed
        if (!is_array($this->filters_opt)) {    // @phpstan-ignore-line
            $this->saveFilterOpts([], true);
            $this->filters_opt = [];
        }

        foreach ($this->filters_opt as $k => $option) {
            if (isset($this->filters[$k]) && is_array($option)) {
                $this->filters[$k]->active      = $option[0] ?? false;
                $this->filters[$k]->order       = $option[1] ?? 0;
                $this->filters[$k]->auto_delete = $option[2] ?? false;
            }
        }
    }
}
