<?php

namespace App\Models;

use Log;
use finfo;
use Image;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use App\Acme\Interfaces\Eloquent\Serializable;
use Symfony\Component\HttpFoundation\File\File;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

/**
 * App\Models\Media
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $label
 * @property string $hash
 * @property mixed $name
 * @property string $path
 * @property string $mime
 * @property mixed $data
 * @property \Carbon\Carbon $created_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $children
 * @property-read \App\Models\Media $parent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media labeled($label)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media name($name)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Media wherePath($value)
 * @mixin \Eloquent
 */
class Media extends Model implements Serializable {

	use Serializer;

	/**
	 *
	 * @var boolean
	 */
	public $timestamps = false;

	/**
	 *
	 * @var array
	 */
	protected $dates = ['created_at'];

	/**
	 *
	 * @var array
	 */
	protected $fillable = ['name', 'label', 'data'];

	/**
	 *
	 * @var \Symfony\Component\HttpFoundation\File\UploadedFile
	 */
	protected $file;

	/**
	 *
	 * @var string
	 */
	protected $baseDir;

	/**
	 * @var string
	 */
	protected $designator;

	/**
	 *
	 * @var array
	 */
	protected $thumbnails = [];

	/**
	 * @var array
	 */
	protected $thumbnail_rules = [];

	public static function boot() {
		parent::boot();

		// Upload if everything is ok
		self::creating(function (Media $model) {

			$model->name = urldecode($model->name);
			$model->created_at = $model->freshTimestamp();

			if ($model->file instanceof UploadedFile) {

				if ($model->file->isValid()) {

					/**
					 * Handle uploaded file
					 */

					$model->hash = sha1_file($model->file);
					return $model->upload();
				}

				return false;
			} else if ($model->file instanceof File && !$model->parent) {
				$model->hash = sha1_file($model->file);

				/**
				 * Move local file to correct
				 * directory (orphans only -> not thumbs)
				 */

				return $model->upload();
			}

			$model->hash = sha1_file($model->file);

			return true;
		});

		// Thumbnails
		self::created(function (Media $model) {

			if ($model->thumbnails && $model->isSupportingThumbnails()) {
				$model->generateThumbnails();
			}
		});

		// Delete file & it's children
		static::deleting(function (Media $model) {

			foreach ($model->children as $child) {
				$child->delete();
			}

			$dir = dirname($model->path);
			if (file_exists($model->path)) {
				@unlink($model->path);
			}

			if (is_dir($dir)) {

				/**
				 * Remove entire directory, if empty
				 */

				$finder = new Finder();
				if (!$finder->files()->in($dir)->count()) {
					@rmdir($dir);
				}
			}
		});
	}

	/**
	 * @param string $value
	 */
	public function setLabelAttribute($value) {

		if (is_string($value)) {
			$this->attributes['label'] = strtolower(str_replace(' ', '-', trim($value)));
		}
	}

	/**
	 * @param QueryBuilder $builder
	 * @param string $label
	 * @return QueryBuilder
	 */
	public function scopeLabeled(QueryBuilder $builder, $label) {
		return $builder->where(get_table_column_name($builder->getModel(), 'label'), '=', $label);
	}

