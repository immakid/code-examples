<?php

namespace App\Models\Dealership;

use App\Models\Model;

/**
 * Class Transmission
 *
 * In future, attach this to cars.
 *
 * @package App\Models\Clients
 */
class Transmission extends Model
{
    /**
     * Define default transmission type.
     */
    const DEFAULT = 1;

    /**
     * Define DB model table.
     *
     * @var string
     */
    protected $table = 'transmissions';

    /**
     * Protect attributes from mass-assigment.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Define visible atrributes.
     *
     * @var array
     */
    protected $visible = ['id', 'name'];
}