<?php

namespace App\Acme\Extensions\Database;

use App;
use Log;
use Cache;
use Closure;
use PDOStatement;

trait QueryCasher {

	/**
	 * @param \App\Acme\Extensions\Database\PDOStatement $statement
	 * @param Closure|null $callback
	 * @return array|mixed
	 */
	protected function cacheProxy(PDOStatement $statement, Closure $callback = null) {

		$query = $statement->getCompiledQuery();
		$matches = get_tables_from_query($query);

		if (!$callback) {
			return [];
		} else if (strpos($query, '`') === false) {

			/**
			 * This may cause false query tables identification, so
			 * be on the safe side and execute the query.
			 */

			Log::alert("Query does not contain tilda (`)", ['sql' => $query]);
			return call_user_func($callback, $statement);
		} else if (!($tables = $matches ?: [])) {

			/**
			 * Somehow, table name could not be determined, so let's just
			 * log the event and execute the query.
			 */

			Log::alert("Unable to determine table name from query", ['sql' => $query]);
			return call_user_func($callback, $statement);
		} else if ($this->shouldNotBeCached($tables, $query)) { // ignoring
			return call_user_func($callback, $statement);
		}

		$tags = config('cms.cache.sql.tags', []);
		$tag_prefix = config('cms.cache.sql.tag_prefix');

		sort($tables);
		foreach ($tables as $table) {
			array_push($tags, sprintf("%s-%s", $tag_prefix, $table));
		}

		return Cache::tags($tags)->rememberForever('query-' . sha1($query), function () use ($tags, $query, $statement, $callback) {
			return call_user_func($callback, $statement);
		});
	}

	/**
	 * @param array|null $tables
	 * @param string $query
	 * @return bool
	 */
	protected function shouldNotBeCached(array $tables, $query) {

		if (
			App::runningInConsole() ||
			App::runningUnitTests() ||
			!config('cms.cache.sql.enabled') ||
			config('environment') === config('cms.states.pf-job.running')
		) {
			return true;
		} else if (strpos(strtolower($query), 'rand()') !== false) { // order by random should be random...
			return true;
		}

		foreach ($tables as $table) {
			if (in_array($table, config('cms.cache.sql.ignored_tables', []))) {
				return true;
			}
		}

		return false;
	}
}