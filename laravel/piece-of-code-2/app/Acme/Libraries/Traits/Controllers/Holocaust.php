<?php

namespace App\Acme\Libraries\Traits\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait Holocaust {

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyMultiple(Request $request) {

        $table = get_table_name(static::$holocaustModel);
        $this->validate($request, ['ids' => sprintf('required|exists:%s,id', $table)]);

        foreach ($request->input('ids', []) as $id) {

            try {

                $instance = call_user_func([static::$holocaustModel, 'findOrFail'], $id);
                $instance->delete();
            } catch (ModelNotFoundException $e) {
                continue;
            }
        }

        if(isset(static::$holocaustCallback) && is_callable(static::$holocaustCallback)) {
        	call_user_func(static::$holocaustCallback);
        }

        flash()->success(__t('messages.success.deleted', ['object' => __t('messages.objects.items')]));
        return redirect()->back();
    }
}