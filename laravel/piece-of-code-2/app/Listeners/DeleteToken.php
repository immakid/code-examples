<?php

namespace App\Listeners;

use App\Events\TokenExpired;

class DeleteToken {

	/**
	 * @param TokenExpired $event
	 * @throws \Exception
	 */
    public function handle(TokenExpired $event) {
        $event->token->delete();
    }
}
