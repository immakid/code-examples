<?php

namespace App\Http\Requests;

use App\Models\Region;
use App\Models\Language;
use App\Acme\Libraries\Http\FormRequest;

class SubmitRegionFormRequest extends FormRequest {

    /**
     * @return array
     */
    public function validationData() {

        $input = $this->all();
        array_walk($input, function (&$value, $key) { // prepare domain for validation
            $value = ($key === 'domain') ? string_strip_protocol($value) : $value;
        });

        $this->replace($input);
        return $this->all();
    }

    /**
     * @return array
     */
    public function rules() {

        $domain_regex = '^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$^';
        $language_rule = sprintf("required|exists:%s,id", get_table_name(Language::class));
        $currency_rule = sprintf("required|exists:%s,id", get_table_name(Language::class));

        $rules = [
            'name' => 'required|max:255',
            'languages.default' => $language_rule,
            'languages.enabled' => $language_rule,
            'currencies.default' => $currency_rule,
            'currencies.enabled' => $currency_rule,
            'domain' => sprintf("required|max:255|regex:%s|unique:%s,domain", $domain_regex, get_table_name(Region::class)),
        ];

        if (in_array($this->method(), ['put', 'patch'])) {
            $rules['domain'] .= sprintf(",%d", $this->route('region')->id);
        }

        return $rules;
    }
}
