<?php

namespace DDZobov\PivotSoftDeletes\Relations\Concerns;

trait InteractsWithPivotTable
{
    /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool   $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        if ($this->using) {
            return $this->updateExistingPivotUsingCustomClass($id, $attributes, $touch);
        }

        if (in_array($this->updatedAt(), $this->pivotColumns)) {
            $attributes = $this->addTimestampsToAttachment($attributes, true);
        }

        $updated = $this->newPivotStatementForId($this->parseId($id))->update(
            $this->castAttributes($attributes)
        );

        if ($touch) {
            $this->touchIfTouching();
        }

        return $updated;
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
            // to run the soft delete on the pivot table. Then, if the touch parameter
            // is true, we will go ahead and touch all related models to sync.
            $fresh = now();

            $attributes = [
                $this->deletedAt() => $fresh
            ];

            if ($this->hasPivotColumn($this->updatedAt())) {
                $attributes[$this->updatedAt()] = $fresh;
            }

            $results = $query->update($attributes);
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        return $results;
    }
}