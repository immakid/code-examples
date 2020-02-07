<?php

namespace App\Console\Commands\Cache\Nornix;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Console\Command;
use App\Jobs\RefreshNornixCache;

class Nornix extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:run'
	.' {--command= : Specific command}'
	.' {--option=* : Format - KEY:value1[,value2][,valueN]}';

	/**
	 * @var string
	 */
	protected $description = 'To be developed further, just run them in a sequence for now.';

	/**
	 * @return \Illuminate\Foundation\Bus\PendingDispatch
	 */
	public function handle() {

		$command = $this->option('command');
		$options = (array)$this->option('option');

		if(!$options) {
			return RefreshNornixCache::dispatch($command)->onConnection('wg.cache');
		}

		$arguments = [];
		foreach($options as $option) {

			$parts = explode(':', $option);
			if(count($parts) !== 2) {
				throw new InvalidArgumentException("Option $option is not respecting the syntax, aborting...");
			}

			list($key, $values) = $parts;
			$arguments["--$key"] = array_merge(Arr::get($arguments, "--$key", []), explode(',', $values));
		}

		return RefreshNornixCache::dispatch($command, $arguments)->onConnection('wg.cache');
	}
}