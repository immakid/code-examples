<?php

namespace App\Http\Requests\Stores;

use StoreFacade;
use App\Models\Region;
use App\Models\Language;
use Illuminate\Support\Arr;
use App\Acme\Libraries\Http\FormRequest;
use App\Acme\Interfaces\MultilingualRequest;

class SubmitStoreFormRequest extends FormRequest implements MultilingualRequest
{

    /**
     * @return array
     */
    public function rulesStatic()
    {
        $rules = [
            'name' => 'required|max:255',
            'domain' => 'required|max:255|regex:/^[A-z0-9]+$/',
            'languages.default' => sprintf("required|exists:%s,id", get_table_name(Language::class)),
            'languages.enabled' => sprintf("required|exists:%s,id", get_table_name(Language::class)),
//            'media[banner]' => 'required_if:banner_enabled,1',
        ];

        if ($this->method() === 'post') {
            $rules['region_id'] = sprintf("required|exists:%s,id", get_table_name(Region::class));
        }

        return $rules;
    }

    /**
     * @return array
     */
    public function validationData()
    {
        $data = $this->all();
        $method = $this->method();
        $store = $this->route('store');

        $fields = array_merge(['enabled', 'featured', 'best_selling'], Arr::except(array_keys(StoreFacade::getPayExFields()), [
            'notifications.email',
        ]));

        foreach ($fields as $key) {
            if (in_array($method, ['put', 'patch'])) {
                switch ($key) {
                    case 'enabled':
                        if (!$store->canBeEnabled) {
                            $data = Arr::except($data, $key);
                        }
                        break;
                }
            }

            if (!user_belongs_to('wg_admin')) {
                $data = Arr::except($data, $key);
            }
        }

        if (in_array($method, ['put', 'patch'])) {

            /**
             * Replace only fields fillable via UI
             */

            foreach (array_replace_recursive(Arr::only(Arr::dot($store->data()), $fields), Arr::only(Arr::dot(Arr::get($data, 'data')), $fields)) as $key => $value) {
                Arr::set($data, "data.$key", $value ?: $store->data($key));
            }
        }

        $this->replace($data);

        return $data;
    }

    /**
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    protected function withValidator($validator)
    {
        if ($this->method() === 'post') {
            $region = Region::find($this->input('region_id'));
        } else {
            $region = $this->route('store')->region;
        }

        $validator->after(function ($validator) use ($region) {

            // Check if all selected languages as enabled for region
            $ids = Arr::pluck($region->languages->toArray(), 'id');
            foreach (['enabled', 'default'] as $key) {
                $field = sprintf("languages.%s", $key);
                foreach ((array)$this->input($field) as $id) {
                    if (!in_array($id, $ids)) {
                        $validator->errors()->add($field, __t('messages.error.invalid_languages_selected'));
                        break;
                    }
                }
            }

            // Check for duplicate sub-domain within region
            $query = $region->stores()->domain($this->input('domain'));
            if (in_array($this->method(), ['put', 'patch'])) {
                $query = $query->where('id', '!=', $this->route()->store->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('domain', __t('messages.error.domain_exists'));
            }
        });
    }
}
