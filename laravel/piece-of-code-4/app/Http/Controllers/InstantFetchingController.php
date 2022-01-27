<?php
namespace App\Http\Controllers;

use App\Features\FetchAudienceDataInstantlyFeature;
use Illuminate\Http\Request;
use Lucid\Foundation\Http\Controller;

class InstantFetchingController extends Controller
{
    /**
     * serving FetchEstimatedReachInstantlyFeature.
     *
     * @param $talentId
     * @return \Illuminate\Http\Response
     */
    public function index($talentId)
    {
        return $this->serve(FetchAudienceDataInstantlyFeature::class, ['talentId' => $talentId]);
    }
}
