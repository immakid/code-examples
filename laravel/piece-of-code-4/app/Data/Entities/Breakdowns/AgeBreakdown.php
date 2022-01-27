<?php

namespace App\Data\Entities\Breakdowns;

use App\Data\Entities\Insights\GraphApiInsight;
use App\Traits\EstimatesReach;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AgeBreakdown extends Collection
{
    use EstimatesReach;

    /**
     * @param  array  $audienceInsights
     * @param  array  $ageGroups
     * @return AgeBreakdown
     */
    public static function makeFromSocialDataAudienceInsights(array $audienceInsights, array $ageGroups): AgeBreakdown
    {
        $ageGroupInsights = collect(Arr::get($audienceInsights, 'audience_followers.data.audience_ages', []));
        $ageGroupInsights = self::normalizeAgeGroupInsights($ageGroupInsights);

        return new self(collect($ageGroups)->map(function ($ageGroup) use ($ageGroupInsights) {
            /** @var array $found */
            $found = $ageGroupInsights->firstWhere('code', $ageGroup);

            return [
                'age_group' => $ageGroup,
                'value' => $found ? self::estimateReachPercentage($found) : static::$defaultPercentage,
            ];
        }));
    }

    /**
     * Divides 45-64 group into 2 age groups (45-54 and 55-64) and renames 65- to 65+
     *
     * @param  Collection  $ageGroupInsights
     * @return Collection
     */
    protected static function normalizeAgeGroupInsights(Collection $ageGroupInsights): Collection
    {
        return $ageGroupInsights->reduce(function (Collection $carry, $ageGroup) {
            if ($ageGroup['code'] == '45-64') {
                $carry->push([
                    'code' => '45-54',
                    'weight' => Arr::get($ageGroup, 'weight') / 2,
                ]);

                $carry->push([
                    'code' => '55-64',
                    'weight' => Arr::get($ageGroup, 'weight') / 2,
                ]);
            } else {
                $carry->push($ageGroup);
            }

            return $carry;
        }, new Collection());
    }

    /**
     * @param  array  $audienceInsights
     * @param  array  $ageGroups
     * @return static
     * @throws \Exception
     */
    public static function makeFromGraphApiAudienceInsights(array $audienceInsights, array $ageGroups): self
    {
        $graphApiInsight = GraphApiInsight::makeFromInsights('audience_gender_age', $audienceInsights);

        $groups = collect($ageGroups)->flip()->transform(fn($group) => 0);
        $rangesToExclude = [];

        foreach ($graphApiInsight->value as $key => $value) {
            $range = preg_replace('/\w\./', null, $key);

            if (isset($groups[$range])) {
                // Graph API Insight group matches Trellis group
                $groups[$range] += $value;
            } else {
                $rangesToExclude[] = $range;
                $groups[$range] = $value;
            }
        }

        $totalValue = $groups->values()->sum();

        // Exclude ranges outside of the expected age groups
        foreach ($rangesToExclude as $range) {
            unset($groups[$range]);
        }

        $data = [];
        foreach ($groups as $range => $value) {
            $data[] = [
                'age_group' => $range,
                'value' => $value * 100 / $totalValue,
            ];
        }

        return new self($data);
    }
}
