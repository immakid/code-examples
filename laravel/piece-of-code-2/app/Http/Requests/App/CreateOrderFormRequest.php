<?php

namespace App\Http\Requests\App;

use Illuminate\Support\Arr;
use App\Acme\Libraries\Http\FormRequest;

class CreateOrderFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function rules() {

        $ids = [];
        $guestAddresss = session()->get('guest_address');

        if(app('acl')->getUser()) {
            $addresses = app('acl')->getUser()->addresses;

            foreach ($addresses as $address) {

                $type = $address->pivot->type;
                if (!Arr::get($ids, $type)) {
                    Arr::set($ids, $type, []);
                }

                array_push($ids[$type], $address->id);
            }
        }

        $rules = [
            'tos' => 'required|in:1',
            'data' => [
                'payment' => [
                    'method' => sprintf("required:in:%s", implode(',', config('cms.payment_methods', [])))
                ]
            ]
        ];

        if(app('acl')->getUser()) {

            $rules['addresses']['shipping'] = sprintf("required|in:%s", implode(',', Arr::get($ids, 'shipping', [])));

            if (!$this->input('addresses._same')) {
                $rules['addresses']['billing'] = sprintf("required|in:%s", implode(',', Arr::get($ids, 'billing', [])));
            }

        }else if(!$guestAddresss){
            $rules['Addresses'] = 'required';
        }else {
            $rules['guest_username'] = 'required';
            $rules['guest_tos'] = 'required|in:1';
        }



        return $rules;
    }
}
