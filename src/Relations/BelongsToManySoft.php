<?php

namespace DDZobov\PivotSoftDeletes\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @method self withoutTrashed() Show only non-trashed records
 * @method self withTrashed() Show all records
 * @method self onlyTrashed() Show only trashed records
 * @method int forceDetach(\Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|array  $ids, bool  $touch) Show only trashed records
 * @method int syncWithForceDetaching(mixed  $ids) Show only trashed records
 */
class BelongsToManySoft extends BelongsToMany
{
    use Concerns\InteractsWithPivotTable;

    /**
     * Indicates if soft deletes are available on the pivot table.
     *
     * @var bool
     */
    public $withSoftDeletes = false;

    /**
     * The custom pivot table column for the deleted_at timestamp.
     *
     * @var string
     */
    protected $pivotDeletedAt;

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function deletedAt()
    {
        return $this->pivotDeletedAt;
    }

    /**
     * Get the fully qualified deleted at column name.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumnName()
    {
        return $this->getQualifiedColumnName($this->pivotDeletedAt);
    }

    /**
     * Get the fully qualified column name.
     *
     * @param string $column
     * @return string
     */
    public function getQualifiedColumnName($column)
    {
        return $this->table.'.'.$column;
    }

    public function withSoftDeletes($deletedAt = 'deleted_at')
    {
        $this->withSoftDeletes = true;

        $this->pivotDeletedAt = $deletedAt;

        $this->macro('withoutTrashed', function () {
            $this->query->withGlobalScope('withoutTrashed', function (Builder $query) {
                $query->whereNull(
                    $this->getQualifiedDeletedAtColumnName()
                );
            })->withoutGlobalScopes(['onlyTrashed']);

            return $this;
        });

        $this->macro('withTrashed', function () {
            $this->query->withoutGlobalScopes(['withoutTrashed', 'onlyTrashed']);

            return $this;
        });

        $this->macro('onlyTrashed', function () {
            $this->query->withGlobalScope('onlyTrashed', function (Builder $query) {
                $query->whereNotNull(
                    $this->getQualifiedDeletedAtColumnName()
                );
            })->withoutGlobalScopes(['withoutTrashed']);

            return $this;
        });

        $this->macro('forceDetach', function ($ids = null, $touch = true) {
            $this->withSoftDeletes = false;

            return tap($this->detach($ids, $touch), function () {
                $this->withSoftDeletes = true;
            });
        });

        $this->macro('syncWithForceDetaching', function ($ids) {
            $this->withSoftDeletes = false;

            return tap($this->sync($ids), function () {
                $this->withSoftDeletes = true;
            });
        });

        return $this->withPivot($this->deletedAt())->withoutTrashed();
    }

    /**
     * Set the join clause for the relation query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $baseTable = $this->related->getTable();

        $key = $baseTable.'.'.$this->relatedKey;

        $query->join($this->table, $key, '=', $this->getQualifiedRelatedPivotKeyName());

        $query->when($this->withSoftDeletes, function (Builder $query) {
            $query->whereNull($this->getQualifiedDeletedAtColumnName());
        });

        return $this;
    }
}