<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * ─────────────────────────────────────────────────────────────
 * HasIndexFilters — Shared Search, Sort & Pagination for Controllers
 * ─────────────────────────────────────────────────────────────
 *
 * Eliminates copy-pasted search/sort/pagination boilerplate
 * across all index methods. Usage:
 *
 *   use HasIndexFilters;
 *
 *   $this->applySearch($query, $request->search, ['name', 'email']);
 *   $this->applySort($query, $request, ['name', 'created_at']);
 *   $result = $this->paginateWithMeta($query, 15);
 */
trait HasIndexFilters
{
    /**
     * Apply LIKE search across given columns + optional relations.
     *
     * @param  Builder  $query
     * @param  string|null  $search
     * @param  array  $columns   e.g. ['name', 'sku', 'description']
     * @param  array  $relations e.g. ['supplier' => 'company_name'] or ['supplier' => ['company_name', 'email']]
     * @return Builder
     */
    protected function applySearch(Builder $query, ?string $search, array $columns, array $relations = []): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $columns, $relations) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }

            foreach ($relations as $relation => $relationColumns) {
                $q->orWhereHas($relation, function (Builder $rq) use ($search, $relationColumns) {
                    $rq->where(function (Builder $inner) use ($search, $relationColumns) {
                        foreach ((array) $relationColumns as $col) {
                            $inner->orWhere($col, 'like', "%{$search}%");
                        }
                    });
                });
            }
        });
    }

    /**
     * Apply validated sort from request with allowlist.
     *
     * @param  Builder  $query
     * @param  Request  $request
     * @param  array    $allowedSorts     e.g. ['name', 'email', 'created_at']
     * @param  string   $defaultField     Default sort column
     * @param  string   $defaultDirection Default sort direction ('asc' or 'desc')
     * @return Builder
     */
    protected function applySort(
        Builder $query,
        Request $request,
        array $allowedSorts,
        string $defaultField = 'created_at',
        string $defaultDirection = 'desc'
    ): Builder {
        $field = $request->input('sort', $defaultField);
        $direction = $request->input('direction', $defaultDirection);

        if (!in_array($field, $allowedSorts)) {
            $field = $defaultField;
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }

        return $query->orderBy($field, $direction);
    }

    /**
     * Paginate query and return standardized meta structure.
     *
     * @param  Builder  $query
     * @param  int      $perPage
     * @return array{paginator: \Illuminate\Contracts\Pagination\LengthAwarePaginator, meta: array}
     */
    protected function paginateWithMeta(Builder $query, int $perPage = 15): array
    {
        $paginated = $query->paginate($perPage)->withQueryString();

        return [
            'paginator' => $paginated,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ];
    }
}
