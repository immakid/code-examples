<?php

namespace App\Http\Requests\Dealership\Clients;

use App\Rules\CyrillicOnlyChars;
use App\Rules\ValidCategoryId;
use App\Rules\ValidCity;
use App\Rules\ValidMarkId;
use App\Rules\ValidModelId;
use App\Rules\ValidPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateClient extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                => ['required', 'string', 'max:50', new CyrillicOnlyChars],
            'phone'               => ['required', 'numeric', 'digits:10', new ValidPhoneNumber],
            'user_id'             => ['nullable', 'exists:users,id'],
            'status'              => ['nullable', Rule::in(collect(\App\Models\Dealership\Client::STATUSES)->pluck('id')->toArray())],
            'year_from'           => ['nullable', 'required_with:year_to', 'numeric', 'digits:4'],
            'year_to'             => [
                'nullable',
                'required_with:year_from',
                'numeric',
                'digits:4',
                "min:{$this->request->get('year_from')}",
                "max:2018",
            ],
            'budget_from'         => 'required|numeric|min:0',
            'budget_to'           => 'required|numeric|max:999999|min:'.(int) $this->request->get('budget_from'),
            'city'                => ['required', 'int', new ValidCity],
            'transmission_id'     => 'required|exists:transmissions,id',
            'comment'             => 'nullable|string|max:5000',
            'request'             => 'required',
            'request.category_id' => ['required', 'int', new ValidCategoryId()],
            'request.mark_id'     => ['required', 'int', new ValidMarkId($this->request->get('request')['category_id'])],
            'request.model_id'    => [
                'required',
                'int',
                new ValidModelId($this->request->get('request')['category_id'], $this->request->get('request')['mark_id']),
            ],
        ];
    }

    public function attributes()
    {
        return [
            'name'                => 'ФИО',
            'phone'               => 'Номер телефона',
            'user_id'             => 'Ответственный',
            'status'              => 'Статус',
            'year_from'           => 'Год(от)',
            'year_to'             => 'Год(до)',
            'budget_from'         => 'Бюджет(от)',
            'budget_to'           => 'Бюджет(до)',
            'city'                => 'Город',
            'transmission_id'     => 'КПП',
            'comment'             => 'Комментарий',
            'request.category_id' => 'Тип авто',
            'request.mark_id'     => 'Марка авто',
            'request.model_id'    => 'Модель авто',
        ];
    }
}
