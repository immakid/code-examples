<?php

namespace App\Traits;

use App\Exceptions\InstagramGraphAPIException;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Vinelab\Http\Client;
use Vinelab\Http\Response;

/**
 * Trait FetchMediaTrait
 *
 * @package App\Traits
 */
trait FetchMediaTrait
{
    /**
     * is used to set limit per 1 ITERATION when we are fetching talent medias
     */
    private $perPage = 100;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $graphPlatformId;

    /**
     * @param Client $client
     * @param string|null $after
     *
     * @return array
     * @throws InstagramGraphAPIException
     */
    protected function fetchMediaPage(Client $client, string $after = null): array
    {
        /** @var Response $response */
        $response = $client->get([
            'url' => implode('/', [
                Config::get('instagram_content.graph_api.url'),
                Config::get('instagram_content.graph_api.version'),
                $this->graphPlatformId,
                'media',
            ]),
            'params' => [
                'access_token' => $this->accessToken,
                'fields' => Config::get('instagram_content.graph_api.posts.fields'),
                'limit' => $this->perPage,
                'after' => $after,
            ],
            'json' => true,
        ]);

        $decodedResponse = json_decode($response->content(), true);

        if (Arr::has($decodedResponse, 'error')) {
            throw new InstagramGraphAPIException(
                Arr::get($decodedResponse, 'error.message'),
                Arr::get($decodedResponse, 'error.code'),
                Arr::get($decodedResponse, 'error.error_subcode')
            );
        }

        return $decodedResponse;
    }

    /**
     * @param Carbon $date
     *
     * @return bool
     */
    protected function isDateOlderThan90Days(Carbon $date): bool
    {
        return $date->lessThan(Carbon::today()->subDays(90));
    }
}
