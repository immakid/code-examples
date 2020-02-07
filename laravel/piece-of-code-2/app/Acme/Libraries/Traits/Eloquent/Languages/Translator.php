<?php

namespace App\Acme\Libraries\Traits\Eloquent\Languages;

use App\Acme\Libraries\Traits\Eloquent\Serializer;
use Closure;
use RuntimeException;
use App\Models\Language;
use Illuminate\Support\Arr;
use App\Acme\Interfaces\Eloquent\Sluggable;
use App\Acme\Interfaces\MultilingualRequest;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;

/**
 * Used by all models which makes use of Translatable models.
 *
 * Trait Translator
 * @package App\Acme\Libraries\Traits\Eloquent
 * @mixin \Eloquent
 */
trait Translator {

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var array
	 */
	protected $translatedAttributes = [];

	/**
	 * Named constructor.
	 *
	 * @param MultilingualRequest $request
	 * @return mixed
	 */
	public static function createFromMultilingualRequest(MultilingualRequest $request, array $relations = null, Closure $callback = null) {

		$instance = new static();
		if ($instance->getFillable()) {
			foreach ($instance->getFillable() as $attribute) {

				$value = $request->input($attribute, false);

				if ($value !== false) {
					$instance->setAttribute($attribute, $request->input($attribute));
				}
			}
		}

		if (array_search(RelationManager::class, class_uses($instance))) {
			$instance->setBooleanRelationsFromRequest($request)->saveRelations(array_merge($request->all(), (array)$relations));
		}

		if ($instance->save()) {

			$key = $request->getTranslationsInputKey();
			foreach ($request->input($key, []) as $language_id => $attributes) {

				$instance->setLanguage(Language::find($language_id));
				$instance->setTranslatedAttributes($attributes);
				$translation = $instance->saveTranslation();

				if ($translation instanceof Translation && $callback) {
					call_user_func_array($callback, [$translation, $attributes]);
				}
			}

			return $instance;
		}

		return false;
	}

	/**
	 * @param MultilingualRequest $request
	 * @return mixed
	 */
	public function updateFromMultilingualRequest(MultilingualRequest $request, array $relations = null, Closure $callback = null, $saveRelations = true) {

		if (array_search(RelationManager::class, class_uses($this)) && $saveRelations) {
			$this->setBooleanRelationsFromRequest($request)
				->saveRelations(array_merge($request->all(), (array)$relations));
		}

		$fillable = $this->getFillable() ? $request->all() : [];
		if (array_search(Serializer::class, class_uses($this))) {

			$this->dataUpdate(Arr::get($fillable, 'data', []));
			$fillable = Arr::except($fillable, 'data');
		}

		if ($this->update($fillable)) {

			$key = $request->getTranslationsInputKey();
			foreach ($request->input($key, []) as $language_id => $attributes) {

				$language = Language::find($language_id);
				if (!$instance = $this->translations()->forLanguage($language)->first()) {

					$this->setLanguage($language);
					$this->setTranslatedAttributes($attributes);
					$translation = $this->saveTranslation();

					if ($translation instanceof Translation && $callback) {
						call_user_func_array($callback, [$translation, $attributes]);
					}

					continue;
				}

				$this->updateTranslation($instance, $attributes, $callback);
			}

			return $this;
		}

		return false;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function translations() {
		return $this->hasMany($this->getTranslatorClass(), 'parent_id');
	}

	/**
	 * @param string $column
	 * @param Language|null $language
	 * @return bool|mixed
	 */
	public function translate($column, Language $language = null) {

		if (!$language) {
			if (!$language = app('defaults')->language) {

				$translations = $this->translations->toArray();
				if (count($translations) === 1) {

					$translation = current($translations);
					return Arr::get($translation, $column);
				}

				throw new RuntimeException("Can not determine language.");
			}
		}

		if (!$translation = Arr::get($this->translations->toArray(), $language->id)) {
			return false;
		}

		return Arr::get($translation, $column);
	}

	/**
	 * @param $key
	 * @param Language|null $language
	 * @param mixed $default
	 * @return mixed
	 */
	public function translateData($key, Language $language = null, $default = null) {

		if (!$language) {
			if (!$language = app('defaults')->language) {
				throw new RuntimeException("Can not determine language.");
			}
		}

		if (!$item = $this->translations->filter(function ($translation) use ($language) {
			return $translation->language->id === $language->id;
		})->first()) {
			return $default;
		} else if (!$item instanceof Serializable) {
			return $default;
		}

		return $item->data($key, $default);
	}

	/**
	 * @param Language|null $language
	 * @param array|null $attributes
	 * @return bool|false|\Illuminate\Database\Eloquent\Model
	 */
	public function saveTranslation(Language $language = null, array $attributes = null) {

		$class = $this->getTranslatorClass();
		if (!$attributes && !($attributes = $this->getTranslatedAttributes())) {
			return true;
		}

		$instance = new $class($attributes);
		if ($instance instanceof Sluggable) {
			$instance->setSlugString(Arr::get($attributes, $instance->getRequestSlugInputName()));
		}

		$instance->language()->associate($language ? $language : $this->getLanguage());
		return $this->translations()->save($instance);
	}

	/**
	 * @param Translation $instance
	 * @param array $attributes
	 * @param Closure|null $callback
	 */
	public function updateTranslation(Translation $instance, array $attributes, Closure $callback = null) {

		if ($instance instanceof Sluggable) {
			$instance->setSlugString(Arr::get($attributes, $instance->getRequestSlugInputName()));
		}

		$instance->update($attributes);

		if ($callback) {
			call_user_func_array($callback, [$instance, $attributes]);
		}
	}

	/**
	 * @return Language
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Model responsible for holding translations
	 * (translatable columns)
	 *
	 * @return string
	 */
	public function getTranslatorClass() {
		return $this->translatorClass;
	}

	/**
	 * Child class's columns which will be translatable.
	 *
	 * @return array
	 */
	public function getTranslatorColumns() {
		return (array)$this->translatorColumns;
	}

	/**
	 * @return array
	 */
	public function getTranslatedAttributes() {
		return array_filter((array)$this->translatedAttributes);
	}

	/**
	 * Language for which translation will be saved.
	 *
	 * @param Language $language
	 * @return $this
	 */
	public function setLanguage(Language $language) {

		$this->language = $language;

		return $this;
	}

	/**
	 * Save translatable attributes (columns) for later use.
	 *
	 * @param array $attributes
	 * @return $this
	 */
	public function setTranslatedAttributes(array $attributes) {

		$this->translatedAttributes = $attributes;

		return $this;
	}
}