<?php

namespace App\Acme\Repositories;

interface CriteriaInterface {

    /**
     * @return mixed
     */
    public function getCriteria();

    /**
     * @return mixed
     */
    public function clearCriteria();

    /**
     * @param Criteria $criteria
     * @return mixed
     */
    public function pushCriteria(Criteria $criteria);

    /**
     * @param array|string $items
     * @return mixed
     */
    public function setCriteria($items);

    /**
     * @return mixed
     */
    public function applyCriteria();

    /**
     * @param Criteria $criteria
     * @return mixed
     */
    public function getByCriteria(Criteria $criteria);

    /**
     * @param bool $state
     * @return mixed
     */
    public function ignoreDefaultCriteria($state = true);
}