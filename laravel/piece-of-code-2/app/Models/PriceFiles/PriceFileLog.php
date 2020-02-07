<?php

namespace App\Models\PriceFiles;

use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\PriceFiles\PriceFileLog
 *
 * @property int $id
 * @property int $price_file_id
 * @property string $source
 * @property string $type
 * @property string $job
 * @property string $message
 * @property mixed $data
 * @property \Carbon\Carbon $created_at
 * @property-read \App\Models\PriceFiles\PriceFile $file
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog type($type)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog whereJob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog wherePriceFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileLog comingFrom($source)
 * @mixin \Eloquent
 */
class PriceFileLog extends Model implements Serializable {

	use Serializer;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $dates = ['created_at'];

	/**
	 * @var array
	 */
	protected $fillable = ['source', 'type', 'job', 'message', 'data'];

	public static function boot() {
		parent::boot();

		static::creating(function (PriceFileLog $model) {
			$model->created_at = $model->freshTimestamp();
		});
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $type
	 * @return QueryBuilder
	 */
	public function scopeType(QueryBuilder $builder, $type) {
		return $builder->where(get_table_column_name($builder->getModel(), 'type'), '=', $type);
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $source
	 * @return QueryBuilder
	 */
	public function scopeComingFrom(QueryBuilder $builder, $source) {
		return $builder->where(get_table_column_name($builder->getModel(), 'source'), '=', $source);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function file() {
		return $this->belongsTo(PriceFile::class);
	}
}