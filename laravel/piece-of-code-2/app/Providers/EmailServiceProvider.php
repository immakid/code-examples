<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Acme\Libraries\Email\EmailProvider;
use App\Acme\Interfaces\Emails\EmailProviderInterface;

class EmailServiceProvider extends ServiceProvider {

	/**
	 * @return void
	 */
	public function boot() {
		//
	}

	/**
	 * @return void
	 */
	public function register() {

		$this->app->alias(EmailProviderInterface::class, 'email');
		$this->app->bind(EmailProviderInterface::class, EmailProvider::class);
	}
}
