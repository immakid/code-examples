<?php

namespace App\Http\Requests\Dealership\Clients\CarRequests;

use App\Rules\ValidCategoryId;
use App\Rules\ValidMarkId;
use App\Rules\ValidModelId;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', $this->route('client'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'category_id' => ['bail', 'required', 'int', new ValidCategoryId],
            'mark_id'     => ['bail', 'required', 'int', new ValidMarkId($this->request->get('category_id'))],
            'model_id'    => ['bail', 'required', 'int', new ValidModelId($this->request->get('category_id'), $this->request->get('mark_id'))],
            'is_default'  => ['sometimes', 'bool'],
        ];
    }

    /**
     * Set user-frendly attributes names.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'category_id' => 'Тип',
            'mark_id'     => 'Марка',
            'model_id'    => 'Модель',
            'is_default'  => 'По-умолчанию',
        ];
    }
}
