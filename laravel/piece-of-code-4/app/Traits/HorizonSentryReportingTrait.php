<?php

namespace App\Traits;

use Exception;
use Lucid\Foundation\InvalidInputException;

/**
 * Trait HorizonSentryReportingTrait
 *
 * @author Illia Balia <illia@invelab.com>
 */
trait HorizonSentryReportingTrait
{
    private bool $shouldExit = false;

    /**
     * Todo: explain why it works this way
     */
    public function shouldReportToSentry()
    {
        $this->shouldExit = true;
    }

    /**
     * @param  string|null  $message
     */
    public function reportToSentry(string $message = null)
    {
        if ($this->shouldExit && app()->environment() !== 'testing') {
            exit($message);
        }
    }

    /**
     * This method is called when the feature fails in the queue.
     * Will only be called if an exception is thrown and not handled.
     *
     * @param  Exception  $exception
     *
     * @throws $exception
     */
    public function failed(Exception $exception)
    {
        if ($exception instanceof InvalidInputException) {
            throw $exception;
        } else {
            $this->reportToSentry($exception->getMessage());
        }
    }
}
