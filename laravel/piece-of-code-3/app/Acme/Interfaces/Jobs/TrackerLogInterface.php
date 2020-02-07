<?php

namespace App\Acme\Interfaces\Jobs;

use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

interface TrackerLogInterface {

	/**
	 * @param string $key
	 * @param array $columns
	 * @param array $rows
	 * @param string|null $title
	 * @return TrackerLogInterface
	 */
	public function addSection(string $key, array $columns = [], array $rows = [], string $title = null): TrackerLogInterface;

	/**
	 * @param string $key
	 * @param array $rows
	 * @param bool $separate
	 * @return TrackerLogInterface
	 */
	public function addSectionData(string $key, array $rows, bool $separate = false): TrackerLogInterface;

	/**
	 * @param string $key
	 * @param string $alignment
	 * @param array $columns
	 * @return TrackerLogInterface
	 */
	public function setSectionTextAlignment(string $key, string $alignment, array $columns = []): TrackerLogInterface;

	/**
	 * @param array $payload
	 * @return string
	 */
	public function render(array $payload): string;

	/**
	 * @param string $value
	 * @param int $colspan
	 * @param int $rowspan
	 * @return TableCell
	 */
	public function createDataCell(string $value, int $colspan = 1, int $rowspan = 1): TableCell;

	/**
	 * @return TableSeparator
	 */
	public function createDataSeparator():TableSeparator;

	/**
	 * @return float
	 */
	public function getPeakMemoryUsage(): int;

	/**
	 * @return float
	 */
	public function getExecutionTime(): float;
}