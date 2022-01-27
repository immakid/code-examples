<?php

namespace App\Features;

use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Domains\Rabbitmq\Jobs\ExtractRabbitmqMessageBodyJob;
use App\Domains\Talent\Jobs\GetSpecificTalentFromRedisJob;
use App\Domains\Talent\Jobs\RemoveInstantTalentFromRedisJob;
use App\Domains\Talent\Jobs\RemoveSpecificTalentFromRedisJob;
use App\Domains\Talent\Jobs\ValidateCachedDataRemovalInputJob;
use Lucid\Foundation\Feature;

class RemoveCachedTalentDataFeature extends Feature
{
    private $msg;

    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    public function handle()
    {
        $body = $this->run(ExtractRabbitmqMessageBodyJob::class, ['msg' => $this->msg]);

        $this->run(ValidateCachedDataRemovalInputJob::class, ['input' => $body]);

        $talentWithContent = $this->run(GetSpecificTalentFromRedisJob::class, ['talentId' => $body['id']]);

        if ($talentWithContent) {
            // remove the talent from Redis
            $this->run(RemoveSpecificTalentFromRedisJob::class, ['talent' => $talentWithContent]);

            //remove talent from redis talents list
            $this->run(RemoveInstantTalentFromRedisJob::class, [
                'currentInstantTalent' => $talentWithContent,
            ]);
        }

        return $this->run(new RespondWithJsonJob(true));
    }
}
