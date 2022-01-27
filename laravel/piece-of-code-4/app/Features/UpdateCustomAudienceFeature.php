<?php

namespace App\Features;

use App\Domains\GraphAPI\Jobs\UpdateCustomAudienceJob;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use Createvo\Support\Domains\Validation\Jobs\ValidateInputJob;
use App\Domains\Validation\UpdateCustomAudienceValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;

/**
 * Class UpdateCustomAudienceFeature
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class UpdateCustomAudienceFeature extends Feature
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $this->run(ValidateInputJob::class, [
            'validator' => UpdateCustomAudienceValidator::class,
        ]);

        $isUpdated = $this->run(UpdateCustomAudienceJob::class, [
            'customAudienceId' => $request->input('custom_audience_id'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return $this->run(RespondWithJsonJob::class, [
            'content' => $isUpdated
        ]);
    }
}
