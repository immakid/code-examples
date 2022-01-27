<?php
namespace App\Operations;

use App\Data\Entities\Insights\TalentPerformanceInsights;
use App\Data\Enums\FileExtension;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Path;
use App\Data\Enums\SocialPlatform;
use App\Data\Enums\Source;
use Createvo\Support\Domains\GCS\Jobs\UploadToGoogleCloudStorageJob;
use Illuminate\Support\Facades\Config;
use Lucid\Foundation\Operation;

class ArchiveInsightsOperation extends Operation
{
    protected TalentPerformanceInsights $insights;
    protected Source $source;
    protected InsightsType $type;

    /**
     * ArchivePerformanceInsightsOperation constructor.
     *
     * @param  TalentPerformanceInsights  $insights
     * @param  Source  $source
     */
    public function __construct(TalentPerformanceInsights $insights, Source $source, InsightsType $type)
    {
        $this->insights = $insights;
        $this->source = $source;
        $this->type = $type;
    }

    public function handle()
    {
        $path = Path::ARCHIVE_DAILY_INSIGHTS(
            $this->insights->talentId,
            SocialPlatform::INSTAGRAM(),
            $this->source,
            $this->type,
            $this->insights->fetchedAt,
            FileExtension::JSON()
        );

        $this->run(UploadToGoogleCloudStorageJob::class, [
            'bucket' => Config::get('google_cloud_storage.archive_bucket_name'),
            'path' => $path,
            'data' => $this->insights->original(),
        ]);

        return true;
    }
}
