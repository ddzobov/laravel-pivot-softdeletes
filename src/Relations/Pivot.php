<?php

namespace DDZobov\PivotSoftDeletes\Relations;

use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pivot extends EloquentPivot
{
    use SoftDeletes;
}