<?php

namespace App\Modules\Integration;

/**
 * Class Module
 * @package App\Modules\Finance
 */
class IntegrationModule extends \yii\base\Module
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

        $this->modules = [
            'CoinMarketCap'    => Modules\CoinMarketCap\Module::class,
        ];

    }
}