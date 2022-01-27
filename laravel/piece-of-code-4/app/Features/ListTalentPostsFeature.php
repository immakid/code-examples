<?php
namespace App\Features;

use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Operations\FetchTalentPostsOperation;
use App\Domains\Graph\Jobs\FetchTalentByIdJob;
use App\Operations\FetchTalentStoriesOperation;
use App\Domains\Instagram\Jobs\ValidatePostTypesJob;
use App\Domains\Instagram\Jobs\FilterPostsByTypeJob;
use App\Domains\Instagram\Jobs\MapPostsToArrayJob;

/**
 * @author Kinane Domloje <kinane@vienlab.com>
 */
class ListTalentPostsFeature extends Feature
{
    private $type;
    private $talentId;

    public function __construct($talentId, $type)
    {
        $this->talentId = $talentId;
        $this->type = strtolower($type);
    }

    public function handle()
    {
        $this->run(ValidatePostTypesJob::class, [
            'type' => $this->type
        ]);

        $talent = $this->run(FetchTalentByIdJob::class, [
            'talentId' => $this->talentId
        ]);

        switch ($this->type) {
            case 'story':
                $posts = $this->run(FetchTalentStoriesOperation::class, compact('talent'));
                break;

            case 'photo':
            case 'video':
            case 'carousel':
                $posts = $this->run(FetchTalentPostsOperation::class, compact('talent'));

                $posts = $this->run(FilterPostsByTypeJob::class, [
                    'posts' => $posts,
                    'type' => $this->type
                ]);

                break;

            default:
                $posts = [];
        }

        $posts = $this->run(MapPostsToArrayJob::class, compact('posts'));

        return $this->run(new RespondWithJsonJob($posts));
    }
}
