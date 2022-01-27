<?php

namespace App\Http\Controllers;

use App\Features\ConfigureBusinessManagerToAdvertiseForPageFeature;
use Illuminate\Http\JsonResponse;
use Lucid\Foundation\ServesFeaturesTrait;

/**
 * Class BusinessManagerController
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class BusinessManagerController extends Controller
{
    use ServesFeaturesTrait;

    /**
     * @return JsonResponse
     */
    public function configureBusinessManagerToAdvertiseForPage(): JsonResponse
    {
        return $this->serve(ConfigureBusinessManagerToAdvertiseForPageFeature::class);
    }
}
