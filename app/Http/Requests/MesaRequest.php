<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ubicacion_id' => ['required', 'integer', Rule::exists('ubicaciones', 'id')],
            'numero' => [
                'required', 'integer', 'min:1', 'max:9999',
                // Unico entre las mesas vivas de esa ubicacion: una mesa dada de baja
                // no reserva su numero para siempre.
                Rule::unique('mesas', 'numero')
                    ->where(fn ($q) => $q->where('ubicacion_id', $this->integer('ubicacion_id'))->whereNull('deleted_at'))
                    ->ignore($this->route('mesa')),
            ],
            'capacidad' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.unique' => 'Esa ubicacion ya tiene una mesa con ese numero.',
        ];
    }
}