	/**
	 *
	 * @param QueryBuilder $builder
	 * @param string $name
	 * @return QueryBuilder
	 */
	public function scopeName(QueryBuilder $builder, $name) {
		return $builder->where(get_table_column_name($builder->getModel(), 'name'), '=', $name);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function children() {
		return $this->hasMany(Media::class, 'parent_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function parent() {
		return $this->hasOne(Media::class, 'id', 'parent_id');
	}

	/**
	 * @param File|string $file
	 * @param string|null $label
	 * @return static
	 */
	public static function fromFile($file, $label = null) {

		if (is_string($file)) {
			$file = new File($file);
		} else if (!$file instanceof File) {
			throw new InvalidArgumentException("I file path or Symfony's File instance as first argument.");
		}

		$instance = new static([
			'label' => (string)$label,
			'name' => $file->getFilename(),
			'data' => [
				'size' => $file->getSize(),
				'ext' => $file->getExtension()
			]
		]);

		$instance->file = $file;
		$instance->mime = $file->getMimeType();
		$instance->path = $file->getPathname();

		$instance->setBaseDir(config('cms.paths.uploads'));

		if (!$instance->hasValidMimetype()) {
			// @TODO: Security exception
		} else if ($instance->isImage()) {

			list($width, $height) = getimagesize($instance->path);
			$instance->dataUpdate(['width' => $width, 'height' => $height]);
		}

		if (!isset($instance->data->requested)){

			$name = $instance->name;

			print_logs_app("Name of resized image :".$name);

			$image_resize = explode("-", $name);

			if ( isset($image_resize[0]) ) {

				$image_dimensions = explode("x", $image_resize[0]);

				if ( isset($image_dimensions[0]) && isset($image_dimensions[1]) ) {
					$instance->dataUpdate(['requested' => array_combine(['width', 'height'], [$image_dimensions[0],$image_dimensions[1]])]);
				} else {
					print_logs_app("image_dimensions[0]- doesn't exist");
				}
			}
			else {
				print_logs_app("image_resize[0]- doesn't exist");
			}
		}
		else {
			print_logs_app("data requested exists.");
		}

		return $instance;
	}


	/**
	 * @param UploadedFile $file
	 * @param string|null $label
	 * @return static
	 */
	public static function fromRequest(UploadedFile $file, $label = null) {

		$instance = new static([
			'label' => (string)$label,
			'name' => $file->getClientOriginalName(),
			'data' => [
				'size' => $file->getClientSize(),
				'ext' => $file->getClientOriginalExtension(),
			]
		]);

		$instance->file = $file;
		$instance->mime = $file->getClientMimeType();
		$instance->setBaseDir(config('cms.paths.uploads'));

		if (!$instance->hasValidMimetype()) {
			// @TODO: Security exception
		} else if ($instance->isImage()) {

			list($width, $height) = getimagesize($file->getPathname());
			$instance->dataUpdate(['width' => $width, 'height' => $height]);
		}

		return $instance;
	}

	/**
	 * @param string $url
	 * @param string|null $label
	 * @return Media
	 */
	public static function fromUrl($url, $label = null) {

		$dir = gen_tmp_dir();
		$name = basename($url);
		$data = file_get_contents($url);
		$parts = array_reverse(explode('.', $url));

		if (strpos($parts[0], '?') !== false) {

			$parts[0] = substr($parts[0], 0, strpos($parts[0], '?'));
			if (in_array($parts[0], config('cms.extensions.images', []))) {

				$url = implode('.', array_reverse($parts));
				$name = basename($url);
			} else {

				/**
				 * Responsible for handling URLs like:
				 * http://www.ekab.se/ItemInfo?itemId=33587219
				 */

				$fileInfo = new finfo(FILEINFO_MIME_TYPE);
				$random = gen_random_string(20, null, ['numbers']);
				$ext = Arr::first(array_reverse(explode('/', $fileInfo->buffer($data))));
				$name = $label ? sprintf("%s-%s.%s", $label, $random, $ext) : sprintf("%s.%s", $random, $ext);
			}
		}

		$file = sprintf("%s/%s", rtrim($dir, '/'), $name);
		file_put_contents($file, $data);

		$instance = self::fromFile($file, $label);

		return $instance;
	}

	/**
	 * @return string
	 */
	public function getUrl() {

		$path = str_replace('\\', '/', $this->path);
		$path_relative = ltrim(substr($path, strpos($path, '/', strlen(base_path()) + 1)), '/');

		// This affect to CDN through home page image load
//		if ($this->isImage()) {
//			return base_url(str_replace('uploads/', 'media/', $path_relative));
//		}

		return base_url($path_relative);
	}

	/**
	 * @return bool
	 */
	public function getChild() {

		$args = func_get_args();
		$width = $height = null;

		switch (count($args)) {
			case 1:
				if (is_array($args[0])) {
					list($width, $height) = $args[0];
				} else {
					$width = $args[0];
				}
				break;
			case 2:
				list($width, $height) = $args;
				break;
		}

		//print_logs_app("Childen size - ".sizeof($this->children));
		
		foreach ($this->children as $child) {
		
			if (!$child->isImage()) {
				//print_logs_app("Child is not image");
				continue;
			}

			if ($width && $height) {
				if ((int)$width === (int)$child->data('requested.width') && (int)$height === (int)$child->data('requested.height')) {
					//print_logs_app("Child width & height.");
					return $child;
				}
				continue;
			} else if ($width && (int)$width === (int)$child->data('requested.width')) {
				//print_logs_app("only width exists");
				return $child;
			} else if ($height && (int)$height === (int)$child->data('requested.height')) {
				//print_logs_app("Only height exists");
				return $child;
			}
		}

		return false;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\File\UploadedFile
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * @param string $dir
	 * @return $this
	 */
	public function setBaseDir($dir) {

		$this->baseDir = $dir;

		return $this;
	}

	/**
	 * @param string $designator
	 * @return $this
	 */
	public function setDesignator($designator) {

		$this->designator = $designator;

		return $this;
	}

	/**
	 * @param array $sizes
	 * @param array $rules
	 * @return $this
	 */
	public function withThumbnails(array $sizes, array $rules = []) {

		foreach ($sizes as $size) {

			if (count($size) !== 2) {
				throw new InvalidArgumentException("Thumbnails array(s) must have exactly 2 arguments, width and height.");
			}

			array_push($this->thumbnails, $size);
		}

		$this->thumbnail_rules = $rules;

		return $this;
	}

	/**
	 * @return bool
	 */
	protected function upload() {

		$designator = $this->designator ? $this->designator : strtotime(date('d.m.Y'));
		$dir = sprintf("%s/%s", $this->baseDir, $designator);

		if (file_exists(sprintf("%s/%s", $dir, $this->name))) {
			$this->name = sprintf("%s-%s", time(), $this->name); // make sure it's unique
		}

		if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
			Log::alert("Could not create directory.", ['dir' => $dir]); // permissions
		} else if (!$uploaded = $this->file->move($dir, $this->name)) {
			Log::alert("Image upload failed.", ['error' => $this->file->getError()]); // whooops
		} else {

			$this->path = $uploaded->getPathName();
			return true;
		}

		return false;
	}

	/**
	 * @return $this
	 */
	public function generateThumbnails() {

		foreach ($this->thumbnails as $index => $size) {

			$labels = $size;
			array_walk($labels, function (&$item) {
				$item = (is_null($item)) ? 'auto' : $item;
			});

			$name = sprintf("%s-%s", implode('x', $labels), $this->name);
			$path = sprintf("%s/%s", dirname($this->path), $name);

			$image = Image::make($this->path);
			if (count(array_filter($size)) !== count($size)) {
				$image->resize($size[0], $size[1], function ($image) {
					$image->aspectRatio();
					$image->upsize();
				});
			} else {

				switch (Arr::get($this->thumbnail_rules, $index)) {
					case 'exact':

						/**
						 * We don't want to crop photos, but rather
						 * to fit it into width/height respecting ratio
						 */

						$width = $size[0] > $size[1] ? $size[0] : null;
						if ($image->width() < $width) {
							$width = $image->width();
						}

						$height = $width ? null : $size[1];
						if ($image->height() < $height) {
							$height = $image->height();
						}

						$image = $image->resize($width, $height, function ($image) {
							$image->aspectRatio();
							$image->upsize();
						});

						if (!$width && $image->width() > $size[0]) { // result is wider then allowed
							$image->widen($size[0]);
						} else if (!$height && $image->height() > $size[1]) { // height to big
							$image->heighten($size[1]);
						}
						break;
					default:
						$image->fit($size[0], $size[1], function ($image) {
							$image->aspectRatio();
							$image->upsize();
						});
				}
			}

			$image->save($path);

			$instance = static::fromFile(new File($path), $this->label);
			$instance->dataUpdate(['requested' => array_combine(['width', 'height'], $size)]);

			$this->children()->save($instance);
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	protected function isImage() {
		return in_array($this->mime, config('cms.mimes.images'));
	}

	/**
	 * @return bool
	 */
	protected function isSupportingThumbnails() {
		return in_array($this->mime, config('cms.mimes.supporting_thumbs'));
	}

	/**
	 * @return bool
	 */
	protected function hasValidMimetype() {
		return in_array($this->mime, config('cms.mimes.allowed'));
	}
}
