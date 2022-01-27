<?php
namespace App\Domains\Archive\Jobs;

use App\Data\Entities\Insights\PostInsights;
use Lucid\Foundation\Job;

class MapPostsInsightsJob extends Job
{
	private $medias;

	private $followers;

	private $collaboration;

    private $timestamp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($medias, $followers, $collaboration, $timestamp)
    {
        $this->medias = $medias;
        $this->followers = $followers;
        $this->collaboration = $collaboration;
        $this->timestamp = $timestamp;
    }

    /**
     * Execute the job.
     *
     * @return array[]
     */
    public function handle()
    {
        $insights = [];
        $postsFound = [];

        if ($this->medias) {
            foreach($this->medias as $media) {
                if(in_array($media['code'], $this->collaboration->getPostsShortcodes())) {
                    $indices = array_keys($this->collaboration->getPostsShortcodes(), $media['code']);

                    foreach ($indices as $index) {
                        $post = $this->collaboration->posts[$index];

                        $insights[] = [
                            'post_id' => $post->id,
                            'shortcode' => $post->shortcode,
                            'followers' => $this->followers,
                            'fetched_at' => $this->timestamp,
                            'insights' => PostInsights::makeFromScraper($media)
                        ];

                        $postsFound[] = $post;
                    }
                }
            }
        }

        return [$insights, $postsFound];
    }
}
