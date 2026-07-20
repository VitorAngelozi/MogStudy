<?php

namespace App\Http\Requests\StudyGroups;

use Illuminate\Foundation\Http\FormRequest;

class StoreFocusRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'icon' => ['nullable', 'string', 'max:40'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => trim((string) $this->input('description')),
            'icon' => trim((string) $this->input('icon')),
        ]);
    }
}
