<?php

namespace App\Models;

use App\Models\Users\User;
use App\Acme\Interfaces\Eloquent\Statusable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Statuses;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Comment
 *
 * @property int $id
 * @property int $user_id
 * @property int $language_id
 * @property mixed $text
 * @property int $rating
 * @property string $ip_address
 * @property int $status
 * @property int $commentable_id
 * @property string $commentable_type
 * @property string|null $deleted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Users\User $author
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read false|string $hr_status
 * @property-read \App\Models\Language $language
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment approved()
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment status($statuses)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment type($type)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereCommentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereCommentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Comment whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment withoutTrashed()
 * @mixin \Eloquent
 */
class Comment extends Model implements Statusable {

	use Statuses,
		SoftDeletes;

	/**
	 * @var array
	 */
	protected $fillable = ['text', 'rating'];

	/**
	 * @var array
	 */
	protected $with = ['author', 'language'];

	/**
	 * @var array
	 */
	protected $casts = [
		'rating' => 'integer',
		'status' => 'integer'
	];

	/**
	 * @var array
	 */
	protected static $statuses = [
		'pending' => 1,
		'approved' => 2,
		'spam' => 3
	];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function commentable() {
		return $this->morphTo();
	}

	public static function boot() {
		parent::boot();

		static::creating(function (Comment $model) {

			$ip = app('request')->getClientIp();

			$model->text = strip_tags($model->text);
			$model->ip_address = $ip ? $ip : 'unknown';
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeApproved(QueryBuilder $builder) {
		return $builder->status(self::$statuses['approved']);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param $type
	 * @return QueryBuilder
	 */
	public function scopeType(QueryBuilder $builder, $type) {
		return $builder->where(get_table_column_name($builder->getModel(), 'commentable_type'), '=', $type);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function author() {
		return $this->belongsTo(User::class, 'user_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function language() {
		return $this->belongsTo(Language::class);
	}
}
