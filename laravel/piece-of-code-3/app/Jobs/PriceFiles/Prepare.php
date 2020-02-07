<?php

namespace App\Jobs\PriceFiles;

use Exception;
use Illuminate\Support\Arr;
use App\Jobs\PriceFiles\Core\Job;
use App\Acme\Exceptions\FileWriteException;
use App\Acme\Interfaces\Jobs\TrackerLogInterface;
use App\Acme\Exceptions\PriceFiles\EmptyFileException;

class Prepare extends Job {

	public function handle() {

		$this->trackExecutionOf(function (TrackerLogInterface $log) {

			if (!$items = $this->file->read('rows')) {
				throw new EmptyFileException($this->file);
			}

			$rows = [];
			$identifiers = $this->file->getAttribute('mappings.schema.identifiers', []);

			foreach ($items as $index => $item) {

				try {
					$this->handleRow($items[$index], $identifiers);
				} catch (Exception $e) {

					unset($items[$index]);
					array_push($rows, [Arr::get($item, 'id'), $index + 1, $e->getMessage()]);
				}
			}

			if ($rows) {
				$log->addSection('preparing', ['ID', 'Line', 'Message'], $rows, 'Detected issues');
			}

			if (!$this->file->write('prepared', $items)) {
				throw new FileWriteException($this->file->getPath('prepared', 'file', false));
			}
		});

	}

	/**
	 * @param array $items
	 * @param array $identifiers
	 * @throws Exception
	 */
	protected function handleRow(array &$items, array $identifiers): void {

		$this->handleInStockField($items, $identifiers);
		$this->handleEnabledField($items, $identifiers);

		$this->handleVat($items);
		$this->handleImage($items);
		$this->handlePriceFields($items, $identifiers);

		if (!strlen($items['id'])) {
			throw new Exception("Missing internal ID.");
		}

		$items['enabled'] = $items['stock']['available'];
	}

	/**
	 * @param array $items
	 * @param array $identifiers
	 * @throws Exception
	 */
	protected function handlePriceFields(array &$items, array $identifiers): void {

		foreach (['price', 'price_shipping', 'price_discounted'] as $key) {

			if (($items['prices'][$key] = Arr::get($items, $key, null)) === null) {
				continue;
			}

			unset($items[$key]);
		}

		array_walk($items['prices'], function (&$value) {
			$value = (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
		});

		if (!array_filter($items['prices']) || !$items['prices']['price']) { // Base price is mandatory
			throw new Exception("Unable to determine price.");
		}
	}

	/**
	 * @param array $items
	 */
	protected function handleVat(array &$items) {

		if (Arr::get($items, 'vat')) {
			$items['vat'] = (int)filter_var($items['vat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
		}
	}

	/**
	 * @param array $items
	 */
	protected function handleImage(array &$items) {

		if (Arr::get($items, 'image')) {

			foreach (['|', ','] as $separator) {
				if (strpos($items['image'], $separator) !== false) {
					$items['image'] = substr($items['image'], 0, strpos($items['image'], $separator));
					break;
				}
			}

			$replace = ['%20', 'https://', 'https://'];
			$find = [' ', 'http://https://', 'https://http://'];

			$items['image'] = str_replace($find, $replace, $items['image']);
		}
	}

	/**
	 * @param array $items
	 * @param array $identifiers
	 */
	protected function handleEnabledField(array &$items, array $identifiers): void {

		$value = Arr::get($items, 'enabled', false);
		if ($value === false) {
			$items['enabled'] = true; // @NOTE: By default -> set products enabled if no info at all
		} else if (!$identifier = Arr::get($identifiers, 'enabled')) {
			$items['enabled'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		} else {
			$items['enabled'] = ($identifier === $value);
		}
	}

	/**
	 * @param array $items
	 * @param array $identifiers
	 */
	protected function handleInStockField(array &$items, array $identifiers): void {

		list($available, $count) = self::getStockData(
			Arr::get($items, 'in_stock', false),
			Arr::get($identifiers, 'in_stock')
		);

		$items['stock'] = [
			'count' => $count,
			'available' => $available,
		];

		unset($items['in_stock']);
	}

	/**
	 * @param string|false $value
	 * @param string|null $identifier
	 * @return array
	 */
	protected static function getStockData($value, string $identifier = null): array {

		if ($value === false) { // @NOTE: By default -> set products as in stock if no info at all
			return [true, null];
		} else if ($identifier) {
			return ($value === $identifier) ? [true, null] : [false, null];
		}

		return (int)$value ? [true, (int)$value] : [false, (int)$value];
	}
}