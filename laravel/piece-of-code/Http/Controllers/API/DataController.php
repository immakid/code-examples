<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class DataController extends Controller {

    public function marks(int $category) {
        $marks = [];
        foreach(data("cars/marks-$category") as $k => $v) {
            $marks[] = [
                'value' => $k,
                'label' => $v,
            ];
        }
        return $marks;
    }

    public function models(int $category, int $mark) {
        $models = [];
        foreach(data("cars/models-$category/$mark") as $k => $v) {
            $models[] = [
                'value' => $k,
                'label' => $v,
            ];
        }
        return $models;
    }

    public function colors() {
        return data('cars/colors');
    }

}