<?php

namespace App\Data\Entities\Breakdowns;

use App\Data\Entities\Insights\GraphApiInsight;
use App\Data\Entities\Insights\SocialDataAudienceInsights;
use App\Data\Enums\Gender;
use App\Traits\EstimatesReach;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GenderBreakdown extends Collection
{
    use EstimatesReach;

    /**
     * @param  SocialDataAudienceInsights  $audienceInsights
     * @param  array  $genders
     * @return Collection|void
     */
    public static function makeFromSocialDataAudienceInsights(array $audienceInsights, array $genders): GenderBreakdown
    {
        $genderInsights = collect(Arr::get($audienceInsights, 'audience_followers.data.audience_genders', []));

        return new self(collect($genders)->mapWithKeys(function ($gender) use ($genderInsights) {
            /** @var array $found */
            $found = $genderInsights->firstWhere('code', self::normalizeGender($gender));

            return [
                $gender => $found ? self::estimateReachPercentage($found) : static::$defaultPercentage,
            ];
        }));
    }

    /**
     * @param  array  $audienceInsights
     * @return GenderBreakdown
     * @throws \Exception
     */
    public static function makeFromGraApiphAudienceInsights(array $audienceInsights)
    {
        $graphApiInsight = GraphApiInsight::makeFromInsights('audience_gender_age', $audienceInsights);

        $male = 0;
        $female = 0;

        foreach ($graphApiInsight->value as $key => $value) {
            if (Str::startsWith($key, 'M')) {
                $male += $value;
            } elseif (Str::startsWith($key, 'F')) {
                $female += $value;
            }
        }

        return new self([
            Gender::MALE => $male * 100 / ($male + $female),
            Gender::FEMALE => $female * 100 / ($male + $female),
        ]);
    }

    /**
     * @param  string  $gender
     * @return string
     */
    protected static function normalizeGender(string $gender): string
    {
        return strtoupper($gender);
    }
}
