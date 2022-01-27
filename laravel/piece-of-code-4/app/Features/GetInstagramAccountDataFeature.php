<?php

namespace App\Features;

use App\Domains\Http\Jobs\RespondWithJsonJob;
use App\Domains\Instagram\Jobs\GetInstagramAccountByUsernameJob;
use App\Domains\Talent\Jobs\MapAccountJob;
use Lucid\Foundation\Feature;

class GetInstagramAccountDataFeature extends Feature
{
    /**
     * @var string
     */
    private $username;

    /**
     * GetInstagramAccountDataFeature constructor.
     * @param  string  $username
     */
    public function __construct(string $username)
    {
        $this->username = $username;
    }

    public function handle()
    {
        $result = $this->run(GetInstagramAccountByUsernameJob::class, [
            'username' => $this->username,
            'useProxy' => true,
            'residentialProxy' => true,
        ]);

        $account = $this->run(MapAccountJob::class, compact('result'));

        return $this->run(new RespondWithJsonJob($account));
    }
}
