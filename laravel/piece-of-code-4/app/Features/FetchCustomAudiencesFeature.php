<?php

namespace App\Features;

use App\Domains\GraphAPI\Jobs\FetchCustomAudiencesJob;
use App\Domains\GraphAPI\Validators\FetchCustomAudiencesValidator;
use Createvo\Support\Domains\Validation\Jobs\ValidateInputJob;
use Createvo\Support\Domains\Http\Jobs\RespondWithJsonJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;

/**
 * Class FetchCustomAudiencesFeature
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class FetchCustomAudiencesFeature extends Feature
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $this->run(ValidateInputJob::class, [
            'validator' => FetchCustomAudiencesValidator::class,
        ]);

        $audiences = $this->run(FetchCustomAudiencesJob::class, [
            'ids' => $request->input('custom_audience_ids'),
        ]);

        return $this->run(RespondWithJsonJob::class, [
            'content' => $audiences,
        ]);
    }
}
