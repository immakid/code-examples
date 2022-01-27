<?php

namespace App\Features;

use Illuminate\Http\Request;
use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Domains\Talent\Jobs\ExtractTalentDataJob;
use App\Domains\Date\Jobs\GetCurrentDateTimeStringJob;
use App\Operations\FetchAudienceDataInstantlyOperation;
use App\Operations\NotifyEndOfTalentCollectionEventInstantlyOperation;

class FetchAudienceDataInstantlyFeature extends Feature
{
    public function handle(Request $request)
    {
        // // track start time
        // $startTime = $this->run(GetCurrentDateTimeStringJob::class);

        // $data = json_decode(json_encode($request->input()));

        // $talent = $this->run(ExtractTalentDataJob::class, compact('data'));

        // if ($talent) {
        //     // call the queueable operation to process instagram audience data for the incoming talent
        //     $audienceData = $this->run(FetchAudienceDataInstantlyOperation::class, compact('talent'));

        //     // track end time
        //     $endTime = $this->run(GetCurrentDateTimeStringJob::class);

        //     $this->run(NotifyEndOfTalentCollectionEventInstantlyOperation::class, [
        //         'talent' => $talent,
        //         'startTime' => $startTime,
        //         'endTime' => $endTime,
        //         'didCollectAudienceInsights' => $audienceData ? true : false,

        //     ]);
        // }

        return $this->run(new RespondWithJsonJob('Data received successfully', 200));
    }
}
