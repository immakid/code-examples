<?php

namespace App\Models;

use App\Acme\Interfaces\Eloquent\Mediable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;
use Illuminate\Support\Arr;

/**
 * App\Models\Career
 *
 * @property int $id
 * @property mixed $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Career whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Career whereName($value)
 * @mixin \Eloquent
 * @property int $order
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Career whereOrder($value)
 */
class Career extends Model implements Mediable {

	use MediaManager;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['name'];

	/**
	 * @var string
	 */
	protected static $mediaKey = 'careers';

	public static function boot() {
		parent::boot();

		static::creating(function (Career $model) {

			/**
			 * Calculate order
			 */
			$query = static::select(['id', 'order']);
			$list = Arr::pluck($query->get()->toArray(), 'order', 'id');
			$model->order = ($list) ? (max($list) + 1) : 1;
		});
	}
}
