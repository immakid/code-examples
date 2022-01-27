<?php

namespace App\Data\RabbitMQ\Payloads;

use App\Data\Models\Talent;
use UnexpectedValueException;
use App\Data\Enums\SocialPlatform;
use App\Data\SocialData\ErrorCode;
use App\Data\Models\SocialDataCollectionAttempt;
use Createvo\Support\Interfaces\JsonSerializableInterface;
use Createvo\Support\Traits\JsonSerializableTrait;
use Createvo\Support\Traits\MagicGetterTrait;
use Exception;
use App\Data\Enums\InsightsType;
use App\Data\Enums\Source;
use Illuminate\Support\Arr;

/**
 * @property-read Talent $talent
 * @property-read SocialPlatform $platform
 * @property-read Source $source
 * @property-read InsightsType $insightsType
 * @property-read string $fetchedAt
 * @property-read string $errorPayload
 * @property-read string $errorReason
 * @property-read bool $isSuccess
 */
class FetchResult implements JsonSerializableInterface
{
    use JsonSerializableTrait;
    use MagicGetterTrait;

    private Talent $talent;
    private Source $source;
    private InsightsType $insightsType;
    private string $fetchedAt;
    /**
     * Expected to the encoded JSON
     *
     * @var string|null
     */
    private ?string $errorPayload;
    private ?string $errorReason;
    private SocialPlatform $platform;
    private bool $isSuccess;

    public function __construct(
        Talent $talent,
        SocialPlatform $platform,
        Source $source,
        InsightsType $insightsType,
        string $fetchedAt,
        Exception $exception = null
    )
    {
        $this->talent = $talent;
        $this->platform = $platform;
        $this->source = $source;
        $this->insightsType = $insightsType;
        $this->fetchedAt = $fetchedAt;
        $this->errorPayload = $exception ? $exception->getMessage() : null;
        $this->errorReason = $exception ? $this->extractErrorReason($source, $exception) : null;
        $this->isSuccess = $exception ? false : true;
    }

    public function attempt(): SocialDataCollectionAttempt
    {
        switch ($this->source) {
            case Source::SOCIAL_DATA():
                return new SocialDataCollectionAttempt(
                    [
                        "talent_id" => $this->talent->id,
                        "username" => $this->talent->username,
                        "platform_id" => $this->talent->platformId,
                        "graph_platform_id" => $this->talent->graphPlatformId,
                        "access_token" => $this->talent->accessToken,
                        "access" => $this->talent->access,
                        "graph_access" => $this->talent->graphAccess,
                        "insights_type" => (string)$this->insightsType,
                        "fetched_at" => $this->fetchedAt,
                        "error_payload" => $this->errorPayload,
                        "error_reason" => $this->errorReason,
                        "is_success" => $this->isSuccess,
                    ]
                );
                break;
        }
    }

    private function extractErrorReason(Source $source, Exception $exception): ErrorCode
    {
        switch ($source) {
            case Source::SOCIAL_DATA():
                try {
                    $error = new ErrorCode(Arr::get(json_decode($exception->getMessage(), true), 'error'));
                } catch (UnexpectedValueException $e) {
                    $error = new ErrorCode('unknown');
                }
                break;
            default:
                $error = null;
        }

        return $error;
    }
}
