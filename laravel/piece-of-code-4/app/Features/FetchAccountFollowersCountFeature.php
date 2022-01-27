<?php
namespace App\Features;

use App\Domains\GraphAPI\Jobs\FetchTalentAccountFollowersJob;
use App\Domains\Validation\Jobs\ValidateFetchAccountFollowersCountJob;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;

/**
 * Class FetchAccountFollowersCountFeature
 * @package App\Features
 */
class FetchAccountFollowersCountFeature extends Feature
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        $this->run(ValidateFetchAccountFollowersCountJob::class, [
            'input' => $request->input()
        ]);

        $followers = $this->run(FetchTalentAccountFollowersJob::class, [
            'graphPlatformId' => $request->input('platform_id'),
            'accessToken' => $request->input('access_token')
        ]);

        return $this->run(new RespondWithJsonJob([
            'followers' => $followers
        ]));
    }
}
