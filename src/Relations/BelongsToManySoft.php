<?php

namespace DDZobov\PivotSoftDeletes\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * Indicates if soft deleted records will be retrieved.
     *
     * @var bool
     */
    public $withTrashed = false;

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
     * Specify that the pivot table has delete timestamp.
     *
     * @param  mixed  $deletedAt
     * @return $this
     */
    public function withSoftDeletes($deletedAt = 'deleted_at')
    {
        $this->withSoftDeletes = true;

        $this->pivotDeletedAt = $deletedAt;

//        return $this->withPivot($this->deletedAt())->whereNull($this->getTable() . '.' . $this->deletedAt());
        return $this->withPivot($this->deletedAt())->wherePivot($this->deletedAt(), '=', null);
    }
}