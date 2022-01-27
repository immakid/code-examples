<?php

namespace App\Features;

use App\Data\Enums\EngagementEventName;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Domains\Validation\CustomAudienceValidator;
use Createvo\Support\Domains\Validation\Jobs\ValidateInputJob;
use App\Operations\CreateCustomAudienceOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;

/**
 * Class CreateCustomAudienceFeature
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class CreateCustomAudienceFeature extends Feature
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $this->run(ValidateInputJob::class, [
            'validator' => CustomAudienceValidator::class,
        ]);

        $customAudienceId = $this->run(CreateCustomAudienceOperation::class, [
            'description' => $request->input('description') ?? '',
            'name' => $request->input('audience_name'),
            'postsIds' => collect($request->input('posts')),
            'adAccountId' => $request->input('ad_account_id'),
            'engagement' => EngagementEventName::getByValue($request->input('engagement')),
        ]);

        return $this->run(RespondWithJsonJob::class, [
            'content' => [
                'custom_audience_id' => $customAudienceId,
            ],
        ]);
    }
}
