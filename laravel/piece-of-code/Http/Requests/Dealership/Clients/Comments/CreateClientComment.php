<?php

namespace App\Http\Requests\Dealership\Clients\Comments;

use Illuminate\Foundation\Http\FormRequest;

class CreateClientComment extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('view', $this->route('client'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'comment' => 'required|string|max:5000',
        ];
    }

    public function attributes()
    {
        return ['comment' => 'Комментарий'];
    }
}
