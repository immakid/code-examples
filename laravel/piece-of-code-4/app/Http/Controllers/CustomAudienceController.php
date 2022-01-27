<?php

namespace App\Http\Controllers;

use App\Features\CreateCustomAudienceFeature;
use App\Features\DeleteCustomAudienceFeature;
use App\Features\FetchCustomAudiencesFeature;
use App\Features\UpdateCustomAudienceFeature;
use Illuminate\Http\JsonResponse;
use Lucid\Foundation\Http\Controller;

/**
 * Class CustomAudienceController
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class CustomAudienceController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->serve(FetchCustomAudiencesFeature::class);
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        return $this->serve(CreateCustomAudienceFeature::class);
    }

    /**
     * @return JsonResponse
     */
    public function update()
    {
        return $this->serve(UpdateCustomAudienceFeature::class);
    }

    /**
     * @return JsonResponse
     */
    public function delete()
    {
        return $this->serve(DeleteCustomAudienceFeature::class);
    }
}
