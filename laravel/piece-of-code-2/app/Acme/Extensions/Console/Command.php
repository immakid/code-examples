<?php

namespace App\Acme\Extensions\Console;

use App;
use Log;
use Closure;
use Exception;
use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Command extends IlluminateCommand {

	/**
	 * @param Closure $callback
	 * @return int
	 */
	public function handleProxy(Closure $callback) {

		try {
			return $callback();
		} catch (ModelNotFoundException $e) {
			return $this->handleProxyError($e, get_called_class());
		} catch (Exception $e) {
			return $this->handleProxyError($e, get_called_class());
		}
	}

	/**
	 * @param Exception $e
	 * @param string $class
	 * @return int
	 */
	private function handleProxyError(Exception $e, $class) {

		if (App::runningInConsole() || App::runningUnitTests()) {

			$file = $e->getFile();
			$line = $e->getLine();
			$message = $e->getMessage();

			$this->error(sprintf("%s (%s:%s)", $message, $file, $line));

			Log::error("Command error", [
				'file' => $file,
				'line' => $line,
				'command' => $class,
				'message' => $message,
			]);
		}

		return 1;
	}
}
