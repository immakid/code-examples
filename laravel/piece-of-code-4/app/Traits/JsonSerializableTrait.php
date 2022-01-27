<?php

namespace App\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * Trait JsonSerializableTrait
 *
 * @author Illia Balia <illia@vinelab.com>
 */
trait JsonSerializableTrait
{
    /**
     * Specify property names that should be excluded from array representation
     * if they are empty.
     *
     * class Foo
     * {
     *     protected $shouldBeOmittedIfEmpty;
     *     protected $arrayOmitEmpty = ['shouldBeOmittedIfEmpty'];
     * }
     *
     * @var string[] $arrayOmitEmpty
     */

    /**
     * Specify property names that should have different names in array representation.
     *
     * class Foo
     * {
     *     protected $shouldBeRenamed;
     *     protected $arrayRenames = ['shouldBeRenamed' => 'new_name'];
     * }
     *
     * @var string[] $arrayRenames
     */

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $data = [];

        foreach ($this as $property => $value) {

            // Skip 'technical' properties
            if (in_array($property, ['arrayOmitEmpty', 'arrayRenames'])) {
                continue;
            }

            // Should be omitted if empty?
            if (
                property_exists($this, 'arrayOmitEmpty')
                && in_array($property, $this->arrayOmitEmpty)
                && empty($this->{$property})
            ) {
                continue;
            }

            // Should use a different name?
            if (
                property_exists($this, 'arrayRenames')
                && isset($this->arrayRenames[$property])
            ) {
                $name = $this->arrayRenames[$property];
            } else {
                $name = Str::snake($property);
            }

            // Is property an instance of Arrayable?
            $property = $this->{$property};
            if ($property instanceof Arrayable) {
                $property = $property->toArray();
            }

            $data[$name] = $property;
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
