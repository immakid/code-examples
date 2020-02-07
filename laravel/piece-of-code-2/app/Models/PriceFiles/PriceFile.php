<?php

namespace App\Models\PriceFiles;

use Artisan;
use Developer;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use InvalidArgumentException;
use App\Acme\Interfaces\Eloquent\Statusable;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Libraries\Traits\Eloquent\Statuses;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\PriceFiles\PriceFile
 *
 * @property int $id
 * @property int $store_id
 * @property int $enabled
 * @property int $interval
 * @property string|null $url
 * @property string $format
 * @property mixed $data
 * @property int $in_progress
 * @property string|null $parsed_at
 * @property-read false|string $hr_status
 * @property-read mixed $is_remote
 * @property-read string $local_file_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PriceFiles\PriceFileMap[] $maps
 * @property-read \App\Models\Stores\Store $store
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile remote()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile status($statuses)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereInProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereParsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereUrl($value)
 * @mixin \Eloquent
 * @property string $status
 * @property \Carbon\Carbon|null $api_touched_at
 * @property \Carbon\Carbon|null $data_touched_at
 * @property \Carbon\Carbon|null $columns_touched_at
 * @property-read bool $is_enabled
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PriceFiles\PriceFileLog[] $logs
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereApiTouchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereColumnsTouchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereDataTouchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PriceFiles\PriceFile whereStatus($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PriceFiles\PriceFileImage[] $pendingImages
 */
class PriceFile extends Model implements Serializable, Statusable {

	use Statuses,
		Serializer,
		RelationManager;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $with = ['store', 'maps'];

	/**
	 * @var array
	 */
	protected $dates = [
		'api_touched_at',
		'data_touched_at',
		'columns_touched_at'
	];

	/**
	 * @var array
	 */
	protected $fillable = [
		'url',
		'format',
		'interval',
		'data',
		'queue' // by mighty developer only
	];

	/**
	 * @var array
	 */
	protected $casts = [
		'interval' => 'integer'
	];

	/**
	 * @var array
	 */
	protected $requestRelations = [
		'store' => 'store_id'
	];

	/**
	 * @var array
	 */
	protected static $statuses = [
		'new' => 0,
		'missing_maps' => 1,
		'in_progress' => 2,
		'disabled_user' => 3,
		'disabled_api' => 4,
		'active' => 5,
		'active_error' => 6
	];

	public static function boot() {
		parent::boot();

		static::creating(function (PriceFile $model) {

			if (!$model->queue) {
				$model->queue = 'wg.price-files.medium';
			} else {

				if (!Developer::isPresent()) {
					return false;
				}
			}
		});

		static::updating(function (PriceFile $model) {

			if (in_array('queue', $model->getDirty()) && !Developer::isPresent()) {
				return false;
			}
		});
	}

	/**
	 * @param string $value
	 */
	public function setUrlAttribute($value) {

		if ($value !== null) {

			if (!Arr::get(parse_url($value), 'scheme')) {
				$value = sprintf("http://%s", $value);
			}

			$this->attributes['url'] = $value;
		} else {
			$this->attributes['url'] = null;
		}
	}

	/**
	 * @return bool
	 */
	public function getIsEnabledAttribute() {

		return in_array($this->status, array_values(Arr::except(static::$statuses, [
			'disabled_api',
			'disabled_user',
		])));
	}

	/***
	 * @return bool
	 */
	public function getIsRemoteAttribute() {
		return (bool)$this->url;
	}

	/**
	 * @return string
	 */
	public function getLocalFileNameAttribute() {

		$directory = rtrim(config('cms.paths.price_files.local'), '/');
		return sprintf("%s/%d.%s", $directory, $this->id, $this->format);
	}

	/**
	 * @param QueryBuilder $builder
	 * @return QueryBuilder
	 */
	public function scopeRemote(QueryBuilder $builder) {
		return $builder->where(get_table_column_name($builder->getModel(), 'url'), '!=', null);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function store() {
		return $this->belongsTo(Store::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function maps() {
		return $this->hasMany(PriceFileMap::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function pendingImages() {
		return $this->hasMany(PriceFileImage::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function logs() {
		return $this->hasMany(PriceFileLog::class);
	}

	/**
	 * @param string $message
	 * @param array $payload
	 * @param string $type
	 * @param string|null $job
	 */
	public function writeApiLog(string $message, array $payload = [], string $type = 'error', string $job = null): void {

		$job = $job ?: get_class_short_name(get_called_class());
		$this->writeLog($message, $payload, $job, $type, 'api');
	}

	/**
	 * @param string $message
	 * @param array $payload
	 * @param string $type
	 * @param string|null $job
	 */
	public function writeLocalLog(string $message, array $payload = [], string $type = 'error', string $job = null): void {

		$job = $job ?: get_class_short_name(get_called_class());
		$this->writeLog($message, $payload, $job, $type);
	}

	/**
	 * @param string $message
	 * @param array $payload
	 * @param string $source
	 * @param string|null $job
	 * @param string $type
	 */
	public function writeLog(string $message, array $payload = [], string $job = null, string $type = 'error', string $source = 'local'): void {

		$job = $job ?: get_class_short_name(get_called_class());

		$this->logs()->create([
			'type' => $type,
			'data' => $payload,
			'source' => $source,
			'message' => $message,
			'job' => $job
		]);

		Artisan::call('cache:clear-specific', [
			'--group' => 'queries',
			'--table' => [$this->logs()->getModel()->getTable()]
		]);
	}

	/**
	 * @return array
	 */
	public function prepareApiData(): array {

		$maps = $this->maps->toArray();
		$data = array_replace_recursive($this->data(), ['extra' => ['remote' => $this->isRemote]]);
		$url = $this->isRemote ? $this->url : route_region('api.price-file.show', [$this->id]);
		
		$images = array();
		
		if (!$this->pendingImages->isEmpty()) {

			$image_urls = Arr::pluck($this->pendingImages, 'url', 'id');
			
			$product_ids_count = 0;

			$output = array();
			
			foreach (Arr::pluck($this->pendingImages, 'product_id','id') as $media_id => $product_id) {
				$output[$product_id][] = $media_id;	
			}

			// print_logs_app("output - ".print_r($output,true));

			foreach ( $output as $product_id => $media_ids) {
				
				if ($product_ids_count >= config('cms.api.limit_importing_pending_images')) {
					continue;
				}
				foreach ($media_ids as $media_id) {
					$images[$product_id][$media_id] = $image_urls[$media_id];
				}
				$product_ids_count++;
			}
			// print_logs_app("Images - ".print_r($images,true));
		}

		return [
			'mappings' => [
				'schema' => [
					'separators' => array_filter(Arr::pluck($maps, 'data.separator', 'column.key')),
					'categories_separator' => array_filter(Arr::pluck($maps, 'data.categories_separator', 'column.key')),
					'identifiers' => array_filter(Arr::pluck($maps, 'data.identifier', 'column.key')),
					'columns' => Arr::pluck($maps, 'column.key', str_replace(['csv', 'xml'], ['index', 'label'], $this->format))
				]
			],
			'images' => $images,
			'file' => [
				'url' => $url,
				'data' => $data,
				'status' => $this->hrStatus,
				'format' => $this->format,
				'remote' => $this->isRemote,
			]
		];
	}

	/**
	 * @param string $key
	 * @param bool $save
	 * @return PriceFile|bool
	 */
	public function touchTs(string $key, $save = true) {

		foreach ($this->dates as $column) {
			if (strpos($column, sprintf("%s_", $key)) === 0) {

				$this->{$column} = $this->freshTimestamp();
				return $save ? ($this->save() ? $this : false) : $this;
			}
		}

		throw new InvalidArgumentException("Invalid timestamp $key");
	}

	/**
	 * @return bool
	 */
	public function enable() {

//		if (!$this->isEnabled) {
//			return (bool)$this->setStatus($this->determineEnabledStatus());
//		}

		if ($this->hrStatus !== 'in_progress') {
			return (bool)$this->setStatus($this->determineEnabledStatus());
		}

		return true;
	}

	/**
	 * @param bool $api
	 * @return bool
	 */
	public function disable($api = false) {

		if ($this->isEnabled && ($this->hrStatus !== 'in_progress' || $api)) {
			return (bool)$this->setStatus(sprintf("disabled_%s", $api ? 'api' : 'user'));
		}
	}

	/**
	 * @return string
	 */
	public function determineEnabledStatus() {

		$mappings = array_filter(Arr::pluck($this->maps->toArray(), 'column.id'));

		if ($this->maps->isEmpty()) { // no columns attached
			return 'new';
		} else if ((bool)array_diff(PriceFileColumn::getMandatory(), $mappings)) { // missing (mandatory) mappings
			return 'missing_maps';
		}
		
		$price_file_logs = PriceFileLog::where('price_file_id', $this->id)
            ->whereNotIn('type', ["debug","warning"])
            ->orderBy('created_at', 'DESC')
            ->take(10)
            ->get();

        if (!empty($price_file_logs)) {

	        foreach ($price_file_logs as $value) {

	        	switch ($value['type']) {
	        		case 'critical':
	        			print_logs_app("Type - ".$value['type']);
	        			return 'active_error';
	 				case 'info':
	        			return 'active';
	        		default:
	        			break;
	        	}
	        }
        }

        return 'active';
	}
}
