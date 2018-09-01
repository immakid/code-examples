<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarExpense;
use App\Models\User;
use Auth;
use DB;

class CarExpensesController extends Controller {

    public function index(Car $car) {
        return $car->expenses;
    }

    /**
     * @param Car $car
     *
     * @return CarExpense
     * @throws \Exception
     * @throws \Throwable
     */
    public function postCreate(Car $car) {
        return DB::transaction(function() use($car) {

            $expense = new CarExpense();
            $data = $this->validateInput();
            $expense->fill($data);
            $expense->car_id = $car->id;
            $expense->user_id = can('setUser', CarExpense::class) && @$data['user_id'] ? $data['user_id'] : Auth::id();
            $expense->save();

            $car->calculateSalePrice();
            $car->save();

            return $expense;
        });
    }

    /**
     * @param Car $car
     * @param CarExpense $expense
     *
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public function postUpdate(Car $car, CarExpense $expense) {
        return DB::transaction(function() use($car, $expense) {
            $data = $this->validateInput(array_keys(\Request::input()));
            $expense->fill($data);
            $expense->save();

            $car->calculateSalePrice();
            $car->save();
        });
    }

    /**
     * @param Car $car
     * @param CarExpense $expense
     * @param User $user
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function postSetUser(Car $car, CarExpense $expense, User $user) {
        DB::transaction(function() use($car, $expense, $user) {
            $expense->user_id = $user->id;
            $expense->save();

            $car->calculateSalePrice();
            $car->save();
        });
    }

    /**
     * @param Car $car
     * @param CarExpense $expense
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function postDelete(Car $car, CarExpense $expense) {
        DB::transaction(function() use($car, $expense) {
            $expense->delete();

            $car->calculateSalePrice();
            $car->save();
        });
    }

    /**
     * @param array|null $fields
     *
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInput($fields = null) {
        $allRules = [
            'user_id' => ['nullable', 'integer', ['exists', 'users', 'id']],
            'name' => ['required', 'string', ['max', 255]],
            'amount' => ['required', 'integer', ['min', 0], ['max', CarExpense::UNSIGNED_INTEGER_MAX]],
        ];

        $rules = is_array($fields)
            ? array_filter($allRules, function($name) use($fields) {
                return in_array($name, $fields, true);
            }, ARRAY_FILTER_USE_KEY)
            : $allRules;

        $data = $this->validateOnly($rules, [], CarExpense::labels());

        return $data;
    }

}