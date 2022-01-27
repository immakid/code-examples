<?php

namespace App\Messaging\Handlers;

use App\Features\RemoveCachedTalentDataFeature;
use Lucid\Foundation\InvalidInputException;
use Predis\Connection\ConnectionException;
use Lucid\Foundation\ServesFeaturesTrait;

class CachedDataRemovalHandler
{
    use ServesFeaturesTrait;

    /**
     * Handle message.
     *
     * @param PhpAmqpLib\Message\AMQPMessage $msg
     */
    public function handle($msg)
    {
        return $this->serve(RemoveCachedTalentDataFeature::class, compact('msg'));
    }

    /**
     * Handle error.
     *
     * @param \Exception                   $e
     * @param Vinelab\Bowler\MessageBroker $broker
     */
    public function handleError($e, $broker)
    {
        if ($e instanceof InvalidInputException) {
            $broker->rejectMessage();
        }

        if ($e instanceof ConnectionException) {
            sleep(5);
            // Requeue message
            $broker->rejectMessage(true);
        }
    }
}
