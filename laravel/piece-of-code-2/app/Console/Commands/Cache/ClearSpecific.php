<?php

namespace App\Console\Commands\Cache;

use DB;
use App;
use Cache;
use Exception;
use Illuminate\Console\Command;

class ClearSpecific extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'cache:clear-specific'
	. ' {--tag=*}'
	. ' {--key=*}'
	. ' {--key-var=* : Data replacements for key vars}'
	. ' {--group= : Supported are "ac", "queries" }'
	. ' {--table=* : Work only with `queries` group}';

	/**
	 * @var string
	 */
	protected $description = 'Delete specific cache keys';

	/**
	 * @return mixed
	 */
	public function handle() {

		try {

			if ($this->option('group')) {
				return $this->handleGroup($this->option('group'));
			}

			$tags = $this->option('tag');
			$keys = $this->option('key');

			if (!$tags) {

				foreach ($keys as $key) {
					Cache::forget($key);
				}
			} else if ($keys) {

				foreach ($keys as $key) {
					Cache::tags($tags)->forget($key);
				}
			} else {
				Cache::tags($tags)->flush();
			}
		} catch (Exception $e) {
			return 1;
		}

		return 0;
	}

	/**
	 * @param $group
	 */
	protected function handleGroup($group) {

		switch ($group) {
			case 'ac':
				Cache::tags(config('cms.cache.ac.tags', []))->flush();
				break;
			case 'queries':

				if (!$tables = $this->option('table')) {
					$tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
				}

				foreach (array_diff($tables, config('cms.cache.sql.ignored_tables', [])) as $table) {
					Cache::tags(sprintf("%s-%s", config('cms.cache.sql.tag_prefix'), $table))->flush();
				}

				break;
			default:
				if (App::runningInConsole()) {
					$this->error("Unsupported group $group");
				}
		}

		return 0;
	}
}
