<?php

namespace App\Modules\Integration\Modules\CoinMarketCap;

/**
 * Class Module
 * @package App\Modules\Integration\Modules\CoinMarketCap
 */
class Module extends \yii\base\Module
{


    /**
     * @inheritdoc
     */
    public $controllerNamespace = __NAMESPACE__.'\Controllers';

    /**
     *
     */
    public function init() 
    {
        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = __NAMESPACE__.'\Commands';
        }
    }

}