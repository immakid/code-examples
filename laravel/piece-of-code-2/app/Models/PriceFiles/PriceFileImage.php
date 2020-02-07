<?php

namespace App\Models\PriceFiles;

use App\Models\Products\Product;
use App\Acme\Extensions\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\PriceFiles\PriceFileImage
 *
 * @property int $id
 * @property int $price_file_id
 * @property mixed $url
 * @property-read \App\Models\PriceFiles\PriceFile $file
 * @property-read \App\Models\Products\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileImage wherePriceFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileImage whereUrl($value)
 * @mixin \Eloquent
 * @property int $product_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileImage forFile(\App\Models\PriceFiles\PriceFile $priceFile)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileImage whereProductId($value)
 */
class PriceFileImage extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $fillable = ['url'];

	/**
	 * @param QueryBuilder $builder
	 * @param PriceFile $priceFile
	 * @return QueryBuilder
	 */
	public function scopeForFile(QueryBuilder $builder, PriceFile $priceFile) {
		return $builder->whereHas('file', function (QueryBuilder $builder) use ($priceFile) {
			return $builder->where(get_table_column_name($builder->getModel(), 'id'), '=', $priceFile->id);
		});
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function file() {
		return $this->belongsTo(PriceFile::class, 'price_file_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function product() {
		return $this->belongsTo(Product::class);

	}
}
