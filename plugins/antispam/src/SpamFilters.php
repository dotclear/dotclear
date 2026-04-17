<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

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
     * @var     array<string, array{bool, int, bool}>        $filters_opt
     */
    private array $filters_opt = [];

    /**
     * Initializes the given filters.
     *
     * @param   array<string>   $filters    The filters
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

            $class                     = new $filter();
            $this->filters[$class->id] = $class;
        }

        $this->setFilterOpts();
        if ($this->filters_opt !== []) {
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
            $author  = is_string($author = $cur->comment_author) ? $author : null;
            $email   = is_string($email = $cur->comment_email) ? $email : null;
            $site    = is_string($site = $cur->comment_site) ? $site : null;
            $ip      = is_string($ip = $cur->comment_ip) ? $ip : null;
            $content = is_string($content = $cur->comment_content) ? $content : null;
            $post_id = is_numeric($cur->post_id) ? (int) $cur->post_id : null;
            $status  = '';

            $is_spam = $f->isSpam($type, $author, $email, $site, $ip, $content, $post_id, $status);

            if ($is_spam === true) {
                if ($f->auto_delete) {
                    $cur->clean();
                } else {
                    $cur->comment_status      = App::status()->comment()::JUNK;
                    $cur->comment_spam_status = $status;
                    $cur->comment_spam_filter = $fid;
                }

                return true;
            }

            if ($is_spam === false) {
                // Not a spam, if only spams are moderated, publish it
                if (My::settings()->moderate_only_spam && $cur->comment_status !== App::status()->comment()::PUBLISHED) {
                    $cur->comment_status = App::status()->comment()::PUBLISHED;
                }

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
            $author  = is_string($author = $rs->comment_author) ? $author : null;
            $email   = is_string($email = $rs->comment_email) ? $email : null;
            $site    = is_string($site = $rs->comment_site) ? $site : null;
            $ip      = is_string($ip = $rs->comment_ip) ? $ip : null;
            $content = is_string($content = $rs->comment_content) ? $content : null;

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
        $status     = $rs->exists('comment_spam_status') && is_string($rs->comment_spam_status) ? $rs->comment_spam_status : '';
        $comment_id = is_numeric($comment_id = $rs->comment_id) ? (int) $comment_id : null;

        return $filter->getStatusMessage($status, $comment_id);
    }

    /**
     * Saves filter settings.
     *
     * @param   array<string, array{bool, int, bool}>       $opts       The settings
     * @param   bool                                        $global     True if global settings
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
        $filters = $this->sanitizeFilterOpts(My::settings()->antispam_filters);
        if ($filters !== []) {
            $this->filters_opt = $filters;
        } else {
            // Create default options if needed
            $this->saveFilterOpts([], true);
            $this->filters_opt = [];
        }

        foreach ($this->filters_opt as $k => $option) {
            if (isset($this->filters[$k])) {
                $this->filters[$k]->active      = $option[0];
                $this->filters[$k]->order       = $option[1];
                $this->filters[$k]->auto_delete = $option[2];
            }
        }
    }

    /**
     * Check antispam filters settings (to cope with old ones)
     *
     * @param  mixed  $filters Current saved filters options
     *
     * @return array<string, array{bool, int, bool}>
     */
    private function sanitizeFilterOpts(mixed $filters): array
    {
        $options = [];
        if (is_array($filters)) {
            $index = 0;
            foreach ($filters as $key => $value) {
                // Check key: should be filter name
                if (!is_string($key)) {
                    continue;
                }

                // Check value, should be an array
                if (!is_array($value)) {
                    continue;
                }

                // 1st item: active flag (boolean)
                $active = (is_bool($value[0]) || is_numeric($value[0]) || is_null($value[0])) && (bool) $value[0];

                // 2nd item (if any): order (integer)
                $order = $index;
                if (isset($value[1])) {
                    $order = is_numeric($value[1]) ? (int) $value[1] : $order;
                }

                // 3rd item (if any): auto delete flag (boolean)
                $auto_delete = false;
                if (isset($value[2])) {
                    $auto_delete = (is_bool($value[2]) || is_numeric($value[2])) && (bool) $value[2];
                }

                $options[$key] = [
                    $active,
                    $order,
                    $auto_delete,
                ];
                $index++;
            }
        }

        return $options;
    }
}
