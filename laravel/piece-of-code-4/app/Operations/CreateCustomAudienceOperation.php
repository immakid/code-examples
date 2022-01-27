<?php

namespace App\Operations;

use App\Data\Enums\EngagementEventName;
use App\Domains\GraphAPI\Jobs\CreateCustomAudienceOnOurAdAccountJob;
use App\Domains\GraphAPI\Jobs\ShareCustomAudienceWithAdAccountJob;
use Illuminate\Support\Collection;
use Lucid\Foundation\Operation;

/**
 * Class CreateCustomAudienceOperation
 *
 * @author Ivan Hunko <ivan@vinelab.com>
 */
class CreateCustomAudienceOperation extends Operation
{
    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $name;

    /**
     * each item has:
     * [
     *      'id' => 'some-neo4j-post-uuid'
     *      'platform_id' => 'facebook-post-id'
     * ]
     *
     * @var Collection
     */
    protected $postsIds;

    /**
     * @var string
     */
    protected $adAccountId;

    /**
     * @var EngagementEventName
     */
    protected $engagement;

    /**
     * CreateCustomAudienceOperation constructor.
     *
     * @param  string  $description
     * @param  string  $name
     * @param  Collection  $postsIds
     * @param  string  $adAccountId
     * @param  EngagementEventName  $engagement
     */
    public function __construct(
        string $description,
        string $name,
        Collection $postsIds,
        string $adAccountId,
        EngagementEventName $engagement
    ) {
        $this->description = $description;
        $this->name = $name;
        $this->postsIds = $postsIds;
        $this->adAccountId = $adAccountId;
        $this->engagement = $engagement;
    }

    /**
     * @return string
     */
    public function handle(): string
    {
        $customAudienceId = $this->run(CreateCustomAudienceOnOurAdAccountJob::class, [
            'description' => $this->description,
            'postsIds' => $this->postsIds,
            'name' => $this->name,
            'engagement' => $this->engagement,
        ]);

        //throw exception if didn't manage to share
        $this->run(ShareCustomAudienceWithAdAccountJob::class, [
            'customAudienceId' => $customAudienceId,
            'adAccountId' => $this->adAccountId,
        ]);

        return $customAudienceId;
    }
}
