<?php

namespace App\Models\Data;

class PriceFile extends Model {

	/**
	 * @var string|null
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $format;

	/**
	 * @var array
	 */
	protected $attributes = [
		'mappings' => [
			'schema' => [
				'columns' => [],
				'separators' => [],
				'identifiers' => []
			],
		],
		'images' => [],
		'parser' => [
			'separators' => [
				'row' => null,
				'column' => null,
			],
			'identifiers' => [
				'item' => null
			],
			'extra' => [
				'remote' => null,
				'missing_columns' => null
			]
		]
	];

	public function __construct($id, $format, $url = null, array $attributes = []) {
		parent::__construct($id, $attributes);

		$this->url = $url;
		$this->format = $format;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * @return bool
	 */
	public function isRemote() {
		return (bool)$this->getAttribute('parser.extra.remote');
	}

	/**
	 * @return bool
	 */
	public function hasMappings() {
		return (bool)$this->getAttribute('mappings.schema.columns');
	}

	/**
	 * @return bool
	 */
	public function hasColumnHeaders() {
		return !(bool)$this->getAttribute('parser.extra.missing_columns');
	}

	/**
	 * @return array
	 */
	public function getPendingImages(): array {
		return $this->getAttribute('images', []);
	}

	/**
	 * @return bool|string
	 */
	public function getLogPath() {
		return $this->getPath('logs', 'file', false);
	}

	/**
	 * @param string $section
	 * @return mixed|string
	 */
	protected function getFilePath($section) {

		switch ($section) {
			case 'raw':
				return sprintf("%d.%s", $this->id, $this->format);
			case 'queue':
			case 'images':
				return sprintf("%d.zip", $this->id);
			default:
				return sprintf("%d.php", $this->id);
		}

	}

	/**
	 * @return string
	 */
	protected static function getConfigKey() {
		return 'price-files';
	}
}