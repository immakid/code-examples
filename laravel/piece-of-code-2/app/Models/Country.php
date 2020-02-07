<?php

namespace App\Models;

use App\Acme\Extensions\Database\Eloquent\Model;

/**
 * App\Models\Country
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Country whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Country whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Country whereName($value)
 * @mixin \Eloquent
 */
class Country extends Model {

	/**
	 * @var bool
	 */
	public $timestamps = false;
}
