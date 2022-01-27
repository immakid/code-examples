<?php

/*
 * This file is part of the Trellis backend project.
 *
 * © Vinelab <dev@vinelab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Domains\DemographicsPro;
use JsonSerializable;

class AudienceMetric implements JsonSerializable
{

	private $title;
	private $sampleSize;
	public $values;

	public function __construct($title, $sampleSize, $values)
	{
		$this->title = $title;
		$this->sampleSize = $sampleSize;
		$this->values = $values;
	}

	public function jsonSerialize() {
        return ['title' => $this->title, 'sample_size' => $this->sampleSize, 'values' => $this->values];
    }

}
