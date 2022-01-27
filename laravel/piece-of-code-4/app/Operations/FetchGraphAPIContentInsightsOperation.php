<?php

namespace App\Operations;

use App\Data\Models\Talent;
use App\Domains\GraphAPI\Jobs\ChunkMediaForBatchRequestJob;
use App\Domains\GraphAPI\Jobs\FetchGraphAPIMediaListJob;
use App\Domains\GraphAPI\Jobs\FetchGraphAPIMediaInsightsJob;
use App\Domains\GraphAPI\Jobs\MapBatchContentInsightsJob;
use App\Domains\GraphAPI\Jobs\MapMediaInfoToInsightsJob;
use App\Exceptions\InstagramGraphAPIException;
use Lucid\Foundation\Operation;

class FetchGraphAPIContentInsightsOperation extends Operation
{
    /**
     * @var Talent
     */
    private $talent;

    /**
     * FetchGraphAPIContentInsightsOperation constructor.
     *
     * @param  Talent  $talent
     */
    public function __construct(Talent $talent)
    {
        $this->talent = $talent;
    }

    /**
     * @return array|null
     */
    public function handle(): ?array
    {
        try {
            $mediaIterator = $this->run(FetchGraphAPIMediaListJob::class, [
                'talent' => $this->talent
            ]);

            $chunkedMediaIterator = $this->run(ChunkMediaForBatchRequestJob::class,
                compact('mediaIterator')
            );

            $contentInsights = [];
            // We'll fetch insights for media in batches of 50 which is the limit for Batch API
            foreach ($chunkedMediaIterator as $mediaChunk) {
                $batchResponse = $this->run(FetchGraphAPIMediaInsightsJob::class, [
                    'talent' => $this->talent,
                    'mediaChunk' => $mediaChunk,
                ]);

                $insightsChunk = $this->run(MapBatchContentInsightsJob::class,
                    compact('batchResponse')
                );

                $contentInsightsChunk = $this->run(MapMediaInfoToInsightsJob::class,
                    compact('mediaChunk', 'insightsChunk')
                );

                array_push($contentInsights, ...$contentInsightsChunk);
            }

            return $contentInsights;

        } catch (InstagramGraphAPIException $e) {
            $this->run(HandleInstagramGraphAPIExceptionsOperation::class, [
                'exception' => $e,
                'talent' => $this->talent
            ]);

            return null;
        }
    }
}
