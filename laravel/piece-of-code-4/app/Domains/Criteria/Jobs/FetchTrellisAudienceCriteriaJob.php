<?php

namespace App\Domains\Criteria\Jobs;

use Illuminate\Support\Arr;
use Lucid\Foundation\Job;
use Trellis\Clients\CriteriaClient;
use Vinelab\Http\Response;

class FetchTrellisAudienceCriteriaJob extends Job
{
    /**
     * Execute the job.
     *
     * @param  CriteriaClient  $client
     * @return array
     */
    public function handle(CriteriaClient $client): array
    {
        /** @var Response $response */
        $response = $client->get([
            'url' => '/criteria/audience/criteria',
            'json' => true,
        ]);

        $decodedResponse = json_decode($response->content(), true);

        return Arr::get($decodedResponse, 'data');
    }
}
