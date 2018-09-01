<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int        $id
 * @property int        $client_id
 * @property integer    $engine_volume
 * @property integer    $mark
 * @property integer    $model
 * @property integer    $category
 * @property string     $city
 * @property string     $carcase
 * @property string     $fuel
 * @property string     $vin
 * @property string     $mark_label
 * @property string     $model_label
 * @property Carbon     $created_at
 * @property Carbon     $updated_at
 */
class ClientCar extends Model
{
    const ENGINE_VOLUME_REGEX = '/^[0-9.,]{3}$/';
    const DEFAULT_CATEGORY = 1;

    protected $casts = [
        'engine_volume' => 'string',
        'mark'          => 'integer',
        'model'         => 'integer',
        'city'          => 'string',
        'fuel'          => 'integer',
        'category'      => 'integer',
    ];

    protected $fillable = [
        'engine_volume',
        'mark',
        'model',
        'carcase',
        'fuel',
        'vin'
    ];


    protected $attributes = [
        'id' => 0,
        'category' => self::DEFAULT_CATEGORY,

        'engine_volume' => '',
        'mark' => 0,
        'model' => 0,
        'carcase' => 0,
        'fuel' => 0

    ];

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function getMarkLabelAttribute() {
        return data("cars/marks-{$this->category}", $this->mark);
    }

    public function getModelLabelAttribute() {
        return data("cars/models-{$this->category}/{$this->mark}", $this->model);
    }
}
