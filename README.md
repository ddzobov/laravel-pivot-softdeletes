# Laravel pivot SoftDeletes for Laravel 5.8 to 10.x

## Installation

Require this package with composer:
```
composer require ddzobov/laravel-pivot-softdeletes
```

## Basic usage

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

### Custom pivot model:

```php
use DDZobov\PivotSoftDeletes\Model;
use DDZobov\PivotSoftDeletes\Relations\Pivot;

class Post extends Model
{
    public function tagsWithCustomPivot()
    {
        return $this->belongsToMany(Tag::class)->using(PostTag::class)->withSoftDeletes();
    }
}

class Tag extends Model
{
    public function postsWithCustomPivot()
    {
        return $this->belongsToMany(Post::class)->using(PostTag::class)->withSoftDeletes();
    }
}

class PostTag extends Pivot
{
    
}
```

### Custom deleted_at field:

```php
$this->belongsToMany(Post::class)->withSoftDeletes('custom_deleted_at');
```

### Show without trashed (default behavior):
```php
// withoutTrashed() already called inside withSoftDeletes()
$this->belongsToMany(Post::class)->withSoftDeletes();

// same behavior
$this->belongsToMany(Post::class)->withSoftDeletes()->withoutTrashedPivots();
```

### Show exists & trashed:
```php
$this->belongsToMany(Post::class)->withSoftDeletes()->withTrashedPivots();
```

### Show only trashed:
```php
$this->belongsToMany(Post::class)->withSoftDeletes()->onlyTrashedPivots();
```

### Restore pivot recods:
```php
$post->tags()->restore([$tag->id]);
```

### Restore pivot recods (with custom pivot):
```php
$post->tagsWithCustomPivot()->restore([$tag->id]);
```

### Force detach pivot records:
```php
$post->tags()->forceDetach([$tag->id]);
```

### Sync with force detaching pivot records:
```php
$post->tags()->syncWithForceDetaching([$tag->id]);
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
