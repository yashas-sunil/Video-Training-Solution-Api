<?php

namespace App\Models;

use App\BlogLike;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use SoftDeletes;

    protected $appends = [
        'image_url',
        'is_liked',
        'total_likes'
    ];

    public function getImageUrlAttribute()
    {
        if (! $this->image) {
            return null;
        }

        return env('IMAGE_URL') . '/blogs/images/' . $this->image;
    }

    public function getIsLikedAttribute()
    {
        return BlogLike::query()
            ->where('blog_id', $this->id)
            ->where('user_id', auth('api')->id())
            ->exists();
    }

    public function getTotalLikesAttribute()
    {
        return BlogLike::query()
            ->where('blog_id', $this->id)
            ->count();
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function blogTags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tags_pivot', 'blog_id', 'tag_id');
    }

    public function relatedBlogs()
    {
        return $this->belongsToMany(Blog::class, 'related_blogs', 'blog_id', 'related_blog_id');
    }

    public function likes()
    {
        return $this->hasMany(BlogLike::class);
    }


    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeOfPublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * @param Builder $query
     * @param int $id
     * @return Builder
     */
    public function scopeOfCategory($query, $id)
    {
        if (! $id) {
            return $query;
        }

        return $query->where('category_id', $id);
    }

    /**
     * @param Builder $query
     * @param int $id
     * @return Builder
     */
    public function scopeOfTag($query, $id)
    {
        if (! $id) {
            return $query;
        }

        return $query->whereHas('blogTags', function ($query) use ($id) {
            $query->where('id', $id);
        });
    }

    /**
     * @param Builder $query
     * @param string $order
     * @return Builder
     */
    public function scopeOfOrder($query, $order)
    {
        if (! $order || $order == 'featured') {
            $query->orderBy('order');
        }

        if ($order == 'asc') {
            $query->orderBy('views');
        }

        if ($order == 'des') {
            $query->orderByDesc('views');
        }

        if ($order == 'oldest') {
            $query->orderBy('published_at');
        }

        if ($order == 'latest') {
            $query->orderByDesc('published_at');
        }
    }

    /**
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    public function scopeOfSearch($query, $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where('title', 'like', '%' . $search . '%')
            ->orWhere('body', 'like', '%' . $search . '%')
            ->orWhereHas('blogTags', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
    }
}
