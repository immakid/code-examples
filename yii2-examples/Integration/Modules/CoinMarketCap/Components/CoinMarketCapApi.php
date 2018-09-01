<?php
/**
 * @author Silchenko Vlad <v.silchenko@minexsystems.com>
 * @date: 14.03.18 - 18:37
 */
namespace App\Modules\Integration\Modules\CoinMarketCap\Components;

use App\Modules\Integration\Modules\CoinMarketCap\Models\TickerModel;
use Curl\Curl;
use yii\base\BaseObject;
use yii\base\UserException;
use yii\helpers\Json;

class CoinMarketCapApi extends BaseObject
{
    private const API_URL = 'https://api.coinmarketcap.com/v1/';

    private const TICKER_MNX = 'ticker/minexcoin/?convert=ETH';

    /**
     * @return TickerModel
     * @throws UserException
     */
    public static function getTicker(): TickerModel 
    {
        $data = self::getCurl()->get(self::API_URL . self::TICKER_MNX);
        //For debug

        //$res = file_put_contents(__DIR__.'/data.json', json_encode($data));
        //$data = json_decode(file_get_contents(__DIR__.'/data.json'));

        if(!isset($data[0])) {
           throw new UserException('Error to parse response  from CoinMarketCap Api');
        }
        $model = new TickerModel();
        $model->load((array)$data[0], '');
        $model->createdAt = time();
        
        if(!$model->validate()) {
            throw new UserException('Error to validate data from CoinMarketCap:' . Json::encode($model->getErrors()));
        }
        return $model;

    }
    
    /**
     * @return Curl
     * @throws \ErrorException
     */
    private static function getCurl(): Curl 
    {
        return new Curl();
    }
}