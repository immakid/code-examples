<?php

namespace App\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;

/**
 * App\Models\Note
 *
 * @property mixed $data
 * @property-read \App\Models\Users\User $user
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Note onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Note withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Note withoutTrashed()
 * @mixin \Eloquent
 * @property int $id
 * @property int $user_id
 * @property string $subject
 * @property string $content
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Note whereUserId($value)
 */
class Note extends Model implements Serializable {

	use Serializer,
		SoftDeletes;

	/**
	 * @var array
	 */
	protected $fillable = ['subject', 'content', 'data'];

	public static function boot() {
		parent::boot();

		static::saving(function (Note $model) {

			if (!$model->subject) {
				$model->subject = $model->data('route.name', 'unknown');
			}
		});
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user() {
		return $this->belongsTo(User::class);
	}
}