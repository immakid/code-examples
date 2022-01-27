<?php

namespace App\Messaging\Handlers;

use App\Features\FetchPlatformInsightsFeature;
use Createvo\Support\Domains\RabbitMQ\Jobs\ExtractRabbitMQMessageBodyJob;
use Exception;
use Lucid\Foundation\InvalidInputException;
use Lucid\Foundation\JobDispatcherTrait;
use Lucid\Foundation\ServesFeaturesTrait;
use PhpAmqpLib\Message\AMQPMessage;
use Vinelab\Bowler\MessageBroker;

class FetchInsightsWithAuthorizedConnectionMessageHandler
{
    use ServesFeaturesTrait;
    use JobDispatcherTrait;

    /**
     * Handle message.
     *
     * @param  AMQPMessage  $msg
     *
     * @return void
     */
    public function handle(AMQPMessage $msg)
    {
        return $this->serve(FetchPlatformInsightsFeature::class, [
            'message' => $this->run(ExtractRabbitMQMessageBodyJob::class, compact('msg')),
            'isAuthorized' => true,
        ]);
    }

    /**
     * @param  Exception  $e
     * @param  MessageBroker  $broker
     */
    public function handleError(Exception $e, MessageBroker $broker)
    {
        if ($e instanceof InvalidInputException) {
            $broker->rejectMessage();
        } else {
            // Requeue message
            $broker->rejectMessage(true);
        }
    }
}
