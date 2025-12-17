<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimularRoverRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'x' => ['required', 'integer', 'min:0', 'max:199'],
            'y' => ['required', 'integer', 'min:0', 'max:199'],
            'direccion' => ['required', 'in:N,S,E,W'],
            'comandos' => ['required', 'string', 'regex:/^[FfLlRr]+$/'],

            'obstaculos' => ['sometimes', 'array'],
            'obstaculos.*.x' => ['required_with:obstaculos', 'integer', 'min:0', 'max:199'],
            'obstaculos.*.y' => ['required_with:obstaculos', 'integer', 'min:0', 'max:199'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
