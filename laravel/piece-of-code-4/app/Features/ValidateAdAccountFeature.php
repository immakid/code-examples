<?php

namespace App\Features;

use App\Domains\Validation\AdAccountIdValidator;
use Createvo\Support\Domains\Validation\Jobs\ValidateInputJob;
use App\Operations\ValidateAdAccountIdOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;

/**
 * Class ValidateAdAccountFeature
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class ValidateAdAccountFeature extends Feature
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $this->run(ValidateInputJob::class, [
            'validator' => AdAccountIdValidator::class,
        ]);

        $isAdAccountValid = $this->run(ValidateAdAccountIdOperation::class, [
            'adAccountId' => $request->input('ad_account_id'),
        ]);

        return $this->run(RespondWithJsonJob::class, [
            'content' => $isAdAccountValid
        ]);
    }
}
