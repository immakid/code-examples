<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\People;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Flash;
use Request;

class CarsController extends Controller {

    public function index() {
        $cars = Car::query()
//            ->with(['status', 'people', 'docs_location', 'users', 'expenses', 'photos'])
            ->orderByDesc('updated_at')
            ->get();

        return view('cars.index', compact('cars'));
    }

    public function my() {
        $cars = $this->createSearchQuery()
            ->with(['users'])
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->paginate(50);

        if(Request::has('ajax')) {
            return view('cars._grid', compact('cars'));
        }

        return view('cars.my', compact('cars'));
    }

    public function all() {
        $cars = $this->createSearchQuery()
            ->with(['users'])
            ->orderByDesc('updated_at')
            ->paginate(50);

        if(Request::has('ajax')) {
            return view('cars._grid', compact('cars'));
        }

        return view('cars.all', compact('cars'));
    }

    protected function createSearchQuery() {
        $dbq = Car::query();

        $query = trim(Request::get('q'));
        if(!strlen($query))
            return $dbq;

        $words = array_filter(
            array_map(
                function($w) {
                    return trim(mb_strtolower($w, 'utf8'));
                },
                preg_split('/[\s\t\r\n]+/', $query)
            ),
            'strlen'
        );
        $words = array_unique($words);

        $marks = data('cars/marks-1');
        $marksFound = [];
        foreach($words as $word) {
            foreach($marks as $markId => $markLabel) {
                if(mb_stristr($markLabel, $word, false, 'utf8') !== false)
                    $marksFound[] = $markId;
            }
        }
        $marksFound = array_unique($marksFound);

        if(count($marksFound)) {
            $dbq->whereIn('mark', $marksFound);

            $models = [];
            foreach($marksFound as $markId)
                $models += data("cars/models-1/$markId");

            $modelsFound = [];
            foreach($words as $word) {
                foreach($models as $modelId => $modelLabel) {
                    if(mb_stristr($modelLabel, $word, false, 'utf8') !== false)
                        $modelsFound[] = $modelId;
                }
            }
            $modelsFound = array_unique($modelsFound);

            if(count($modelsFound)) {
                $dbq->whereIn('model', $modelsFound);
            }
        }

        $vins = array_filter($words, function($word) {
            return preg_match(Car::VIN_REGEX_IC, $word);
        });
        $vins = array_unique($vins);
        if(count($vins)) {
            $dbq->whereIn('vin', $vins);
        }

        $lots = array_filter($words, function($word) {
            return preg_match(Car::LOT_REGEX, $word);
        });
        $lots = array_unique($lots);
        if(count($lots)) {
            $dbq->whereIn('lot', $lots);
        }

        $lastnames = array_filter($words, function($word) {
            return preg_match(People::LASTNAME_REGEX_IC, $word);
        });
        $lastnames = array_unique($lastnames);
        if(count($lastnames)) {
            $people = People::query()->whereIn('lastname', $lastnames)->get();
            $dbq->whereIn('people_id', $people->pluck('id')->toArray());
        }

        return $dbq;
    }

    public function extra(Car $car) {
        return view('cars._grid.extra', compact('car'));
    }

    public function create() {
        $car = new Car();
        $car->fill(old());
        $car->user_id = Auth::id();

        return view('cars.create', compact('car'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function postCreate() {
        $car = new Car();

        $data = $this->validateInput();
        $car->fillNotNull($data);
        $car->user_id = Auth::id();

        DB::transaction(function() use($car, $data) {
            $images = $car->images();
            $oldImagesDir = $car->createImageInstance()->dirname;

            $car->calculateSalePrice();
            $car->save();

            $car->users()->attach($this->getUsers($car, $data));

            if(File::isDirectory($oldImagesDir)) {
                $newImagesDir = $car->createImageInstance()->dirname;
                if(!File::moveDirectory(public_path($oldImagesDir), public_path($car->createImageInstance()->dirname)))
                    throw new \Exception("Rename $oldImagesDir to $newImagesDir failed.");
            }
            $images->update(['car_id' => $car->id]);

            Flash::success("Авто #{$car->id} добавлено.");
        });

        return Request::has('continue') ? back() : redirect(action('CarsController@my'));
    }

    public function update(Car $car) {
        $car->fill(old());

        return view('cars.update', compact('car'));
    }

    /**
     * @param Car $car
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Validation\ValidationException|\Exception|\Throwable
     */
    public function postUpdate(Car $car) {
        $data = $this->validateInput();

        $car->fillNotNull($data);

        DB::transaction(function() use($car, $data) {
            $oldUsers = $car->users->pluck('id')->toArray();
            $newUsers = $this->getUsers($car, $data);
            $car->users()->detach(array_diff($oldUsers, $newUsers));
            $car->users()->attach(array_diff($newUsers, $oldUsers));

            $car->calculateSalePrice();

            if($car->isDirty()) {
                $car->save();
                Flash::success("Авто #{$car->id} изменено.");
            }
        });

        return Request::has('continue') ? back() : redirect(action('CarsController@my'));
    }

    /**
     * @param Car $car
     *
     * @throws \Exception
     */
    public function delete(Car $car) {
        $car->delete();
    }

    public function expenses(Car $car) {
        return view('cars._expenses.list', compact('car'));
    }

    /**
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInput() {
        $cat = Car::DEFAULT_CATEGORY;
        $mark = Request::input('mark');

        $data = $this->validateOnly([
            'li' => ['required', 'integer', in('cars/li')],
            'mark' => ['required', 'integer', in("cars/marks-$cat")],
            'model' => ['required', 'integer', in("cars/models-$cat/$mark")],
            'year' => ['required', 'integer', ['min', Car::YEAR_MIN], ['max', Carbon::now()->year]],
            'color' => ['required', 'integer', in('cars/colors')],
            'race' => ['required', 'integer', ['min', 0], ['max', Car::RACE_MAX]],
            'vin' => ['required', ['max', 17], ['regex', Car::VIN_REGEX]],
            'lot' => ['nullable', ['max', 10], ['regex', Car::LOT_REGEX]],
            'port' => ['nullable', ['max', 20], ['regex', Car::PORT_REGEX]],
            'form' => ['nullable', ['max', 5], ['regex', Car::FORM_REGEX]],
            'tracking' => ['nullable', ['max', 30]],
            'line' => ['nullable', ['max', 10]],

            'status_id' => ['nullable', 'integer', ['exists', 'car_statuses', 'id']],
            'people_id' => ['nullable', 'integer', ['exists', 'people', 'id']],
            'docs_location_id' => ['nullable', 'integer', ['exists', 'car_docs_locations', 'id']],

            'start_price' => ['required', 'integer', ['min', 1], ['max', Car::UNSIGNED_INTEGER_MAX]],
//            'sale_price' => [],
            'end_price' => ['required', 'integer', ['min', 0], ['max', Car::UNSIGNED_INTEGER_MAX]],
            'margin' => ['required', 'integer', ['min', 0], ['max', Car::UNSIGNED_INTEGER_MAX]],

            'users' => ['required', 'array'],
            'users.*' => ['required', 'integer', ['exists', 'users', 'id']],
        ], [], Car::labels());

        return $data;
    }

    protected function getUsers(Car $car, array $data) {
        return array_unique(
            array_merge(
                [$car->user_id],
                array_map('intval', array_filter($data['users'], 'is_string'))
            )
        );
    }

}