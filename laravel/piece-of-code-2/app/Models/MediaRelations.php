<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaRelations extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'media_relations';

    public $timestamps = false;

    protected $fillable = ['media_id', 'related_id', 'key', 'updated_at'];
}
