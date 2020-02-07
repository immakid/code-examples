<?php

namespace App\Acme\Libraries\Datatables;

use DB;
use View;
use Closure;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Translatable;
use Yajra\Datatables\Datatables as YajraDatatables;

class Datatables extends YajraDatatables {

    /**
     * @var array
     */
    protected static $defaults = [
        'checkboxes' => true
    ];

    /**
     * @param mixed $builder
     * @param array|null $options
     * @return mixed
     */
    public static function of($builder, array $options = null) {

        $instance = parent::of($builder);
        $config = array_merge(self::$defaults, (array)$options);

        if ($config['checkboxes']) {
            $instance->rawColumns(['checkbox']);
            $instance->addColumn('checkbox', function ($model) {
                return View::make('backend._partials.checkbox-ids', ['id' => $model->id])->render();
            });
        }

//	    $instance->setRowData([
//		    'data-id' => function ($model) {
//			    return $model->id;
//		    }
//	    ]);

        $instance->setRowAttr([
            'data-id' => function ($model) {
                return $model->id;
            }
        ]);

        return $instance;
    }

    /**
     * @param Translatable $model
     * @param $language_id
     * @param array|null $conditions
     * @param array|null $options
     * @return mixed
     */
    public static function ofTranslatable(Translatable $model, $language_id, Closure $callback, array $options = null) {

        $table1 = get_table_name($model);
        $table2 = get_table_name($model->getTranslatorClass());

        $columns = ["$table1.*"];
        foreach ($model->getTranslatorColumns() as $column) {
            array_push($columns, "translation.$column as translation_$column");
        }

        $query = DB::table($table1)->select($columns)
            ->join("$table2 as translation", "$table1.id", '=', "translation.parent_id")
            ->where("translation.language_id", '=', $language_id);

        if (in_array(SoftDeletes::class, class_uses($model))) {
            $query = $query->where(sprintf("%s.deleted_at", $table1), '=', null);
        }

        if ($callback) {
            $query = $callback($query, $table1, $table2);
        }

        return self::of($query, $options);
    }
}