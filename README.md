# Laravel pivot SoftDeletes for Laravel 5.8 & 6.x

## Installation

Require this package with composer:
```
composer require ddzobov/laravel-pivot-softdeletes
```

## Basic usage

### New models:

```php
use DDZobov\PivotSoftDeletes\Model;

class Post extends Model
{
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withSoftDeletes();
    }
}

class Tag extends Model
{
    public function posts()
    {
        return $this->belongsToMany(Post::class)->withSoftDeletes();
    }
}
```

### Existing models:

```php
use Illuminate\Database\Eloquent\Model;
use DDZobov\PivotSoftDeletes\Concerns\HasRelationships;

class Post extends Model
{
    use HasRelationships;

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withSoftDeletes();
    }
}

class Tag extends Model
{
    use HasRelationships;

    public function posts()
    {
        return $this->belongsToMany(Post::class)->withSoftDeletes();
    }
}
```

### New pivot model:

```php
use DDZobov\PivotSoftDeletes\Model;
use DDZobov\PivotSoftDeletes\Relations\Pivot;

class Post extends Model
{
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->using(PostTag::class)->withSoftDeletes();
    }
}

class Tag extends Model
{
    public function posts()
    {
        return $this->belongsToMany(Post::class)->using(PostTag::class)->withSoftDeletes();
    }
}

class PostTag extends Pivot
{
    
}
```

### Existing pivot models:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use DDZobov\PivotSoftDeletes\Concerns\HasRelationships;

class Post extends Model
{
    use HasRelationships;

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->using(PostTag::class)->withSoftDeletes();
    }
}

class Tag extends Model
{
    use HasRelationships;

    public function posts()
    {
        return $this->belongsToMany(Post::class)->using(PostTag::class)->withSoftDeletes();
    }
}

class PostTag extends Pivot
{
    use SoftDeletes;
}
```

### Custom deleted_at field:

```php
$this->belongsToMany(Post::class)->withSoftDeletes('custom_deleted_at');
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.