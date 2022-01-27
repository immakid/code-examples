<?php

namespace App\Operations;

use App\Data\Models\Talent;
use App\Domains\Graph\Jobs\UpdateSocialAccountAccessJob;
use App\Domains\GraphAPI\Jobs\FetchTalentAccountJob;
use App\Exceptions\InstagramGraphAPIException;
use App\Exceptions\RevokedTalentAccountException;
use Lucid\Foundation\Operation;
use App\Data\Enums\Source;

class CheckTalentAccountGraphAccessOperation extends Operation
{
    /**
     * @var Talent
     */
    private $talent;

    /**
     * CheckInstagramAccountExistenceOperation constructor.
     *
     * @param  Talent  $talent
     */
    public function __construct(Talent $talent)
    {
        $this->talent = $talent;
    }

    /**
     * @throws InstagramGraphAPIException
     * @throws RevokedTalentAccountException
     */
    public function handle()
    {
        try {
            $this->run(FetchTalentAccountJob::class, ['talent' => $this->talent]);
        } catch (InstagramGraphAPIException $exception) {
            if (
                $this->isAccountNotFound($exception)
                || $this->isInvalidAccessToken($exception)
            ) {
                $this->run(UpdateSocialAccountAccessJob::class, [
                    'reference' => $this->talent->graphPlatformId,
                    'access' => false,
                    'source' => Source::GRAPH_API(),
                ]);

                throw new RevokedTalentAccountException($this->talent, $exception);
            }

            throw $exception;
        }
    }

    /**
     * @param  InstagramGraphAPIException  $exception
     *
     * @return bool
     */
    protected function isAccountNotFound(InstagramGraphAPIException $exception): bool
    {
        return $exception->code == 100 && $exception->subcode == 33;
    }

    /**
     * @param  InstagramGraphAPIException  $exception
     *
     * @return bool
     */
    protected function isInvalidAccessToken(InstagramGraphAPIException $exception): bool
    {
        return $exception->code == 190;
    }
}
