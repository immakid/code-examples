<?php

namespace App\Http\Controllers;

use App\Features\ValidateAdAccountFeature;
use Illuminate\Http\JsonResponse;
use Lucid\Foundation\Http\Controller;

/**
 * Class AdAccountsController
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class AdAccountsController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function validateAdAccount(): JsonResponse
    {
        return $this->serve(ValidateAdAccountFeature::class);
    }
}
