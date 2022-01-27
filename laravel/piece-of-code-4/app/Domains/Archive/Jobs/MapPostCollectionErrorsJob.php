<?php
namespace App\Domains\Archive\Jobs;

use Lucid\Foundation\Job;

class MapPostCollectionErrorsJob extends Job
{
    private $posts;

	private $campaignId;

	private $message;

    private $code;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($posts, $campaignId, $message, $code)
    {
        $this->posts = $posts;
        $this->campaignId = $campaignId;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $collectionErrors = [];

        foreach ($this->posts as $post) {
            $collectionError['campaign_id'] = $this->campaignId;
            $collectionError['post_id'] = $post->id;
            $collectionError['shortcode'] = $post->shortcode;
            $collectionError['error']['code'] = $this->code;
            $collectionError['error']['message'] = $this->message;
            $collectionError['error']['invalid'] = true;

            array_push($collectionErrors, $collectionError);
        }

        return $collectionErrors;
    }
}
