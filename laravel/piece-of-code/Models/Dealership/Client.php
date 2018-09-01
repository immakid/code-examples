<?php

namespace App\Models\Dealership;

use App\Models\User;
use App\Traits\HasComments;
use App\Traits\HasUser;
use Illuminate\Database\Eloquent\Model;
use function collect;
use function data;

/**
 * Class Client
 *
 * @package App\Models\Clients
 */
class Client extends Model
{
    use HasComments, HasUser;

    /**
     * Define client`s default status.
     */
    const DEFAULT_STATUS = self::STATUS_COLD;

    /**
     * Define "cold" status.
     */
    const STATUS_COLD = 'cold';

    /**
     * Define "warm" status
     */
    const STATUS_WARM = 'warm';

    /**
     * Define "hot" status.
     */
    const STATUS_HOT = 'hot';

    /**
     * Define available client statuses.
     *
     * TODO shame as well, refactor.
     */
    const STATUSES = [
        'cold' => ['id' => self::STATUS_COLD, 'name' => 'Холодный'],
        'warm' => ['id' => self::STATUS_WARM, 'name' => 'Теплый'],
        'hot'  => ['id' => self::STATUS_HOT, 'name' => 'Горячий'],
    ];

    /**
     * Define DB model table.
     *
     * @var string
     */
    protected $table = 'dealership_clients';

    /**
     * Protect attributes from mass-assigment.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Define attributes type casting.
     *
     * @var array
     */
    protected $casts = [
        'id'         => 'int',
        'user_id'    => 'int',
        'creator_id' => 'int,',
    ];

    /**
     * Load data on each call.
     *
     * @var array
     */
    protected $with = ['transmission', 'creator', 'user'];

    /**
     * Append attributes on each call.
     *
     * @var array
     */
    protected $appends = ['years', 'budget', 'city_name', 'status_name', 'car_name'];

    /**
     * Client creator.
     *
     * @return mixed
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Define current client transmission relationship.
     *
     * @return mixed
     */
    public function transmission()
    {
        return $this->belongsTo(Transmission::class, 'transmission_id');
    }

    /**
     * Define client car requests
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carRequests()
    {
        return $this->hasMany(CarRequest::class);
    }

    /**
     * Get city name.
     *
     * @return mixed
     */
    public function getCityNameAttribute()
    {
        return collect(data('clients/cities'))->filter(function ($city, $key) {
            return $key == $this->city;
        })->values()[0]; // TODO  refactor this shame.
    }

    /**
     * Get city name.
     *
     * @return mixed
     */
    public function getStatusNameAttribute()
    {
        return self::STATUSES[$this->status]['name'] ?? self::STATUSES['cold']['name'];
    }

    /**
     * Simplify years display
     *
     * @return mixed|null|string
     */
    public function getYearsAttribute()
    {
        return $this->year_from.'-'.$this->year_to;
    }

    /**
     * Simplify bugdet display
     *
     * @return mixed|null|string
     */
    public function getBudgetAttribute()
    {
        return $this->budget_from.'-'.$this->budget_to;
    }

    /**
     * Define custom attribute for default car request.
     *
     * @return string
     */
    public function getCarNameAttribute()
    {
        if ($this->car_default !== null) {
            $items = $this->carRequests()->find($this->car_default);

            return $items->mark.' '.$items->carModel;
        }

        return 'No default car.';
    }
}