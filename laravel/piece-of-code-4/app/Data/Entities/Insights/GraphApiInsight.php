<?php

namespace App\Data\Entities\Insights;

use App\Traits\JsonSerializableTrait;
use Createvo\Support\Interfaces\JsonSerializableInterface;
use Createvo\Support\Traits\MagicGetterTrait;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;


/**
 * Class GraphApiInsight
 *
 * @property-read string $name
 * @property-read mixed $value
 * @property-read string $period
 * @property-read string $title
 * @property-read string $description
 * @property-read string $id
 *
 * @author Illia Balia <illia@vinelab.com>
 * @author Vlad Silchenko <vlad@vinelab.com>
 */
class GraphApiInsight implements JsonSerializableInterface
{
    use JsonSerializableTrait;
    use MagicGetterTrait;

    private string $name;
    private $value;
    private string $period;
    private string $title;
    private string $description;
    private string $id;

    public function __construct(
        string $name,
        $value,
        string $period,
        string $title,
        string $description,
        string $id
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->period = $period;
        $this->title = $title;
        $this->description = $description;
        $this->id = $id;
    }

    /**
     * @param  string  $name
     * @param  array  $insights
     * @return static
     * @throws Exception
     */
    public static function makeFromInsights(string $name, array $insights): self
    {
        $data = Arr::collapse(Arr::where($insights, function (array $insight) use ($name) {
            return $insight['name'] === $name;
        }));

        if (empty($data)) {
            throw new Exception("Requested insight '{$name}' is not present in insights array");
        }

        $name = Arr::get($data, 'name');

        // Ease data manipulation for audience insights
        if (Str::startsWith($name, 'audience')) {
            $value = Arr::get($data, 'values.0.value');
        } else {
            $value = Arr::get($data, 'value');
        }

        return new self(
            $name,
            $value,
            Arr::get($data, 'period'),
            Arr::get($data, 'title'),
            Arr::get($data, 'description'),
            Arr::get($data, 'id')
        );
    }
}
