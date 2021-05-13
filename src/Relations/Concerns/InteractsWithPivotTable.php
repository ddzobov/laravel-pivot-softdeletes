<?php

namespace DDZobov\PivotSoftDeletes\Relations\Concerns;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder;

trait InteractsWithPivotTable
{
    /**
     * Toggles a model (or models) from the parent.
     *
     * Each existing model is detached, and non existing ones are attached.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return array
     */
    public function toggle($ids, $touch = true)
    {
        $changes = [
            'attached' => [], 'detached' => [],
        ];

        $records = $this->formatRecordsList($this->parseIds($ids));

        // Next, we will determine which IDs should get removed from the join table by
        // checking which of the given ID/records is in the list of current records
        // and removing all of those rows from this "intermediate" joining table.
        $detach = array_values(array_intersect(
            $this->newPivotQueryWithoutTrashed()->pluck($this->relatedPivotKey)->all(),
            array_keys($records)
        ));

        if (count($detach) > 0) {
            $this->detach($detach, false);

            $changes['detached'] = $this->castKeys($detach);
        }

        // Finally, for all of the records which were not "detached", we'll attach the
        // records into the intermediate table. Then, we will add those attaches to
        // this change list and get ready to return these results to the callers.
        $attach = array_diff_key($records, array_flip($detach));

        if (count($attach) > 0) {
            $this->attach($attach, [], false);

            $changes['attached'] = array_keys($attach);
        }

        // Once we have finished attaching or detaching the records, we will see if we
        // have done any attaching or detaching, and if we have we will touch these
        // relationships if they are configured to touch on any database updates.
        if ($touch && (count($changes['attached']) ||
                count($changes['detached']))) {
            $this->touchIfTouching();
        }

        return $changes;
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        if ($this->using && ! empty($ids) && empty($this->pivotWheres) && empty($this->pivotWhereIns)) {
            $results = $this->detachUsingCustomClass($ids);
        } else {
            $query = $this->newPivotQuery();

            // If associated IDs were passed to the method we will only delete those
            // associations, otherwise all of the association ties will be broken.
            // We'll return the numbers of affected rows when we do the deletes.
            if (! is_null($ids)) {
                $ids = $this->parseIds($ids);

                if (empty($ids)) {
                    return 0;
                }

                $query->whereIn($this->relatedPivotKey, (array) $ids);
            }

            // Once we have all of the conditions set on the statement, we are ready
            // to run the delete on the pivot table. Then, if the touch parameter
            // is true, we will go ahead and touch all related models to sync.
            if ($this->withSoftDeletes) {
                $fresh = now();

                $attributes = [
                    $this->deletedAt() => $fresh,
                ];

                if ($this->hasPivotColumn($this->updatedAt())) {
                    $attributes[$this->updatedAt()] = $fresh;
                }

                $results = $query->update($attributes);
            } else {
                $results = $query->delete();
            }
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        return $results;
    }

    /**
     * Restore the intermediate table entries with a list of IDs or collection of models.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function restore($ids = null, $touch = true)
    {
        if ($this->using && ! empty($ids) && empty($this->pivotWheres) && empty($this->pivotWhereIns)) {
            $results = $this->restoreUsingCustomClass($ids);
        } else {
            $query = $this->newPivotQuery();

            // If associated IDs were passed to the method we will only restore those
            // associations, otherwise all of the association ties will be broken.
            // We'll return the numbers of affected rows when we do the restores.
            if (! is_null($ids)) {
                $ids = $this->parseIds($ids);

                if (empty($ids)) {
                    return 0;
                }

                $query->whereIn($this->relatedPivotKey, (array) $ids);
            }

            // Once we have all of the conditions set on the statement, we are ready
            // to run the restore on the pivot table. Then, if the touch parameter
            // is true, we will go ahead and touch all related models to sync.
            if ($this->withSoftDeletes) {
                $attributes = [
                    $this->deletedAt() => null,
                ];

                if ($this->hasPivotColumn($this->updatedAt())) {
                    $attributes[$this->updatedAt()] = now();
                }

                $results = $query->update($attributes);
            } else {
                $results = $query->delete();
            }
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        return $results;
    }

    /**
     * Restore models from the relationship using a custom class.
     *
     * @param  mixed  $ids
     * @return int
     */
    protected function restoreUsingCustomClass($ids)
    {
        $results = 0;

        foreach ($this->parseIds($ids) as $id) {
            $results += $this->newPivot([
                $this->foreignPivotKey => $this->parent->{$this->parentKey},
                $this->relatedPivotKey => $id,
            ], true)->restore();
        }

        return $results;
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCurrentlyAttachedPivots()
    {
        return $this->newPivotQueryWithoutTrashed()->get()->map(function ($record) {
            $class = $this->using ?: Pivot::class;

            $pivot = $class::fromRawAttributes($this->parent, (array) $record, $this->getTable(), true);

            return $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey);
        });
    }

    /**
     * Create a new query builder for the pivot table selection without trashed records.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQueryWithoutTrashed()
    {
        return $this->newPivotQuery()->when($this->withSoftDeletes, function (Builder $query) {
            $query->whereNull($this->getQualifiedDeletedAtColumnName());
        });
    }
}