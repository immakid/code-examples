<?php

namespace App\Middlewares;

use Illuminate\Support\Facades\Redis;

/**
 * Class RateLimited
 * Introduced in Laravel 8, this middleware would only be applicable when:
 * 1. We upgrade to Laravel 8.
 * 2. Lucid units are compatible/compliant with Laravel jobs.
 *
 * @package Trellis\Instagram\Domains
 */
class RateLimited
{
    private int $limit;

    private int $period;

    public function __construct(int $limit, int $period)
    {
        $this->limit = $limit;
        $this->period = $period;
    }

    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, callable $next)
    {
        Redis::throttle($job->queue)
            ->block(0)->allow($this->limit)->every($this->period)
            ->then(function () use ($job, $next) {
                // Lock obtained...
                $next($job);
            }, function () use ($job) {
                // Could not obtain lock...
                $job->release($this->limit);
            });
    }
}
