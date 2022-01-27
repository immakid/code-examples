<?php

namespace App\Data\Repositories;

use Predis\Response\Status as PredisStatus;

class BaseRepository
{
    /**
     * Check if all pipeline commands did run successfully.
     *
     * @param $response
     * @return bool
     */
    protected function isPipelineExecutionSuccessful($response): bool
    {
        $response = is_array($response) ? $response : (array) $response;

        foreach ($response as $status) {
            if ($status instanceof PredisStatus) {
                $status = $status->getPayload() === 'OK';
            }

            if ($status === false) {
                return false;
            }
        }

        return true;
    }
}
