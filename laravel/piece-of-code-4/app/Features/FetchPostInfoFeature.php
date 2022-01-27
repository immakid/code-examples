<?php

namespace App\Features;

use App\Domains\Graph\Jobs\FetchTalentJob;
use Lucid\Foundation\Feature;
use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Operations\FetchTalentStoriesOperation;
use App\Operations\FetchTalentPostInfoOperation;
use App\Exceptions\InstagramStoryNotFoundException;
use App\Domains\Instagram\Jobs\ValidatePostTypesJob;
use App\Domains\Instagram\Jobs\FindPostWithShortcodeJob;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class FetchPostInfoFeature extends Feature
{
    /** @var string */
    private $talentId;

    /** @var string */
    private $type;

    /** @var string */
    private $shortcode;

    /**
     * FetchPostInfoFeature constructor.
     *
     * @param string $talentId
     * @param string $type
     * @param string $shortcode
     */
    public function __construct(string $talentId, string $type, string $shortcode)
    {
        $this->talentId = $talentId;
        $this->type = $type;
        $this->shortcode = $shortcode;
    }

    /**
     * @return mixed
     * @throws \App\Exceptions\InstagramStoryNotFoundException
     */
    public function handle()
    {
        $this->run(ValidatePostTypesJob::class, [
            'type' => $this->type
        ]);

        $talent = $this->run(FetchTalentJob::class, [
            'talentId' => $this->talentId,
            'withHidden' => true,
        ]);

        switch ($this->type) {
            case 'story':
                $posts = $this->run(FetchTalentStoriesOperation::class, compact('talent'));

                $post = $this->run(FindPostWithShortcodeJob::class, [
                    'posts' => $posts,
                    'shortcode' => $this->shortcode
                ]);

                if(!$post) {
                    throw new InstagramStoryNotFoundException();
                }
                break;

            case 'photo':
            case 'video':
            case 'carousel':
                $post = $this->run(FetchTalentPostInfoOperation::class, [
                    'talent' => $talent,
                    'shortcode' => $this->shortcode
                ]);
                break;

            default:
                $post = [];
        }

        return $this->run(new RespondWithJsonJob($post));
    }
}
