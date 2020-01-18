<?php

namespace DDZobov\PivotSoftDeletes;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    use Concerns\HasRelationships;
}