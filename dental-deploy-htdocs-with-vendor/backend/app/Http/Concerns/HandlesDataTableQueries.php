<?php

namespace App\Http\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared query plumbing for the frontend DataTable component.
 *
 * The table sends `sort`, `dir`, `page` and `per_page` on every request.
 * Sort columns are whitelisted per controller — a raw column name from the
 * query string would otherwise be interpolated straight into ORDER BY.
 */
trait HandlesDataTableQueries
{
    /**
     * Apply ORDER BY from the request, restricted to $allowed.
     *
     * @param  array<string, string>  $allowed  sort key => qualified column
     */
    protected function applySort(
        Builder $query,
        Request $request,
        array $allowed,
        string $default,
        string $defaultDir = 'desc',
    ): Builder {
        $key = (string) $request->query('sort', $default);
        $column = $allowed[$key] ?? $allowed[$default];

        $dir = strtolower((string) $request->query('dir', $defaultDir)) === 'asc' ? 'asc' : 'desc';

        // Tie-break on id so pagination stays stable when the sorted column
        // holds duplicates — otherwise rows can repeat or vanish across pages.
        return $query->orderBy($column, $dir)->orderBy(
            $query->getModel()->getQualifiedKeyName(),
            'desc',
        );
    }

    /** Clamp per_page so a hand-edited URL cannot ask for the whole table. */
    protected function perPage(Request $request, int $default = 25): int
    {
        $requested = (int) $request->query('per_page', $default);

        return max(5, min(200, $requested ?: $default));
    }

    /**
     * Apply an inclusive date range against a timestamp column.
     *
     * whereDate() is used rather than a >= / <= on the raw value so that a
     * `to` of 2026-08-14 includes visits recorded at 2026-08-14 17:30.
     */
    protected function applyDateRange(
        Builder $query,
        Request $request,
        string $column = 'created_at',
        string $fromKey = 'from',
        string $toKey = 'to',
    ): Builder {
        if ($from = $request->query($fromKey)) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to = $request->query($toKey)) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Apply an inclusive numeric range (whole IQD) against a money column.
     */
    protected function applyAmountRange(
        Builder $query,
        Request $request,
        string $column,
        string $minKey,
        string $maxKey,
    ): Builder {
        if (($min = $request->query($minKey)) !== null && $min !== '') {
            $query->where($column, '>=', (int) $min);
        }
        if (($max = $request->query($maxKey)) !== null && $max !== '') {
            $query->where($column, '<=', (int) $max);
        }

        return $query;
    }
}
