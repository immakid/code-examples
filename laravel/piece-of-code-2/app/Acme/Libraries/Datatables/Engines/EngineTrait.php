<?php

namespace App\Acme\Libraries\Datatables\Engines;

trait EngineTrait {

	/**
	 * @param mixed $query
	 * @param string $columnName
	 * @param string $keyword
	 * @param string $boolean
	 */
	protected function compileQuerySearch($query, $column, $keyword, $boolean = 'or') {

		$column = $this->addTablePrefix($query, $column);
		$column = $this->castColumn($column);

		if ($this->config->isCaseInsensitive()) {

			$sql = 'LOWER(' . $column . ') LIKE ?';
			$query->{$boolean . 'WhereRaw'}($sql, [$this->prepareKeyword($keyword)]);
		} else {

			/**
			 * We can not use LOWER on BLOB columns, so this is genius solution....
			 */

			$sql = $column . ' LIKE ? OR ' . $column . ' LIKE ?';
			$query->{$boolean . 'WhereRaw'}($sql, [
				strtolower($this->prepareKeyword($keyword)),
				($this->prepareKeyword(ucfirst(strtolower($keyword)))),
			]);
		}
	}
}