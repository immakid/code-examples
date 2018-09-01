<?php

namespace App\Modules\Integration\Modules\CoinMarketCap\Models;

use App\Components\Models\FieldsLoaderTrait;
use yii\base\Model;

/**
 * Class GlobalModel
 * @package App\Modules\Integration\Modules\CoinMarketCap\Models
 */
class GlobalModel extends Model
{
    use FieldsLoaderTrait;

    /**
     * @return array
     */
    public function rules() 
    {
        return [
        ];
    }

    /**
     * @return array
     */
    public function fields() 
    {
        return [];
    }


}