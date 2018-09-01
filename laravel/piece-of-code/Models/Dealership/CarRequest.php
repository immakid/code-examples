<?php

namespace App\Models\Dealership;

use Illuminate\Database\Eloquent\Model;
use function data;

class CarRequest extends Model
{
    /**
     * Define model table in DB.
     *
     * @var string
     */
    protected $table = 'dealership_clients_cars_requests';

    /**
     * Guard attributes from mass-assigment.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Hide attributes from JSON
     *
     * @var array
     */
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Append custom attributes to each call.
     *
     * @var array
     */
    protected $appends = ['category', 'mark', 'carModel'];

    /**
     * Define client relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Mark this car request as default for related client.
     *
     * @return  $this
     */
    public function setAsDefault()
    {
        $this->client()->update(['car_default' => $this->id]);

        return $this;
    }

    /**
     * Human-friendly category attribute
     *
     * @return mixed
     */
    public function getCategoryAttribute()
    {
        return data('cars/categories')[$this->category_id];
    }

    /**
     * Human-friendly mark attribute.
     *
     * @return mixed
     */
    public function getMarkAttribute()
    {
        return data("cars/marks-{$this->category_id}")[$this->mark_id];
    }

    /**
     * Human-friendly model attribute.
     *
     * @return mixed
     */
    public function getCarModelAttribute()
    {
        return data("cars/models-{$this->category_id}/{$this->mark_id}")[$this->model_id];
    }
}