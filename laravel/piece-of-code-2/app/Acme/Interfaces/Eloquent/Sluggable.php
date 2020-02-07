<?php

namespace App\Acme\Interfaces\Eloquent;

/**
 * Interface Sluggable
 * @package App\Acme\Interfaces\Eloquent
 * @mixin \Eloquent
 */
interface Sluggable {

	/**
	 * @return mixed
	 */
	public function slug();

	/**
	 * @return string
	 */
	public function getSlugColumn();

	/**
	 * @return string
	 */
	public function getSlugString();

	/**
	 * @param string $string
	 * @return mixed
	 */
	public function setSlugString($string);

	/**
	 * @return string
	 */
	public function getRequestSlugInputName();
}