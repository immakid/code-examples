<?php

/*
 * This file is part of the Trellis Instagram Content service.
 *
 * (c) Vinelab <dev@vinelab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Traits;

use Log;
use App\Data\Enums\Proxy;
use InstagramScraper\Instagram;
use App\Exceptions\ProxyServerException;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
trait ProxyTrait
{
    public function enableProxy(Instagram &$instagram)
    {
        $type = config('services.smartproxy.default');

        $this->setup($instagram, $type);

        Log::info("Enabled Datacenter proxy.");
    }

    public function enableResidentialProxy(Instagram &$instagram, bool $enableGeoLocation = true)
    {
        $type = Proxy::RESIDENTIAL();

        if ($enableGeoLocation) {
            // Select a random country from the vicinity.
            // We assume our servers are in Germany/Frankfurt.
            // Short listed countries in the vicinity are France, Netherlands, Germany, Belgium and Poland.
            $country = array_rand(config("services.smartproxy.$type.geography"), 1);

            $type = $type . '.geography.' . $country;
        }

        $this->setup($instagram, $type);

        Log::info("Enabled Residential proxy from $country.");
    }

    public function disableProxy(Instagram &$instagram)
    {
        $instagram::disableProxy();
    }

    private function setup(Instagram &$instagram, string $type)
    {
        $instagram::setProxy([
            'address' => config("services.smartproxy.$type.address"),
            'port'    => config("services.smartproxy.$type.port"),
            'timeout' => 30,
            'auth' => [
                'user' => config("services.smartproxy.username"),
                'pass' => config("services.smartproxy.password"),
                'method' => CURLAUTH_BASIC
            ]
        ]);
    }

    public function handleProxyException(\Exception $e)
    {
        if(strpos($e->getMessage(), '407')) {
            $message = $e->getMessage().': Possibly you ran out of bandwidth. Please review your account consumption.';
            $code = 407;
        } else {
            $message = $e->getMessage();
            $code = $e->getCode();
        }

        throw new ProxyServerException($message, $code);
    }
}
