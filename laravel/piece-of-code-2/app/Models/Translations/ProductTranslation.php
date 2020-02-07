<?php

namespace App\Models\Translations;

use Illuminate\Support\Arr;
use App\Models\Products\Product;
use App\Acme\Interfaces\Eloquent\Sluggable;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Libraries\Traits\Eloquent\Slugger;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\ProductTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property string $name
 * @property string $excerpt
 * @property string $details
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Products\Product $parent
 * @property-read \App\Models\Slug $slug
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductTranslation whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductTranslation whereExcerpt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\ProductTranslation whereParentId($value)
 * @mixin \Eloquent
 */
class ProductTranslation extends Model implements Translation, Sluggable {

	use Slugger,
		Polyglot;

	/**
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * @var array
	 */
	protected $with = ['slug'];

	/**
	 * @var array
	 */
	protected $fillable = ['name', 'excerpt', 'details'];

	/**
	 * @var string
	 */
	protected static $parentClass = Product::class;

	/**
	 * @return string
	 */
	public function getSlugColumn() {
		return 'name';
	}

	/**
	 * @return string
	 */
	public function getNameAttribute() {
		return $this->parseUtf8Attribute('name');
	}

	/**
	 * @return string
	 */
	public function getExcerptAttribute() {
		return $this->parseUtf8Attribute('excerpt');
	}

	/**
	 * @return string
	 */
	public function getDetailsAttribute() {
		return $this->parseUtf8Attribute('details');
	}

	/**
	 * @param $value
	 */
	public function setNameAttribute($value) {
		$this->parseUtf8Attribute('name', $value);
	}

	/**
	 * @param $value
	 */
	public function setExcerptAttribute($value) {
		$this->parseUtf8Attribute('excerpt', $value);
	}

	/**
	 * @param $value
	 */
	public function setDetailsAttribute($value) {
		$this->parseUtf8Attribute('details', $value);
	}

	/**
	 * @param string|null $name
	 * @return array
	 */
	public function getKeywords($name = null, $strToLower = true) {

		$find = ['/', '.', '+'];
		$replace = [' ', ' ', ' '];

		$string =  mb_strtolower($name ? $name : $this->name);
		if(!$strToLower){
            $string = $name ? $name : $this->name;
        }

		$keys = explode(' ', str_replace($find, $replace, $string));

		$this->parseKeywords($keys);
		$this->filterKeywords($keys);

		return $keys;
	}

	/**
	 * @param $keys
	 */
	protected function parseKeywords(&$keys) {

		$items = [
			'trim' => ['(', ')', '.', ',', ':', '"', "'", '+', '!', '?', '/', '#', '-']
		];

		array_walk($keys, function (&$value) use ($items) {
			$value = trim(trim($value), implode('', Arr::get($items, 'trim', [])));
			$value = str_replace([','], [''], $value);
		});
	}

	/**
	 * @param $keys
	 */
	protected function filterKeywords(&$keys) {

		$keys = array_filter($keys, function ($value) {

			if (is_numeric($value)) {
				return false;
			} else if (count(array_filter(explode('x', $value), 'is_numeric')) === count(explode('x', $value))) {
				return false;
			} else if (mb_strlen(mb_convert_encoding($value, 'UTF-8', 'UTF-8')) < 3) {
				return false;
			}
//			} else if (strlen(mb_convert_encoding($value, 'UTF-8', 'UTF-8')) < 4) {
//				return false;
//			}

			return true;
		});
	}
}
