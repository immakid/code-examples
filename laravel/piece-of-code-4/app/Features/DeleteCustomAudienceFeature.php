<?php

namespace App\Features;

use App\Domains\GraphAPI\Jobs\DeleteCustomAudienceJob;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Domains\Validation\DeleteCustomAudienceValidator;
use Createvo\Support\Domains\Validation\Jobs\ValidateInputJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;

/**
 * Class DeleteCustomAudienceFeature
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class DeleteCustomAudienceFeature extends Feature
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $this->run(ValidateInputJob::class, [
            'validator' => DeleteCustomAudienceValidator::class,
        ]);

        $isDeleted = $this->run(DeleteCustomAudienceJob::class, [
            'customAudienceId' => $request->input('custom_audience_id')
        ]);

        return $this->run(RespondWithJsonJob::class, [
            'content' => $isDeleted
        ]);
    }
}
