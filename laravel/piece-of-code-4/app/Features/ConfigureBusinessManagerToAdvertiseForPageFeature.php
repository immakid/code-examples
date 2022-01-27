<?php

namespace App\Features;

use App\Domains\GraphAPI\Jobs\AssignBusinessManagerSystemUserToFacebookPageJob;
use App\Domains\GraphAPI\Jobs\GrantAccessForBusinessManagerJob;
use App\Domains\GraphAPI\Validators\ManageFacebookPageValidator;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use Createvo\Support\Domains\Validation\Jobs\ValidateInputJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;

/**
 * Class ConfigureBusinessManagerToAdvertiseForPageFeature
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class ConfigureBusinessManagerToAdvertiseForPageFeature extends Feature
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $this->run(ValidateInputJob::class, [
            'validator' => ManageFacebookPageValidator::class,
        ]);

        $this->run(GrantAccessForBusinessManagerJob::class, [
            'accessToken' => $request->input('access_token'),
            'pageId' => $request->input('fb_platform_id'),
            'businessManagerId' => config('business_manager.vinelab_business_manager.id'),
        ]);

        $this->run(AssignBusinessManagerSystemUserToFacebookPageJob::class, [
            'accessToken' => config('business_manager.vinelab_business_manager.system_user.access_token'),
            'pageId' => $request->input('fb_platform_id'),
            'userId' => config('business_manager.vinelab_business_manager.system_user.id'),
        ]);

        return $this->run(RespondWithJsonJob::class, [
            'content' => true,
        ]);
    }
}
