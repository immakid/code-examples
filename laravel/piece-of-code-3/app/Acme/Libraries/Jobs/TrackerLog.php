<?php

namespace App\Acme\Libraries\Jobs;

use Illuminate\Support\Arr;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableStyle;
use App\Acme\Interfaces\Jobs\TrackerLogInterface;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Output\StreamOutput as SymfonyOutput;

class TrackerLog implements TrackerLogInterface {

	/**
	 * @var mixed
	 */
	private $time;

	/**
	 * @var int
	 */
	private $memory;

	/**
	 * @var array
	 */
	private $sections = [];

	/**
	 * @var array
	 */
	private $sectionColumns = [
		'padding' => [],
	];

	public function __construct() {

		$this->time = microtime(true);
		$this->memory = $this->getPeakMemoryUsage();
	}

	/**
	 * @param string $key
	 * @param array $columns
	 * @param array $rows
	 * @param string|null $title
	 * @return TrackerLogInterface
	 */
	public function addSection(string $key, array $columns = [], array $rows = [], string $title = null): TrackerLogInterface {

		$this->sections[$key] = [
			'rows' => [],
			'columns' => $columns,
			'title' => strtoupper($title ?: $key)
		];

		$this->sectionColumns['padding'][$key] = array_fill_keys(array_keys($columns), 'STR_PAD_RIGHT');

		return $rows ? $this->addSectionData($key, $rows) : $this;
	}

	/**
	 * @param string $key
	 * @param array $rows
	 * @param bool $separate
	 * @return TrackerLogInterface
	 */
	public function addSectionData(string $key, array $rows, bool $separate = false): TrackerLogInterface {

		foreach ($rows as $index => $row) {

			array_push($this->sections[$key]['rows'], $row);

			if ($separate && $index + 1 < count($rows)) {
				array_push($this->sections[$key]['rows'], new TableSeparator());
			}
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @param string $alignment
	 * @param array $columns
	 * @return TrackerLogInterface
	 */
	public function setSectionTextAlignment(string $key, string $alignment, array $columns = []): TrackerLogInterface {

		$find = ['left', 'right', 'center'];
		$replace = ['STR_PAD_RIGHT', 'STR_PAD_LEFT', 'STR_PAD_BOTH'];

		foreach (array_keys($this->sectionColumns['padding'][$key]) as $index) {
			$this->sectionColumns['padding'][$key][$index] = str_replace($find, $replace, Arr::get($columns, $index, $alignment));
		}

		return $this;
	}

	/**
	 * @param array $payload
	 * @return string
	 */
	public function render(array $payload): string {

		$rows = [];
		foreach ($this->sections as $key => $section) {

			$columns = $section['columns'];
			$table = $this->generateTable($section['rows'], $columns, function (SymfonyTable &$table) use ($key, $columns) {

				$table->setStyle((clone $this->getDefaultStyle()));
				foreach ($this->sectionColumns['padding'][$key] as $index => $padding) {
					$table->setColumnStyle($index, (clone $this->getDefaultStyle())->setPadType(constant($padding)));
				}
			});

			array_push($rows, [sprintf(" %s", $section['title'])], [$table]);
		}

		$results = $this->generateTable($rows, [], function (SymfonyTable &$table) {
			$table->setStyle((clone $this->getDefaultStyle())->setVerticalBorderChar('|'));
		});

		return $this->generateTable(array_merge($payload, [
			[sprintf("Execution time: %.2f seconds", $this->getExecutionTime())],
			[sprintf("Started at: %s (RAM: %s)", date('d.m.Y H:i:s', $this->time), convert($this->memory))],
			[sprintf("Completed at: %s (RAM: %s)", date('d.m.Y H:i:s', microtime(true)), convert($this->getPeakMemoryUsage()))],
			[$results]
		]), [], function (SymfonyTable &$table) {
			$table->setStyle((clone $this->getDefaultStyle())->setVerticalBorderChar('|'));
		});
	}

	/**
	 * @param string $value
	 * @param int $colspan
	 * @param int $rowspan
	 * @return TableCell
	 */
	public function createDataCell(string $value, int $colspan = 1, int $rowspan = 1): TableCell {

		return new TableCell($value, [
			'rowspan' => $rowspan,
			'colspan' => $colspan
		]);
	}

	/**
	 * @return TableSeparator
	 */
	public function createDataSeparator(): TableSeparator {
		return new TableSeparator();
	}

	/**
	 * @return int
	 */
	public function getPeakMemoryUsage(): int {
		return memory_get_peak_usage(true);
	}

	/**
	 * @return float
	 */
	public function getExecutionTime(): float {
		return microtime(true) - $this->time;
	}

	/**
	 * @param array $rows
	 * @param array $columns
	 * @param callable|null $callback
	 * @return string
	 */
	private function generateTable(array $rows, array $columns = [], callable $callback = null): string {

		$stream = fopen('php://memory', 'r+');
		$instance = new SymfonyTable(new SymfonyOutput($stream));

		if ($callback) {
			$callback($instance);
		}

		$instance
			->setRows($rows)
			->setHeaders($columns)
			->render();

		rewind($stream);
		return (($data = stream_get_contents($stream)) && fclose($stream)) ? $data : false;
	}

	/**
	 * @return TableStyle
	 */
	private function getDefaultStyle(): TableStyle {

		return (new TableStyle())
			->setHorizontalBorderChar('-')
			->setVerticalBorderChar(' ')
			->setCrossingChar(' ');
	}
}