<?php

namespace App\Models\PriceFiles;

use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;

/**
 * App\Models\PriceFiles\PriceFileMap
 *
 * @property int $id
 * @property int $price_file_id
 * @property int|null $price_file_column_id
 * @property int $index
 * @property mixed $label
 * @property mixed $data
 * @property-read \App\Models\PriceFiles\PriceFileColumn|null $column
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileMap whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileMap whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileMap whereIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileMap whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileMap wherePriceFileColumnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFileMap wherePriceFileId($value)
 * @mixin \Eloquent
 */
class PriceFileMap extends Model implements Serializable {

    use Serializer;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['index', 'label', 'data'];

    /**
     * @var array
     */
    protected $with = ['column'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function column() {
        return $this->belongsTo(PriceFileColumn::class, 'price_file_column_id');
    }
}