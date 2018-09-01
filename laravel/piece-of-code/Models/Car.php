<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int    $id
 * @property int    $user_id
 * @property int    $li
 * @property int    $category
 * @property int    $mark
 * @property int    $model
 * @property int    $year
 * @property int    $color
 * @property int    $race
 * @property string $vin
 * @property string $lot
 * @property string $port
 * @property string $form
 * @property string $tracking
 * @property string $line
 * @property int    $status_id
 * @property int    $people_id
 * @property int    $docs_location_id
 * @property int    $start_price
 * @property int    $sale_price
 * @property int    $end_price
 * @property int    $margin
 * @property Carbon $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property string     $mark_label
 * @property string     $model_label
 * @property string     $title
 * @property string     $color_label
 * @property int        $total_expenses
 *
 * @property User                       $user
 * @property CarStatus                  $status
 * @property People                     $people
 * @property CarDocsLocation            $docs_location
 * @property User[]|Collection          $users
 * @property CarExpense[]|Collection    $expenses
 * @property CarPhoto[]|Collection      $photos
 * @property CarPhoto|null              $main_photo
 */
class Car extends Model implements HasImages {

    use SoftDeletes;

    const DEFAULT_CATEGORY = 1;
    const LI_LOCAL = 1;
    const LI_IMPORT = 2;
    const YEAR_MIN = 1000;
    const RACE_MAX = 999999;

    const VIN_REGEX = '/^[0-9A-Z]{17}$/';
    const VIN_REGEX_IC = '/^[0-9A-Za-z]{17}$/';
    const LOT_REGEX = '/^[0-9]{0,10}$/';
    const PORT_REGEX = '/^[A-Z]{0,20}$/';
    const FORM_REGEX = '/^[0-9A-Z]{0,5}$/';

    protected $attributes = [
        'id' => 0,

        'li' => 0,
        'category' => self::DEFAULT_CATEGORY,

        'lot' => '',
        'port' => '',
        'form' => '',
        'tracking' => '',
        'line' => '',

        'sale_price' => 0,
        'end_price' => 0,
        'margin' => 0,
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'li' => 'integer',
        'category' => 'integer',
        'mark' => 'integer',
        'model' => 'integer',
        'year' => 'integer',
        'color' => 'integer',
        'race' => 'integer',
        'vin' => 'string',
        'lot' => 'string',
        'port' => 'string',
        'form' => 'string',
        'tracking' => 'string',
        'line' => 'string',
        'status_id' => 'string',
        'people_id' => 'integer',
        'docs_location_id' => 'integer',
        'start_price' => 'integer',
        'sale_price' => 'integer',
        'end_price' => 'integer',
        'margin' => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'li',
        'mark',
        'model',
        'year',
        'color',
        'race',
        'vin',
        'lot',
        'port',
        'form',
        'tracking',
        'line',
        'status_id',
        'people_id',
        'docs_location_id',
        'start_price',
//        'sale_price',
        'end_price',
        'margin',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function status() {
        return $this->belongsTo(CarStatus::class)->withDefault(function(CarStatus $status) {
            $status->value = '';
        });
    }

    public function people() {
        return $this->belongsTo(People::class)->withDefault(function(People $people) {
            $people->firstname = '';
            $people->lastname = '';
        });
    }

    public function docs_location() {
        return $this->belongsTo(CarDocsLocation::class)->withDefault(function(CarDocsLocation $location) {
            $location->value = '';
        });
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function expenses() {
        return $this->hasMany(CarExpense::class)->with('user');
    }

    public function photos() {
        return $this->hasMany(CarPhoto::class)->where('user_id', $this->user_id)->orderBy('id');
    }

    public function images(): HasMany {
        return $this->photos();
    }

    public function main_photo() {
        return $this->hasOne(CarPhoto::class)->where('user_id', $this->user_id)->where('main', true);
    }

    public function main_image(): HasOne {
        return $this->main_photo();
    }

    public function createImageInstance(int $userId = null, int $parentId = null): Image {
        $img = new CarPhoto();

        if(!is_null($userId))
            $img->user_id = $userId;
        elseif($this->user_id)
            $img->user_id = $this->user_id;

        if(!is_null($parentId))
            $img->parent_id = $parentId;
        elseif($this->id)
            $img->parent_id = $this->id;

        $img->car_id = is_null($parentId) ? (int) $this->id : $parentId;

        return $img;
    }

    public function hasMainImage(): bool {
        return true;
    }

    public function getMarkLabelAttribute() {
        return data("cars/marks-{$this->category}", $this->mark);
    }

    public function getModelLabelAttribute() {
        return data("cars/models-{$this->category}/{$this->mark}", $this->model);
    }

    public function getTitleAttribute() {
        $parts = [
            "#{$this->id} {$this->mark_label} {$this->model_label}",
            $this->li === self::LI_IMPORT && strlen($this->lot) ? self::label('lot') . ': ' . $this->lot : null,
        ];
        return join(', ', array_filter($parts));
    }

    public function getColorLabelAttribute() {
        return @data('cars/colors', $this->color)['label'];
    }

    public function getTotalExpensesAttribute() {
        $total = 0;
        foreach($this->expenses as $expense) {
            $total += $expense->amount;
        }
        return $total;
    }

    public function calculateSalePrice() {
        return $this->sale_price = $this->start_price + $this->margin + $this->total_expenses;
    }

}
