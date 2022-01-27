<?php
namespace App\Http\Controllers;

use App\Features\FetchAccountFollowersCountFeature;
use App\Features\FetchAccountMediaFeature;
use Illuminate\Http\Request;
use Lucid\Foundation\Http\Controller;
use App\Features\FetchPostInfoFeature;
use App\Features\ListTalentPostsFeature;
use App\Features\GetInstagramAccountDataFeature;
use App\Features\ScheduleLatestStoryInsightsCollectionFeature;
use App\Features\RemoveScheduledStoryInsightsCollectionFeature;

class ContentController extends Controller
{
    /**
     * Get Instagram Account.
     *
     * @api {get} accounts/{username} account
     * @apiName ListInterests
     * @apiGroup Instagram
     * @apiVersion 1.0.0
     *
     * @apiParam (URL parameters) {string} username              The instagram account to be returned
     *
     * @apiSuccessExample {json} Response
     * HTTP/1.1 200 OK
     *
     *
     * {
     *  "data": {
     *      "id": "194895440",
     *      "username": "elissazkh",
     *      "fullName": "Elissa",
     *      "profilePicUrl": "https://igcdn-photos-g-a.akamaihd.net/hphotos-ak-xfa1/t51.2885-19/s150x150/14374392_1778569972381990_1076947016_a.jpg",
     *      "biography": "Intl. Lebanese artist, winner of regional&global entertainment awards. Love the center-stage, the catwalk&my music album #saharnayaleil",
     *      "externalUrl": "http://www.twitter.com/elissakh",
     *      "followsCount": 168,
     *      "followedByCount": 6880112,
     *      "mediaCount": 3475,
     *      "isPrivate": false,
     *      "isVerified": true
     *   }
     *  "status": 200
     * }
     *
     * @apiError account not found
     *
     * @apiErrorExample {json} Account with given username does not exist:
     * HTTP/1.1 400 Not Found
     *
     * {
     * "status": 400
     * "error": {
     *      "code": 400,
     *      "message": "Account with given username does not exist."
     *  }
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAccountData($username)
    {
        return $this->serve(GetInstagramAccountDataFeature::class, ['username' => $username]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getAccountFollowersCount(Request $request)
    {
        return $this->serve(FetchAccountFollowersCountFeature::class, ['request' => $request]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getAccountMedia(Request $request)
    {
        return $this->serve(FetchAccountMediaFeature::class, ['request' => $request]);
    }

    /**
     * @param string $talentId
     * @param string $type
     * @param string $shortcode
     * @return \Illuminate\Http\Response
     */
    public function getPostInfo(string $talentId, string $type, string $shortcode)
    {
        return $this->serve(FetchPostInfoFeature::class, compact('talentId', 'type', 'shortcode'));
    }

    public function listPosts($talentId, $type)
    {
        return $this->serve(ListTalentPostsFeature::class, compact('talentId', 'type'));
    }

    public function scheduleStoryInsightsCollection($talentId)
    {
        return $this->serve(ScheduleLatestStoryInsightsCollectionFeature::class, compact('talentId'));
    }

    public function removeScheduledStoryInsightsCollection($talentId, $storyId)
    {
        return $this->serve(RemoveScheduledStoryInsightsCollectionFeature::class, compact('talentId', 'storyId'));
    }
}
