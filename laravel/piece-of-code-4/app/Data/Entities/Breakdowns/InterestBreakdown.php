<?php

namespace App\Data\Entities\Breakdowns;

use App\Traits\EstimatesReach;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class InterestBreakdown extends Collection
{
    use EstimatesReach;

    public static function makeFromSocialDataAudienceInsights(array $audienceInsights, array $interests)
    {
        $flatInterestBreakdown = self::getRawInterestBreakdown($audienceInsights, $interests);

        return new self(collect($interests)->map(function ($interest) use ($flatInterestBreakdown) {
            $found = $flatInterestBreakdown->whereIn('name', $interest['instagram']);

            return [
                'interest_id' => $interest['id'],
                'value' => $found->sum('value'),
                'title' => $interest['title'],
            ];
        }));
    }

    /**
     * @param  array  $audienceInsights
     * @param  array  $interests
     * @return Collection
     */
    protected static function getRawInterestBreakdown(array $audienceInsights, array $interests): Collection
    {
        $interestInsights = collect(Arr::get($audienceInsights, 'audience_followers.data.audience_interests', []));

        return collect($interests)
            ->flatMap(function ($interest) {
                return $interest['instagram'];
            })
            ->map(function ($interest) use ($interestInsights) {
                /** @var array $found */
                $found = $interestInsights->firstWhere('name', $interest);

                return [
                    'name' => $interest,
                    'value' => $found ? self::estimateReachPercentage($found) : static::$defaultPercentage,
                ];
            });
    }
}
