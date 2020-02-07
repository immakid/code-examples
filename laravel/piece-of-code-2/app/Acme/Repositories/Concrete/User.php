<?php

namespace App\Acme\Repositories\Concrete;

use App\Acme\Repositories\Criteria\Where;
use App\Acme\Repositories\EloquentRepository;
use App\Acme\Repositories\Criteria\User\Current;
use App\Acme\Repositories\Interfaces\UserInterface;
use Laravel\Socialite\Contracts\User as SocialProvider;

class User extends EloquentRepository implements UserInterface {

	/**
	 * @return string
	 */
	protected function model() {
		return \App\Models\Users\User::class;
	}

	/**
	 * @return $this
	 */
	public function current() {
		return $this->setCriteria(new Current())->first();
	}

	/**
	 * @param $username
	 * @return mixed
	 */
	public function findByUsername($username) {
		return $this->setCriteria(new Where('username', $username))->first();
	}

	/**
	 * @param SocialProvider $provider
	 * @param string $type
	 * @return bool|\Illuminate\Database\Eloquent\Model
	 */
	public function createFromSocialAccount(SocialProvider $provider, $type) {

		$user = $this->make([
			'password' => null,
			'name' => $provider->getName(),
			'username' => $provider->getEmail()
		]);

		if ($user->setStatus('active')) {

			$account = $user->socialAccount()->create([
				'social_type' => $type,
				'social_id' => $provider->getId()
			]);

			if ($account) {
				return $user;
			}

			$user->forceDelete();
		}

		return false;
	}


    /**
     * @param $user
     * @param $groupKey
     * @return boolean
     */
    public function checkBackendUser($user) {

        $groups = $user->groups;

        foreach ($groups as $group){
            if($group->key != 'customer'){
                return true;
            }
        }
        return false;
    }
}