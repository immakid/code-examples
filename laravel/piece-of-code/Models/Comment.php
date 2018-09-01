<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use function strip_tags;

class Comment extends Model
{
    /**
     * Define DB model table.
     *
     * @var string
     */
    protected $table = 'comments';

    /**
     * Guard ID from mass-assigment.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Hide attributes from default JSON.
     *
     * @var array
     */
    protected $hidden = ['commentable_type', 'updated_at'];

    /**
     * Load relationship by default.
     *
     * @var array
     */
    protected $with = ['user'];

    /**
     * Polymorphic rel.
     *
     * @return mixed
     */
    public function commentable()
    {
        return $this->morphTo();
    }

    /**
     * Define cmomentator relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Strip all tags, preventing JS injections.
     *
     * @param string $comment
     */
    public function setCommentAttrribute($comment)
    {
        $this->attributes['comment'] = strip_tags($comment);
    }
}