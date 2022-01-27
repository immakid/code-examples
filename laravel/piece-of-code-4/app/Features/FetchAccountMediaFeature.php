<?php

namespace App\Features;

use App\Domains\GraphAPI\Jobs\FetchTalentMediasJob;
use App\Domains\GraphAPI\Jobs\MapMediaForMediaSummaryListJob;
use App\Domains\Validation\Jobs\ValidateFetchAccountMediaJob;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;

/**
 * Class FetchAccountFollowersCountFeature
 *
 * @package App\Features
 */
class FetchAccountMediaFeature extends Feature
{
    public function handle(Request $request)
    {
        $this->run(ValidateFetchAccountMediaJob::class, [
            'input' => $request->input()
        ]);

        $posts = $this->run(FetchTalentMediasJob::class, [
            'graphPlatformId' => $request->input('platform_id'),
            'accessToken' => $request->input('access_token'),
            'limit' => $request->input('limit', 1)
        ]);
        $posts = $this->run(MapMediaForMediaSummaryListJob::class, [
            'posts' => $posts,
            'fields' => ['id', 'timestamp']
        ]);

        return $this->run(new RespondWithJsonJob([
            'media' => $posts->toArray()
        ]));
    }
}
