<?php

namespace App\Listeners\Subscribers;

use App\Events\Users\UserRegister;
use App\Jobs\SendEmail;
use App\Models\Users\User;
use App\Events\TokenExpired;
use App\Events\Users\Deleted;
use App\Events\Users\Created;
use Illuminate\Events\Dispatcher;
use App\Events\Users\CreatedByStore;
use App\Events\Users\AttachedToStore;
use App\Events\Users\DetachedFromStore;
use App\Events\Users\PasswordForgotten;
use App\Models\Users\UserGroup as Group;
use App\Acme\Interfaces\Events\UserEventInterface;
use App\Acme\Repositories\Interfaces\UserInterface;


class UserEventSubscriber {


    /**
     * @var \App\Models\Users\User|null
     */
    protected $user;

    public function __construct(UserInterface $user) {

        $this->user = $user;

    }

	/**
	 * @param UserEventInterface $event
	 */
	public function onCreated(UserEventInterface $event) {

		$user = $event->getUser();
		if ($user->groups->isEmpty()) {

			/**
			 * 1. Set default group
			 */
			$group = Group::default()->first();
			$user->groups()->attach($group);
		}

		if ($user->hrStatus === 'inactive') {

			/**
			 * 2. Send activation email
			 */
			$token = $this->generateToken($user);
			$link = route_region('app.account.activate', [$token->string]);

			dispatch(SendEmail::usingTranslation('user.account_activation', [
				'name' => $user->name,
				'link' => sprintf('<a href="%s">%s</a>', $link, $link)
			])->setRecipients([$user->username, $user->name]))->onConnection('wg.emails');
		}
	}

	/**
	 * @param UserEventInterface $event
	 */
	public function onDelete(UserEventInterface $event) {

		$user = $event->getUser();
		$user->groups()->detach();
		$user->stores()->detach();
	}

	/**
	 * @param CreatedByStore $event
	 */
	public function onCreatedByStore(CreatedByStore $event) {

		$user = $event->getUser();
		$token = $this->generateToken($user);

		$links = [
			'store' => get_store_url($event->store),
			'activation' => route_region('app.auth.change-pwd.form', [$token->string])
		];

		dispatch(SendEmail::usingTranslation('user.created_by_store', [
			'name' => $user->name,
			'group' => $event->group->name,
			'store_name' => $event->store->name,
			'store_url' => sprintf('<a href="%s">%s</a>', $links['store'], $links['store']),
			'link' => sprintf('<a href="%s">%s</a>', $links['activation'], $links['activation'])
		], 'backend')->setRecipients([$user->username, $user->name]))->onConnection('wg.emails');
	}

	/**
	 * @param DetachedFromStore $event
	 */
	public function onDetachedFromStore(DetachedFromStore $event) {

		/**
		 * If user is left out of stores, we will remove
		 * set his group to a default one.
		 */

		$user = $event->getUser();
		if (!$user->stores->count()) {

			$group = Group::default()->first();
			$user->groups()->sync([$group->id]);
		}
	}

	/**
	 * @param AttachedToStore $event
	 */
	public function onAttachedToStore(AttachedToStore $event) {

		$user = $event->getUser();
		if (!$user->groups->find($event->group)) {
			$user->groups()->attach($event->group);
		}
	}

	/**
	 * @param UserEventInterface $event
	 */
	public function sendResetPasswordEmail(UserEventInterface $event) {

		$user = $event->getUser();
		$token = $this->generateToken($user);
		$link = route_region('app.auth.change-pwd.form', [$token->string]);

        $backendUser = $this->user->checkBackendUser($user);


        if($backendUser){
            $link = route_region('admin.auth.change-pwd.form', [$token->string]);
        }

		dispatch(SendEmail::usingTranslation('user.password_forgotten', [
			'name' => $user->name,
			'link' => sprintf('<a href="%s">%s</a>', $link, $link),
            'username' => $user->username,
		])->setRecipients([$user->username, $user->name]))->onConnection('wg.emails');

	}


	/**
	 * @param User $user
	 * @return mixed
	 */
	protected function generateToken(User $user) {

		if ($user->token) {
			event(new TokenExpired($user->token));
		}

		return $user->token()->create([]);
	}

	/**
	 * @param Dispatcher $dispatcher
	 */
	public function subscribe(Dispatcher $dispatcher) {

		$dispatcher->listen(
			Created::class,
			'App\Listeners\Subscribers\UserEventSubscriber@onCreated'
		);

		$dispatcher->listen(
			Deleted::class,
			'App\Listeners\Subscribers\UserEventSubscriber@onDelete'
		);

		$dispatcher->listen(
			CreatedByStore::class,
			'App\Listeners\Subscribers\UserEventSubscriber@onCreatedByStore'
		);

		$dispatcher->listen(
			DetachedFromStore::class,
			'App\Listeners\Subscribers\UserEventSubscriber@onDetachedFromStore'
		);

		$dispatcher->listen(
			AttachedToStore::class,
			'App\Listeners\Subscribers\UserEventSubscriber@onAttachedToStore'
		);

		$dispatcher->listen(
			PasswordForgotten::class,
			'App\Listeners\Subscribers\UserEventSubscriber@sendResetPasswordEmail'
		);
	}

}